<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_login();
$user = current_user();

$orderId = (int)($_POST['order_id'] ?? 0);
$method = trim($_POST['payment_method'] ?? 'mobile');

if ($orderId <= 0) {
    redirect('pages/marketplace.php');
}

$pdo = db();
$stmt = $pdo->prepare('SELECT id, total_amount FROM orders WHERE id = ? AND buyer_id = ?');
$stmt->execute([$orderId, (int)$user['id']]);
$order = $stmt->fetch();
if (!$order) {
    redirect('pages/marketplace.php');
}

$insert = $pdo->prepare('INSERT INTO payments (order_id, amount, method, status, transaction_ref, paid_at) VALUES (?, ?, ?, "success", ?, NOW())');
$insert->execute([$orderId, $order['total_amount'], $method, 'TXN' . time()]);

$update = $pdo->prepare('UPDATE orders SET payment_status = "paid", status = "confirmed", payment_method = ? WHERE id = ?');
$update->execute([$method, $orderId]);

redirect('pages/payment-success.php?order_id=' . $orderId);
