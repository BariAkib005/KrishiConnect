<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$user = require_role('finance');

$applicationId = (int)($_POST['application_id'] ?? 0);
$action = trim($_POST['action'] ?? '');

if ($applicationId <= 0 || ($action !== 'approve' && $action !== 'reject')) {
    redirect('pages/loan-management.php');
}

$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM loan_applications WHERE id = ? AND status = "pending"');
$stmt->execute([$applicationId]);
$app = $stmt->fetch();
if (!$app) {
    redirect('pages/loan-management.php');
}

if ($action === 'reject') {
    $upd = $pdo->prepare('UPDATE loan_applications SET status = "rejected", reviewed_by = ? WHERE id = ?');
    $upd->execute([(int)$user['id'], $applicationId]);
    redirect('pages/loan-management.php?tab=rejected');
}

$tenure = (int)($app['tenure_months'] ?? 12);
$tenure = $tenure > 0 ? $tenure : 12;
$rate = 9.5;
$principal = (float)$app['requested_amount'];
$monthlyRate = $rate / 12 / 100;
$emi = $monthlyRate > 0
    ? ($principal * $monthlyRate * pow(1 + $monthlyRate, $tenure)) / (pow(1 + $monthlyRate, $tenure) - 1)
    : ($principal / $tenure);
$emi = round($emi, 2);

$loanStmt = $pdo->prepare(
    'INSERT INTO loans (farmer_id, loan_type, principal, interest_rate, tenure_months, status, approved_amount, disbursed_at)
     VALUES (?, ?, ?, ?, ?, "active", ?, NOW())'
);
$loanStmt->execute([
    $app['farmer_id'],
    $app['purpose'] ?: 'Agriculture Loan',
    $principal,
    $rate,
    $tenure,
    $principal,
]);
$loanId = (int)$pdo->lastInsertId();

// for ($i = 1; $i <= $tenure; $i++) {
//     $dueDate = date('Y-m-d', strtotime("+{$i} month"));
//     $payStmt = $pdo->prepare('INSERT INTO loan_payments (loan_id, amount, due_date, status) VALUES (?, ?, ?, "due")');
//     $payStmt->execute([$loanId, $emi, $dueDate]);
// }

// $upd = $pdo->prepare('UPDATE loan_applications SET status = "approved", reviewed_by = ? WHERE id = ?');
// $upd->execute([(int)$user['id'], $applicationId]);

// redirect('pages/loan-management.php?tab=approved');

for ($i = 1; $i <= $tenure; $i++) {
    $dueDate = date('Y-m-d', strtotime("+{$i} month"));

    // Insert according to current schema: loan_payments has (loan_id, amount, due_date, paid_at, status)
    $payStmt = $pdo->prepare('INSERT INTO loan_payments (loan_id, amount, due_date, status) VALUES (?, ?, ?, "due")');
    $payStmt->execute([$loanId, $emi, $dueDate]);
}

$upd = $pdo->prepare('UPDATE loan_applications SET status = "approved", reviewed_by = ? WHERE id = ?');
$upd->execute([(int)$user['id'], $applicationId]);

redirect('pages/loan-management.php?tab=approved');
