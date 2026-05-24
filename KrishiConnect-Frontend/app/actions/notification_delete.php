<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_login();
$user = current_user();
$notificationId = (int)($_POST['notification_id'] ?? 0);

if ($notificationId > 0) {
    $pdo = db();
    $stmt = $pdo->prepare('DELETE FROM notifications WHERE id = ? AND user_id = ?');
    $stmt->execute([$notificationId, (int)$user['id']]);
}

redirect('pages/notifications.php');
