<?php
require_once __DIR__ . '/../includes/auth.php';

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    redirect('pages/login.php?error=missing');
}

$pdo = db();
$stmt = $pdo->prepare('SELECT id, full_name, email, role, password_hash, status FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    redirect('pages/login.php?error=invalid');
}

if ($user['status'] !== 'active') {
    redirect('pages/login.php?error=inactive');
}

login_user($user);

switch ($user['role']) {
    case 'admin':
        redirect('pages/admin-dashboard.php');
    case 'finance':
        redirect('pages/finance-dashboard.php');
    case 'buyer':
        redirect('pages/buyer-dashboard.php');
    default:
        redirect('pages/dashboard.php');
}
