<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$user = require_role('farmer');

$loanType = trim($_POST['loan_type'] ?? '');
$amount = (float)($_POST['loan_amount'] ?? 0);
$purpose = trim($_POST['purpose'] ?? '');
$tenure = (int)($_POST['tenure_months'] ?? 0);
$location = trim($_POST['location'] ?? '');
$farmSize = trim($_POST['farm_size'] ?? '');
$raw_income = trim($_POST['monthly_income'] ?? '');
// Normalize income input to a numeric value (strip commas, currency text etc.)
$income_value = (float) preg_replace('/[^0-9\.]/', '', $raw_income);
$collateral = trim($_POST['collateral'] ?? '');
$bankName = trim($_POST['bank_name'] ?? '');
$bankAccount = trim($_POST['bank_account'] ?? '');

// Basic required fields check
if ($amount <= 0 || $purpose === '' || $tenure <= 0) {
    $_SESSION['loan_error'] = 'Please fill out the required fields.';
    $_SESSION['old_income'] = $_POST['monthly_income'] ?? '';
    $_SESSION['old_amount'] = $_POST['loan_amount'] ?? '';
    redirect('pages/loan-application.php');
}

// Loan ceiling rules
$min_loan = 5000;
$max_loan = 30000;

if ($income_value >= 25000 && $income_value <= 50000) {
    $max_loan = 50000;
} elseif ($income_value > 50000 && $income_value <= 100000) {
    $min_loan = 50000;
    $max_loan = 100000;
} elseif ($income_value > 100000) {
    $min_loan = 50000;
    $max_loan = 150000;
}

if ($amount < $min_loan || $amount > $max_loan) {
    $_SESSION['loan_error_field'] = 'amount_error';
    $_SESSION['allowed_min'] = $min_loan;
    $_SESSION['allowed_max'] = $max_loan;
    $_SESSION['old_income'] = $_POST['monthly_income'] ?? '';
    $_SESSION['old_amount'] = $_POST['loan_amount'] ?? '';
    header("Location: ../pages/loan-application.php");
    exit();
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
    $raw_income,
    $collateral,
    $bankName,
    $bankAccount,
    $risk,
]);

redirect('pages/loans.php?submitted=1');
