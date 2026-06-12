<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_login();
require_csrf_token($_POST['csrf_token'] ?? null, 'app', 'pages/notifications.php?error=csrf');
$user = current_user();

$pdo = db();
$stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?');
$stmt->execute([(int)$user['id']]);

redirect('pages/notifications.php');
