<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$user = require_role('farmer');
require_csrf_token($_POST['csrf_token'] ?? null, 'app', 'pages/loan-application.php?error=csrf');

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

// Per-application loan amount limits: every farmer may request between the
// minimum and a maximum of BDT 1,50,000 (single source of truth in helpers).
[$min_loan, $max_loan] = loan_amount_bounds();

if ($amount < $min_loan || $amount > $max_loan) {
    $_SESSION['loan_error_field'] = 'amount_error';
    $_SESSION['allowed_min'] = $min_loan;
    $_SESSION['allowed_max'] = $max_loan;
    $_SESSION['old_income'] = $_POST['monthly_income'] ?? '';
    $_SESSION['old_amount'] = $_POST['loan_amount'] ?? '';
    header("Location: ../pages/loan-application.php");
    exit();
}

// Outstanding-due eligibility: blocks a new application when the farmer's
// EXISTING due already exceeds the absolute 1,00,000 cap or their income-tier
// ceiling. (The per-application amount itself is bounded above by $max_loan.)
$eligibility = loan_eligibility((int)$user['id'], $income_value);
if (!$eligibility['can_apply']) {
    $_SESSION['loan_error'] = $eligibility['reason'];
    $_SESSION['old_income'] = $_POST['monthly_income'] ?? '';
    $_SESSION['old_amount'] = $_POST['loan_amount'] ?? '';
    redirect('pages/loan-application.php');
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
