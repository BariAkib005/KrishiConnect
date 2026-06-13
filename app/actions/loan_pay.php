<?php
/**
 * Initiate a loan installment payment through SSLCommerz (sandbox). Reuses the
 * same gateway client as the buyer checkout but is keyed entirely off
 * loan_payments, so the order flow is unaffected.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/sslcommerz.php';

$user = require_role('farmer');
require_csrf_token($_POST['csrf_token'] ?? null, 'app', 'pages/repayment_ledger.php?error=csrf');

$paymentId = (int)($_POST['payment_id'] ?? 0);
if ($paymentId <= 0) {
    redirect('pages/repayment_ledger.php?error=invalid');
}

$pdo = db();

// The installment must belong to this farmer's loan and still be unpaid.
$stmt = $pdo->prepare(
    'SELECT lp.id, lp.loan_id, lp.amount, lp.status
     FROM loan_payments lp
     JOIN loans l ON l.id = lp.loan_id
     WHERE lp.id = ? AND l.farmer_id = ? AND lp.status <> "paid"'
);
$stmt->execute([$paymentId, (int)$user['id']]);
$payment = $stmt->fetch();
if (!$payment) {
    redirect('pages/repayment_ledger.php?error=notfound');
}

// Installments are paid in order — only the earliest unpaid one is payable.
$earliest = $pdo->prepare('SELECT id FROM loan_payments WHERE loan_id = ? AND status <> "paid" ORDER BY due_date ASC, id ASC LIMIT 1');
$earliest->execute([(int)$payment['loan_id']]);
if ((int)($earliest->fetch()['id'] ?? 0) !== $paymentId) {
    redirect('pages/repayment_ledger.php?error=sequence');
}

$amount = (float)$payment['amount'];

/* -------------------------------------------------------------------------
 * Mock fallback: no SSLCommerz credentials configured. Mark the installment
 * paid directly so the project still works out of the box.
 * ---------------------------------------------------------------------- */
if (!sslcommerz_enabled()) {
    sslcommerz_mark_loan_payment_paid($pdo, $payment);
    redirect('pages/repayment_ledger.php?paid=1');
}

/* -------------------------------------------------------------------------
 * Real SSLCommerz sandbox flow.
 * ---------------------------------------------------------------------- */
$tranId = 'KCL' . $paymentId . '-' . bin2hex(random_bytes(6));
$pdo->prepare('UPDATE loan_payments SET tran_id = ? WHERE id = ?')->execute([$tranId, $paymentId]);

$acct = $pdo->prepare('SELECT full_name, email, phone FROM users WHERE id = ?');
$acct->execute([(int)$user['id']]);
$account = $acct->fetch() ?: [];

$payload = [
    'total_amount' => number_format($amount, 2, '.', ''),
    'currency' => 'BDT',
    'tran_id' => $tranId,
    'success_url' => absolute_url('app/actions/loan_payment_success.php'),
    'fail_url' => absolute_url('app/actions/loan_payment_fail.php'),
    'cancel_url' => absolute_url('app/actions/loan_payment_cancel.php'),
    'ipn_url' => absolute_url('app/actions/loan_payment_ipn.php'),

    'cus_name' => $account['full_name'] ?? ($user['full_name'] ?? 'Farmer'),
    'cus_email' => $account['email'] ?? ($user['email'] ?? 'farmer@example.com'),
    'cus_add1' => 'N/A',
    'cus_city' => 'Dhaka',
    'cus_postcode' => '1000',
    'cus_country' => 'Bangladesh',
    'cus_phone' => $account['phone'] ?? '01700000000',

    // Loan repayment is a non-physical product, so no shipping leg.
    'shipping_method' => 'NO',
    'num_of_item' => 1,
    'product_name' => 'KrishiConnect Loan EMI #' . $paymentId,
    'product_category' => 'Loan Repayment',
    'product_profile' => 'non-physical-goods',
];

$result = sslcommerz_initiate($payload);
if ($result['ok']) {
    header('Location: ' . $result['redirect']);
    exit;
}

redirect('pages/repayment_ledger.php?error=gateway');
