<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_login();
$user = current_user();
if (($user['role'] ?? '') !== 'admin') {
    redirect('pages/dispute-resolution.php');
}

$disputeId = (int)($_POST['dispute_id'] ?? 0);
$status = trim($_POST['status'] ?? '');
if ($disputeId <= 0 || !in_array($status, ['in_review', 'resolved'], true)) {
    redirect('pages/dispute-resolution.php');
}

$pdo = db();
$resolvedAt = $status === 'resolved' ? 'NOW()' : 'NULL';
$stmt = $pdo->prepare("UPDATE disputes SET status = ?, handled_by = ?, resolved_at = {$resolvedAt} WHERE id = ?");
$stmt->execute([$status, (int)$user['id'], $disputeId]);

redirect('pages/dispute-resolution.php?status=' . $status);
