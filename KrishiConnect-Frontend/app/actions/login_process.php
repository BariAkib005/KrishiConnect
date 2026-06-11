<?php
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pages/login.php?error=method');
}

require_csrf_token($_POST['csrf_token'] ?? null, 'login_form', 'pages/login.php?error=csrf');

$identity = trim((string)($_POST['email_or_username'] ?? $_POST['email'] ?? ''));
$password = (string)($_POST['password'] ?? '');

if ($identity === '' || $password === '') {
    redirect('pages/login.php?error=missing');
}

$pdo = db();
$stmt = $pdo->prepare(
    'SELECT id, full_name, email, role, password_hash, status
     FROM users
     WHERE role <> :admin_role
       AND (email = :identity OR full_name = :identity)
     LIMIT 1'
);
$stmt->execute([
    ':admin_role' => 'admin',
    ':identity' => $identity,
]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    redirect('pages/login.php?error=invalid');
}

if ($user['status'] !== 'active') {
    redirect('pages/login.php?error=inactive');
}

login_user($user);

switch ($user['role']) {
    case 'farmer':
        redirect('pages/dashboard.php');
    case 'buyer':
        redirect('pages/buyer-dashboard.php');
    case 'finance':
        redirect('pages/finance-dashboard.php');
    default:
        logout_user();
        redirect('pages/login.php?error=unauthorized');
}
