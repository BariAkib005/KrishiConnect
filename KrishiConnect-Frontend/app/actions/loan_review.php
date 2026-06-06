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
$principal = (float)$app['requested_amount'];

// Query loan_products table for interest rates based on loan product
$productStmt = $pdo->prepare(
    'SELECT min_interest_rate, max_interest_rate FROM loan_products WHERE id = ? AND is_active = 1'
);
$productStmt->execute([(int)$app['loan_product_id']]);
$product = $productStmt->fetch();
if ($product) {
    // Use average of min and max rates for the EMI calculation
    $rate = ((float)$product['min_interest_rate'] + (float)$product['max_interest_rate']) / 2;
} else {
    $rate = 9.5; // Fallback to 9.5% if no product found
}
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
    
    $payStmt = $pdo->prepare('INSERT INTO loan_payments (loan_id, payment_number, principal_amount, total_amount, due_date, status) VALUES (?, ?, ?, ?, ?, "due")');
    $payStmt->execute([$loanId, $i, $emi, $emi, $dueDate]);
}

$upd = $pdo->prepare('UPDATE loan_applications SET status = "approved", reviewed_by = ? WHERE id = ?');
$upd->execute([(int)$user['id'], $applicationId]);

redirect('pages/loan-management.php?tab=approved');
