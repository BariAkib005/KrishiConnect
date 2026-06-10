<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cart.php';

require_login();
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

$stmt = $pdo->prepare('INSERT INTO orders (buyer_id, status, total_amount, payment_status, shipping_address) VALUES (?, "pending", ?, "unpaid", ?)');
$stmt->execute([(int)$user['id'], $totals['total'], $address]);
$orderId = (int)$pdo->lastInsertId();

foreach ($items as $item) {
    $insert = $pdo->prepare('INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)');
    $insert->execute([$orderId, $item['product_id'], $item['quantity'], $item['unit_price']]);
}

$cartId = get_or_create_cart_id((int)$user['id']);
$pdo->prepare('UPDATE carts SET status = "checked_out" WHERE id = ?')->execute([$cartId]);

redirect('pages/payment.php?order_id=' . $orderId);
