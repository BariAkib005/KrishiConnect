<?php
/** SSLCommerz failure callback — records the failed attempt, leaves the order unpaid. */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/sslcommerz.php';

$tranId = (string)($_REQUEST['tran_id'] ?? '');
if ($tranId === '') {
    redirect('pages/marketplace.php');
}

$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM orders WHERE tran_id = ?');
$stmt->execute([$tranId]);
$order = $stmt->fetch();
if (!$order) {
    redirect('pages/marketplace.php');
}

if (($order['payment_status'] ?? '') !== 'paid') {
    sslcommerz_record_failure($pdo, $order, (string)($_REQUEST['error'] ?? 'Payment failed'));
}

redirect('pages/payment.php?order_id=' . (int)$order['id'] . '&error=failed');
