<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_login();
$user = current_user();
if (($user['role'] ?? '') !== 'admin') {
    redirect('pages/admin-dashboard.php');
}

$userId = (int)($_POST['user_id'] ?? 0);
if ($userId > 0) {
    $pdo = db();
    $stmt = $pdo->prepare('UPDATE users SET status = "suspended" WHERE id = ?');
    $stmt->execute([$userId]);
}

redirect('pages/user-moderation.php');
