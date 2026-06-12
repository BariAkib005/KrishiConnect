<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$user = require_role('farmer');
require_csrf_token($_POST['csrf_token'] ?? null, 'loan_repay', 'pages/repayment_ledger.php?error=csrf');

$paymentId = (int)($_POST['payment_id'] ?? 0);
if ($paymentId <= 0) {
    redirect('pages/repayment_ledger.php?error=invalid');
}

$pdo = db();

// Only allow repaying an unpaid installment that belongs to this farmer's loan.
$stmt = $pdo->prepare(
    'SELECT lp.id, lp.loan_id
     FROM loan_payments lp
     JOIN loans l ON l.id = lp.loan_id
     WHERE lp.id = ? AND l.farmer_id = ? AND lp.status <> "paid"'
);
$stmt->execute([$paymentId, (int)$user['id']]);
$payment = $stmt->fetch();
if (!$payment) {
    redirect('pages/repayment_ledger.php?error=notfound');
}

$pdo->prepare('UPDATE loan_payments SET status = "paid", paid_at = ? WHERE id = ?')
    ->execute([date('Y-m-d'), (int)$payment['id']]);

// If every installment is settled, close the loan so the due drops to zero.
$remaining = $pdo->prepare('SELECT COUNT(*) AS c FROM loan_payments WHERE loan_id = ? AND status <> "paid"');
$remaining->execute([(int)$payment['loan_id']]);
if ((int)($remaining->fetch()['c'] ?? 0) === 0) {
    $pdo->prepare('UPDATE loans SET status = "closed" WHERE id = ?')->execute([(int)$payment['loan_id']]);
}

redirect('pages/repayment_ledger.php?paid=1');
