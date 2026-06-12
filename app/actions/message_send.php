<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_login();
require_csrf_token($_POST['csrf_token'] ?? null, 'app', 'pages/messaging.php?error=csrf');
$user = current_user();
$pdo = db();

$conversationId = (int)($_POST['conversation_id'] ?? 0);
$recipientId = (int)($_POST['recipient_id'] ?? 0);
$body = trim($_POST['body'] ?? '');
$subject = trim($_POST['subject'] ?? 'Marketplace conversation');

if (!$user || $body === '') {
    redirect('pages/messaging.php?error=empty');
}

$userId = (int)$user['id'];

if ($conversationId > 0) {
    $check = $pdo->prepare('SELECT 1 FROM conversation_participants WHERE conversation_id = ? AND user_id = ?');
    $check->execute([$conversationId, $userId]);
    if (!$check->fetch()) {
        redirect('pages/messaging.php?error=access');
    }
} elseif ($recipientId > 0 && $recipientId !== $userId) {
    $recipient = $pdo->prepare('SELECT id, full_name FROM users WHERE id = ? AND status = "active"');
    $recipient->execute([$recipientId]);
    if (!$recipient->fetch()) {
        redirect('pages/messaging.php?error=recipient');
    }

    $existing = $pdo->prepare(
        'SELECT cp1.conversation_id
         FROM conversation_participants cp1
         JOIN conversation_participants cp2 ON cp2.conversation_id = cp1.conversation_id
         WHERE cp1.user_id = ? AND cp2.user_id = ?
         LIMIT 1'
    );
    $existing->execute([$userId, $recipientId]);
    $row = $existing->fetch();

    if ($row) {
        $conversationId = (int)$row['conversation_id'];
    } else {
        $pdo->beginTransaction();
        try {
            $create = $pdo->prepare('INSERT INTO conversations (subject) VALUES (?)');
            $create->execute([$subject !== '' ? $subject : 'Marketplace conversation']);
            $conversationId = (int)$pdo->lastInsertId();

            $participant = $pdo->prepare('INSERT INTO conversation_participants (conversation_id, user_id) VALUES (?, ?)');
            $participant->execute([$conversationId, $userId]);
            $participant->execute([$conversationId, $recipientId]);

            $pdo->commit();
        } catch (Throwable $exception) {
            $pdo->rollBack();
            redirect('pages/messaging.php?error=create');
        }
    }
} else {
    redirect('pages/messaging.php?error=recipient');
}

$stmt = $pdo->prepare('INSERT INTO messages (conversation_id, sender_id, body) VALUES (?, ?, ?)');
$stmt->execute([$conversationId, $userId, $body]);

redirect('pages/messaging.php?conversation_id=' . $conversationId);
