<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$user = require_role('farmer');
require_csrf_token($_POST['csrf_token'] ?? null, 'farmer_profile', 'pages/farmer_profile.php?error=csrf#edit');

$fullName = trim($_POST['full_name'] ?? '');
if ($fullName === '') {
    redirect('pages/farmer_profile.php?error=name#edit');
}

$phone = trim($_POST['phone'] ?? '');
$farmName = trim($_POST['farm_name'] ?? '');
$location = trim($_POST['location'] ?? '');
$landArea = (float)($_POST['land_area'] ?? 0);
$monthlyIncome = (float)preg_replace('/[^0-9.]/', '', (string)($_POST['monthly_income'] ?? '0'));
$soilType = trim($_POST['soil_type'] ?? '');
$irrigation = trim($_POST['irrigation'] ?? '');
$bankName = trim($_POST['bank_name'] ?? '');
$bankAccount = trim($_POST['bank_account'] ?? '');
$bankBranch = trim($_POST['bank_branch'] ?? '');

$pdo = db();
$pdo->prepare('UPDATE users SET full_name = ?, phone = ? WHERE id = ?')
    ->execute([$fullName, $phone, (int)$user['id']]);

$stmt = $pdo->prepare(
    'INSERT INTO farmer_profiles
        (user_id, farm_name, location, land_area, soil_type, irrigation, monthly_income, bank_name, bank_account, bank_branch)
     VALUES (:uid, :farm, :loc, :land, :soil, :irr, :income, :bank, :acct, :branch)
     ON DUPLICATE KEY UPDATE
        farm_name = VALUES(farm_name),
        location = VALUES(location),
        land_area = VALUES(land_area),
        soil_type = VALUES(soil_type),
        irrigation = VALUES(irrigation),
        monthly_income = VALUES(monthly_income),
        bank_name = VALUES(bank_name),
        bank_account = VALUES(bank_account),
        bank_branch = VALUES(bank_branch)'
);
$stmt->execute([
    ':uid' => (int)$user['id'],
    ':farm' => $farmName,
    ':loc' => $location,
    ':land' => $landArea,
    ':soil' => $soilType,
    ':irr' => $irrigation,
    ':income' => $monthlyIncome,
    ':bank' => $bankName,
    ':acct' => $bankAccount,
    ':branch' => $bankBranch,
]);

redirect('pages/farmer_profile.php?saved=1');
