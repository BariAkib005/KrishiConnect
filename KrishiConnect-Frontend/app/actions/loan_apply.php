<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$user = require_role('farmer');

$loanType = trim($_POST['loan_type'] ?? '');
$amount = (float)($_POST['amount'] ?? 0);
$purpose = trim($_POST['purpose'] ?? '');
$tenure = (int)($_POST['tenure_months'] ?? 0);
$location = trim($_POST['location'] ?? '');
$farmSize = trim($_POST['farm_size'] ?? '');
$income = trim($_POST['monthly_income'] ?? '');
$collateral = trim($_POST['collateral'] ?? '');
$bankName = trim($_POST['bank_name'] ?? '');
$bankAccount = trim($_POST['bank_account'] ?? '');

if ($amount <= 0 || $purpose === '' || $tenure <= 0) {
    redirect('pages/loan-application.php?error=missing');
}

$risk = 'medium';
if ($amount >= 200000) {
    $risk = 'high';
} elseif ($amount <= 60000) {
    $risk = 'low';
}

$pdo = db();
$stmt = $pdo->prepare(
    'INSERT INTO loan_applications
        (farmer_id, requested_amount, purpose, tenure_months, location, farm_size, monthly_income, collateral, bank_name, bank_account, risk_level, status)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "pending")'
);
$stmt->execute([
    (int)$user['id'],
    $amount,
    $loanType !== '' ? ($loanType . ' - ' . $purpose) : $purpose,
    $tenure,
    $location,
    $farmSize,
    $income,
    $collateral,
    $bankName,
    $bankAccount,
    $risk,
]);

redirect('pages/loans.php?submitted=1');
