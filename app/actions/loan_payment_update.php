<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$user = require_role('finance');
require_csrf_token($_POST['csrf_token'] ?? null, 'app', 'pages/disbursements.php?error=csrf');
$paymentId = (int)($_POST['payment_id'] ?? 0);
$status = trim($_POST['status'] ?? '');

$allowed = ['paid', 'due', 'late'];
if ($paymentId <= 0 || !in_array($status, $allowed, true)) {
    redirect('pages/disbursements.php?error=invalid');
}

$pdo = db();
$stmt = $pdo->prepare('UPDATE loan_payments SET status = ?, paid_at = ? WHERE id = ?');
$stmt->execute([
    $status,
    $status === 'paid' ? date('Y-m-d') : null,
    $paymentId,
]);

redirect('pages/disbursements.php?updated=1#installments');
