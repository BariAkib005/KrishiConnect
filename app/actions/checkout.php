<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cart.php';

require_login();
require_csrf_token($_POST['csrf_token'] ?? null, 'app', 'pages/checkout.php?error=csrf');
$user = current_user();
$items = get_cart_items((int)$user['id']);

if (!$items) {
    redirect('pages/cart.php?error=empty');
}

$fullName = trim($_POST['full_name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$street = trim($_POST['street'] ?? '');
$city = trim($_POST['city'] ?? '');
$state = trim($_POST['state'] ?? '');
$postal = trim($_POST['postal'] ?? '');
$landmark = trim($_POST['landmark'] ?? '');

if ($fullName === '' || $phone === '' || $street === '' || $city === '' || $state === '' || $postal === '') {
    redirect('pages/checkout.php?error=missing');
}

$addressParts = [$fullName, $phone, $email, $street, $landmark, $city, $state, $postal];
$address = implode(', ', array_filter($addressParts, fn($v) => $v !== ''));

$totals = cart_totals($items);
$pdo = db();

// Remember the buyer's delivery details so they don't have to re-enter them.
$saveAddress = $pdo->prepare(
    'INSERT INTO buyer_addresses (user_id, full_name, phone, email, street, city, state, postal, landmark)
     VALUES (:uid, :name, :phone, :email, :street, :city, :state, :postal, :landmark)
     ON DUPLICATE KEY UPDATE
        full_name = VALUES(full_name), phone = VALUES(phone), email = VALUES(email),
        street = VALUES(street), city = VALUES(city), state = VALUES(state),
        postal = VALUES(postal), landmark = VALUES(landmark)'
);
$saveAddress->execute([
    ':uid' => (int)$user['id'],
    ':name' => $fullName,
    ':phone' => $phone,
    ':email' => $email,
    ':street' => $street,
    ':city' => $city,
    ':state' => $state,
    ':postal' => $postal,
    ':landmark' => $landmark,
]);

// Backfill the account phone the first time, without overwriting an existing one.
if ($phone !== '') {
    $pdo->prepare('UPDATE users SET phone = ? WHERE id = ? AND (phone IS NULL OR phone = "")')
        ->execute([$phone, (int)$user['id']]);
}

$cartId = get_or_create_cart_id((int)$user['id']);

// Create the order, its line items, and close the cart as one atomic unit so a
// mid-write failure can never leave a half-built order behind.
try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('INSERT INTO orders (buyer_id, status, total_amount, payment_status, shipping_address) VALUES (?, "pending", ?, "unpaid", ?)');
    $stmt->execute([(int)$user['id'], $totals['total'], $address]);
    $orderId = (int)$pdo->lastInsertId();

    $insertItem = $pdo->prepare('INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)');
    foreach ($items as $item) {
        $insertItem->execute([$orderId, $item['product_id'], $item['quantity'], $item['unit_price']]);
    }

    $pdo->prepare('UPDATE carts SET status = "checked_out" WHERE id = ?')->execute([$cartId]);

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    redirect('pages/checkout.php?error=failed');
}

redirect('pages/payment.php?order_id=' . $orderId);
