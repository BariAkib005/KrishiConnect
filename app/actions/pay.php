<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/sslcommerz.php';

require_login();
$user = current_user();
require_csrf_token($_POST['csrf_token'] ?? null, 'pay', 'pages/marketplace.php');

$orderId = (int)($_POST['order_id'] ?? 0);
$method = trim($_POST['payment_method'] ?? 'mobile');

if ($orderId <= 0) {
    redirect('pages/marketplace.php');
}

$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND buyer_id = ?');
$stmt->execute([$orderId, (int)$user['id']]);
$order = $stmt->fetch();
if (!$order) {
    redirect('pages/marketplace.php');
}

// Already settled — don't charge twice.
if (($order['payment_status'] ?? '') === 'paid') {
    redirect('pages/payment-success.php?order_id=' . $orderId);
}

/* -------------------------------------------------------------------------
 * Mock fallback: no SSLCommerz credentials configured. Keeps the project
 * working out-of-the-box exactly as before.
 * ---------------------------------------------------------------------- */
if (!sslcommerz_enabled()) {
    $insert = $pdo->prepare('INSERT INTO payments (order_id, amount, method, status, transaction_ref, paid_at) VALUES (?, ?, ?, "success", ?, NOW())');
    $insert->execute([$orderId, $order['total_amount'], $method, 'TXN' . time()]);

    $pdo->prepare('UPDATE orders SET payment_status = "paid", status = "confirmed", payment_method = ? WHERE id = ?')
        ->execute([$method, $orderId]);

    redirect('pages/payment-success.php?order_id=' . $orderId);
}

/* -------------------------------------------------------------------------
 * Real SSLCommerz sandbox flow.
 * ---------------------------------------------------------------------- */

// Unique per-attempt transaction id, stored so callbacks can find this order.
$tranId = 'KC' . $orderId . '-' . bin2hex(random_bytes(6));
$pdo->prepare('UPDATE orders SET tran_id = ? WHERE id = ?')->execute([$tranId, $orderId]);

// Customer details (prefer the saved delivery address, then the account).
$addr = $pdo->prepare('SELECT * FROM buyer_addresses WHERE user_id = ?');
$addr->execute([(int)$user['id']]);
$address = $addr->fetch() ?: [];

$acct = $pdo->prepare('SELECT full_name, email, phone FROM users WHERE id = ?');
$acct->execute([(int)$user['id']]);
$account = $acct->fetch() ?: [];

$itemCount = (int)($pdo->query('SELECT COUNT(*) c FROM order_items WHERE order_id = ' . $orderId)->fetch()['c'] ?? 1);

$customerName = $address['full_name'] ?? $account['full_name'] ?? ($user['full_name'] ?? 'Customer');
$customerEmail = $address['email'] ?? $account['email'] ?? ($user['email'] ?? 'buyer@example.com');
$customerPhone = $address['phone'] ?? $account['phone'] ?? '01700000000';

$payload = [
    'total_amount' => number_format((float)$order['total_amount'], 2, '.', ''),
    'currency' => 'BDT',
    'tran_id' => $tranId,
    'success_url' => absolute_url('app/actions/payment_success.php'),
    'fail_url' => absolute_url('app/actions/payment_fail.php'),
    'cancel_url' => absolute_url('app/actions/payment_cancel.php'),
    'ipn_url' => absolute_url('app/actions/payment_ipn.php'),

    'cus_name' => $customerName,
    'cus_email' => $customerEmail,
    'cus_add1' => $address['street'] ?? ($order['shipping_address'] ?? 'N/A'),
    'cus_city' => $address['city'] ?? 'Dhaka',
    'cus_postcode' => $address['postal'] ?? '1000',
    'cus_country' => 'Bangladesh',
    'cus_phone' => $customerPhone,

    'shipping_method' => 'Courier',
    'num_of_item' => max(1, $itemCount),
    'ship_name' => $customerName,
    'ship_add1' => $address['street'] ?? ($order['shipping_address'] ?? 'N/A'),
    'ship_city' => $address['city'] ?? 'Dhaka',
    'ship_postcode' => $address['postal'] ?? '1000',
    'ship_country' => 'Bangladesh',

    'product_name' => 'KrishiConnect Order #' . $orderId,
    'product_category' => 'Agriculture',
    'product_profile' => 'physical-goods',
];

$result = sslcommerz_initiate($payload);
if ($result['ok']) {
    header('Location: ' . $result['redirect']);
    exit;
}

redirect('pages/payment.php?order_id=' . $orderId . '&error=gateway');
