<?php
/**
 * SSLCommerz IPN (Instant Payment Notification) — a server-to-server POST from
 * SSLCommerz's servers. This is the authoritative confirmation. It mirrors the
 * success callback's validation but returns a plain text response instead of a
 * redirect (there is no browser here).
 *
 * Note: SSLCommerz cannot reach http://localhost, so on a local XAMPP setup the
 * IPN simply never fires and the success-redirect validation does the job. It
 * becomes active automatically once the app is reachable on a public URL.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/sslcommerz.php';

header('Content-Type: text/plain; charset=utf-8');

$tranId = (string)($_REQUEST['tran_id'] ?? '');
$valId = (string)($_REQUEST['val_id'] ?? '');
if ($tranId === '' || $valId === '') {
    echo 'IGNORED';
    exit;
}

$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM orders WHERE tran_id = ?');
$stmt->execute([$tranId]);
$order = $stmt->fetch();
if (!$order) {
    echo 'UNKNOWN';
    exit;
}

if (($order['payment_status'] ?? '') === 'paid') {
    echo 'OK';
    exit;
}

$validation = sslcommerz_validate($valId);
if (!sslcommerz_validation_matches($validation, $order)) {
    echo 'INVALID';
    exit;
}

sslcommerz_mark_order_paid(
    $pdo,
    $order,
    (string)($validation['card_type'] ?? 'SSLCommerz'),
    (string)($validation['bank_tran_id'] ?? $valId)
);

echo 'OK';
