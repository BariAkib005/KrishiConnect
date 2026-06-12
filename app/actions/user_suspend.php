<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$user = require_role('admin');
require_csrf_token($_POST['csrf_token'] ?? null, 'app', 'pages/user-moderation.php?error=csrf');

$userId = (int)($_POST['user_id'] ?? 0);
// An admin must not be able to suspend their own account.
if ($userId > 0 && $userId !== (int)$user['id']) {
    $pdo = db();
    $stmt = $pdo->prepare('UPDATE users SET status = "suspended" WHERE id = ?');
    $stmt->execute([$userId]);
}

redirect('pages/user-moderation.php');
