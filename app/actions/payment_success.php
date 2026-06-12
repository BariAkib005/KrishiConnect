<?php
/**
 * SSLCommerz success callback. The gateway POSTs here from a different origin,
 * so there is NO buyer session (and we deliberately do not start one — that
 * would clobber the login cookie). We identify the order by tran_id and only
 * trust the payment after a server-to-server validation of val_id.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/sslcommerz.php';

$tranId = (string)($_REQUEST['tran_id'] ?? '');
$valId = (string)($_REQUEST['val_id'] ?? '');

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
$orderId = (int)$order['id'];

if (($order['payment_status'] ?? '') === 'paid') {
    redirect('pages/payment-success.php?order_id=' . $orderId);
}

$validation = $valId !== '' ? sslcommerz_validate($valId) : null;
if (!sslcommerz_validation_matches($validation, $order)) {
    redirect('pages/payment.php?order_id=' . $orderId . '&error=validation');
}

sslcommerz_mark_order_paid(
    $pdo,
    $order,
    (string)($validation['card_type'] ?? 'SSLCommerz'),
    (string)($validation['bank_tran_id'] ?? $valId)
);

redirect('pages/payment-success.php?order_id=' . $orderId);
