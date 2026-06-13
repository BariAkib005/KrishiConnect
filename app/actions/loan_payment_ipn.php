<?php
/**
 * SSLCommerz IPN for loan repayments — a server-to-server POST. Authoritative
 * confirmation; mirrors the success callback but returns plain text. (On a local
 * XAMPP setup SSLCommerz cannot reach localhost, so the success redirect does
 * the work; this activates automatically on a public URL.)
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
$stmt = $pdo->prepare('SELECT id, loan_id, amount, status FROM loan_payments WHERE tran_id = ?');
$stmt->execute([$tranId]);
$payment = $stmt->fetch();
if (!$payment) {
    echo 'UNKNOWN';
    exit;
}

if (($payment['status'] ?? '') === 'paid') {
    echo 'OK';
    exit;
}

$validation = sslcommerz_validate($valId);
if (!sslcommerz_validation_ok($validation, $tranId, (float)$payment['amount'])) {
    echo 'INVALID';
    exit;
}

sslcommerz_mark_loan_payment_paid($pdo, $payment);
echo 'OK';
