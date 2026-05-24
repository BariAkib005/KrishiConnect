<?php
require_once __DIR__ . '/../includes/auth.php';

$fullName = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$role = $_POST['role'] ?? 'farmer';
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if ($fullName === '' || $email === '' || $password === '') {
    redirect('pages/register.php?error=missing');
}

if ($password !== $confirm) {
    redirect('pages/register.php?error=nomatch');
}

$pdo = db();
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    redirect('pages/register.php?error=exists');
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare('INSERT INTO users (full_name, email, phone, role, password_hash, status) VALUES (?, ?, ?, ?, ?, ?)');
$stmt->execute([$fullName, $email, $phone, $role, $hash, 'active']);

$userId = (int)$pdo->lastInsertId();
login_user(['id' => $userId, 'role' => $role]);

switch ($role) {
    case 'admin':
        redirect('pages/admin-dashboard.php');
    case 'finance':
        redirect('pages/finance-dashboard.php');
    case 'buyer':
        redirect('pages/buyer-dashboard.php');
    default:
        redirect('pages/dashboard.php');
}
