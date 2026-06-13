<?php
/**
 * SSLCommerz success callback for loan repayments. The gateway POSTs here from a
 * different origin, so there is NO farmer session (and we deliberately do not
 * start one). We identify the installment by tran_id and only mark it paid after
 * a server-to-server validation of val_id.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/sslcommerz.php';

$tranId = (string)($_REQUEST['tran_id'] ?? '');
$valId = (string)($_REQUEST['val_id'] ?? '');

if ($tranId === '') {
    redirect('pages/repayment_ledger.php');
}

$pdo = db();
$stmt = $pdo->prepare('SELECT id, loan_id, amount, status, tran_id FROM loan_payments WHERE tran_id = ?');
$stmt->execute([$tranId]);
$payment = $stmt->fetch();
if (!$payment) {
    redirect('pages/repayment_ledger.php');
}

if (($payment['status'] ?? '') === 'paid') {
    redirect('pages/repayment_ledger.php?paid=1');
}

$validation = $valId !== '' ? sslcommerz_validate($valId) : null;
if (!sslcommerz_validation_ok($validation, $tranId, (float)$payment['amount'])) {
    redirect('pages/repayment_ledger.php?error=validation');
}

sslcommerz_mark_loan_payment_paid($pdo, $payment);

redirect('pages/repayment_ledger.php?paid=1');
