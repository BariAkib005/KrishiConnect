<?php
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pages/admin_login.php?error=method');
}

require_csrf_token($_POST['csrf_token'] ?? null, 'admin_login_form', 'pages/admin_login.php?error=csrf');

$lockedUntil = (int)($_SESSION['admin_login_locked_until'] ?? 0);
if ($lockedUntil > time()) {
    redirect('pages/admin_login.php?error=locked');
}

$pin = preg_replace('/\D/', '', (string)($_POST['admin_pin'] ?? ''));
if (!preg_match('/^\d{6}$/', $pin)) {
    $_SESSION['admin_login_failures'] = (int)($_SESSION['admin_login_failures'] ?? 0) + 1;
    write_security_log(null, 'failed_admin_login', 'Malformed admin PIN submitted.');
    redirect('pages/admin_login.php?error=invalid');
}

$pdo = db();
$stmt = $pdo->prepare(
    'SELECT id, full_name, email, role, admin_pin_hash, status
     FROM users
     WHERE role = :role
       AND admin_pin_hash IS NOT NULL
     ORDER BY id ASC
     LIMIT 1'
);
$stmt->execute([':role' => 'admin']);
$admin = $stmt->fetch();

if (!$admin || !password_verify($pin, (string)$admin['admin_pin_hash'])) {
    $_SESSION['admin_login_failures'] = (int)($_SESSION['admin_login_failures'] ?? 0) + 1;

    if ((int)$_SESSION['admin_login_failures'] >= 5) {
        $_SESSION['admin_login_locked_until'] = time() + 300;
    }

    write_security_log(
        $admin ? (int)$admin['id'] : null,
        'failed_admin_login',
        'Failed admin PIN login attempt.'
    );

    redirect('pages/admin_login.php?error=invalid');
}

if ($admin['status'] !== 'active') {
    write_security_log((int)$admin['id'], 'failed_admin_login', 'Inactive admin account attempted PIN login.');
    redirect('pages/admin_login.php?error=inactive');
}

unset($_SESSION['admin_login_failures'], $_SESSION['admin_login_locked_until']);
login_user($admin);
redirect('pages/admin-dashboard.php');
