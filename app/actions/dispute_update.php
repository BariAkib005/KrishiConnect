<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$user = require_role('admin');
require_csrf_token($_POST['csrf_token'] ?? null, 'app', 'pages/dispute-resolution.php?error=csrf');

$disputeId = (int)($_POST['dispute_id'] ?? 0);
$status = trim($_POST['status'] ?? '');
if ($disputeId <= 0 || !in_array($status, ['in_review', 'resolved'], true)) {
    redirect('pages/dispute-resolution.php');
}

$pdo = db();
// Bind the timestamp as a value instead of splicing NOW()/NULL into the SQL.
$resolvedAt = $status === 'resolved' ? date('Y-m-d H:i:s') : null;
$stmt = $pdo->prepare('UPDATE disputes SET status = ?, handled_by = ?, resolved_at = ? WHERE id = ?');
$stmt->execute([$status, (int)$user['id'], $resolvedAt, $disputeId]);

redirect('pages/dispute-resolution.php?status=' . $status);
