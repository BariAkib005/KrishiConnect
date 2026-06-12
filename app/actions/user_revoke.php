<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$user = require_role('admin');
require_csrf_token($_POST['csrf_token'] ?? null, 'app', 'pages/user-moderation.php?error=csrf');

// Restores a suspended account or approves a pending one (e.g. a finance
// officer awaiting activation).
$userId = (int)($_POST['user_id'] ?? 0);
if ($userId > 0) {
    $pdo = db();
    $stmt = $pdo->prepare('UPDATE users SET status = "active" WHERE id = ?');
    $stmt->execute([$userId]);
}

redirect('pages/user-moderation.php');
