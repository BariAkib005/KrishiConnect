<?php
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/includes/helpers.php';
require_once __DIR__ . '/../app/includes/db.php';

require_login();
$user = current_user();
$userId = (int)$user['id'];
$pdo = db();
$selectedId = (int)($_GET['conversation_id'] ?? 0);
$error = $_GET['error'] ?? '';

$conversationStmt = $pdo->prepare(
    'SELECT c.id, c.subject, c.created_at,
            other_user.id AS other_id,
            other_user.full_name AS other_name,
            other_user.role AS other_role,
            (
                SELECT m.body FROM messages m
                WHERE m.conversation_id = c.id
                ORDER BY m.created_at DESC, m.id DESC
                LIMIT 1
            ) AS last_message,
            (
                SELECT m.created_at FROM messages m
                WHERE m.conversation_id = c.id
                ORDER BY m.created_at DESC, m.id DESC
                LIMIT 1
            ) AS last_at,
            (
                SELECT COUNT(*) FROM messages m
                WHERE m.conversation_id = c.id AND m.sender_id <> ? AND m.read_at IS NULL
            ) AS unread_count
     FROM conversations c
     JOIN conversation_participants me ON me.conversation_id = c.id AND me.user_id = ?
     JOIN conversation_participants other_cp ON other_cp.conversation_id = c.id AND other_cp.user_id <> ?
     JOIN users other_user ON other_user.id = other_cp.user_id
     ORDER BY COALESCE(last_at, c.created_at) DESC'
);
$conversationStmt->execute([$userId, $userId, $userId]);
$conversations = $conversationStmt->fetchAll();

if ($selectedId <= 0 && $conversations) {
    $selectedId = (int)$conversations[0]['id'];
}

$selected = null;
foreach ($conversations as $conversation) {
    if ((int)$conversation['id'] === $selectedId) {
        $selected = $conversation;
        break;
    }
}

$messages = [];
if ($selected) {
    $read = $pdo->prepare('UPDATE messages SET read_at = NOW() WHERE conversation_id = ? AND sender_id <> ? AND read_at IS NULL');
    $read->execute([$selectedId, $userId]);

    $messageStmt = $pdo->prepare(
        'SELECT m.*, u.full_name
         FROM messages m
         JOIN users u ON u.id = m.sender_id
         WHERE m.conversation_id = ?
         ORDER BY m.created_at ASC, m.id ASC'
    );
    $messageStmt->execute([$selectedId]);
    $messages = $messageStmt->fetchAll();
}

$contactRole = $user['role'] === 'buyer' ? 'farmer' : 'buyer';
$contactStmt = $pdo->prepare('SELECT id, full_name, role FROM users WHERE status = "active" AND role = ? AND id <> ? ORDER BY full_name');
$contactStmt->execute([$contactRole, $userId]);
$contacts = $contactStmt->fetchAll();

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $first = strtoupper(substr($parts[0] ?? 'U', 0, 1));
    $last = strtoupper(substr($parts[count($parts) - 1] ?? $first, 0, 1));
    return $first . $last;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - KrishiConnect</title>
    <link rel="stylesheet" href="<?= asset_url('css/styles.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<?php require __DIR__ . '/../app/includes/header.php'; ?>

<section class="section">
    <div class="container">
        <?php if ($error): ?>
            <div class="notice error"><i class="fas fa-circle-exclamation"></i> Could not send that message. Please check the form and try again.</div>
        <?php endif; ?>

        <div class="message-layout">
            <div class="message-list">
                <div class="message-header">
                    <h2>Messages</h2>
                    <p class="muted">Conversations with <?= $user['role'] === 'buyer' ? 'farmers' : 'buyers'; ?></p>
                </div>
                <div class="message-items">
                    <?php if (!$conversations): ?>
                        <div class="card" style="margin:1rem;text-align:center">No conversations yet.</div>
                    <?php endif; ?>
                    <?php foreach ($conversations as $conversation): ?>
                        <a class="message-item <?= (int)$conversation['id'] === $selectedId ? 'active' : ''; ?>" href="<?= url('pages/messaging.php?conversation_id=' . (int)$conversation['id']); ?>">
                            <div class="avatar"><?= htmlspecialchars(initials($conversation['other_name'])); ?></div>
                            <div>
                                <h4><?= htmlspecialchars($conversation['other_name']); ?></h4>
                                <p><?= htmlspecialchars($conversation['last_message'] ?: $conversation['subject']); ?></p>
                            </div>
                            <?php if ((int)$conversation['unread_count'] > 0): ?>
                                <span class="badge"><?= (int)$conversation['unread_count']; ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="message-start">
                    <h3>Start New Chat</h3>
                    <form method="post" action="<?= url('app/actions/message_send.php'); ?>">
                        <div class="form-group">
                            <label for="recipient_id">Contact</label>
                            <select id="recipient_id" name="recipient_id" required>
                                <option value="">Choose <?= htmlspecialchars($contactRole); ?></option>
                                <?php foreach ($contacts as $contact): ?>
                                    <option value="<?= (int)$contact['id']; ?>"><?= htmlspecialchars($contact['full_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="body_start">Message</label>
                            <textarea id="body_start" name="body" placeholder="Write your first message..." required></textarea>
                        </div>
                        <button class="btn btn-primary btn-block" type="submit">Start Conversation</button>
                    </form>
                </div>
            </div>

            <div class="message-chat">
                <?php if (!$selected): ?>
                    <div class="empty-chat">
                        <i class="fas fa-comments"></i>
                        <h3>Select or start a conversation</h3>
                        <p>Messages will appear here once you choose a contact.</p>
                    </div>
                <?php else: ?>
                    <div class="chat-header">
                        <div>
                            <h3><?= htmlspecialchars($selected['other_name']); ?></h3>
                            <span><?= htmlspecialchars(ucfirst($selected['other_role'])); ?> · <?= htmlspecialchars($selected['subject']); ?></span>
                        </div>
                    </div>
                    <div class="chat-body">
                        <?php foreach ($messages as $message): ?>
                            <div class="bubble <?= (int)$message['sender_id'] === $userId ? 'me' : 'other'; ?>">
                                <?= nl2br(htmlspecialchars($message['body'])); ?>
                                <small><?= date('M j, g:i A', strtotime($message['created_at'])); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <form class="chat-input" method="post" action="<?= url('app/actions/message_send.php'); ?>">
                        <input type="hidden" name="conversation_id" value="<?= (int)$selected['id']; ?>">
                        <input type="text" name="body" placeholder="Type a message..." required>
                        <button class="btn btn-primary btn-sm" type="submit">Send</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../app/includes/footer.php'; ?>
</body>
</html>
