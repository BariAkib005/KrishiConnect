<?php
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/includes/helpers.php';
require_once __DIR__ . '/../app/includes/db.php';

require_login();
$user = current_user();
$filter = $_GET['filter'] ?? 'all';

$pdo = db();
$where = 'user_id = ?';
$params = [(int)$user['id']];

if ($filter === 'unread') {
    $where .= ' AND is_read = 0';
} elseif ($filter !== 'all') {
    $where .= ' AND type = ?';
    $params[] = $filter;
}

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE {$where} ORDER BY created_at DESC");
$stmt->execute($params);
$notifications = $stmt->fetchAll();

$countStmt = $pdo->prepare('SELECT type, SUM(is_read = 0) AS unread, COUNT(*) AS total FROM notifications WHERE user_id = ? GROUP BY type');
$countStmt->execute([(int)$user['id']]);
$counts = ['all' => 0, 'unread' => 0, 'order' => 0, 'loan' => 0, 'price' => 0];
foreach ($countStmt->fetchAll() as $row) {
    $counts['all'] += (int)$row['total'];
    $counts['unread'] += (int)$row['unread'];
    $counts[$row['type']] = (int)$row['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications — KrishiConnect</title>
    <link rel="stylesheet" href="<?= asset_url('css/styles.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<?php require __DIR__ . '/../app/includes/header.php'; ?>

<section class="section">
    <div class="container">
        <div class="dash-header">
            <div><h1>Notifications</h1><p>Stay updated with all your activities</p></div>
            <form method="post" action="<?= url('app/actions/notifications_mark_all.php'); ?>">
                <button class="btn btn-outline btn-sm" type="submit">Mark all as read</button>
            </form>
        </div>

        <div class="filter-pills">
            <a class="pill <?= $filter === 'all' ? 'active' : ''; ?>" href="<?= url('pages/notifications.php?filter=all'); ?>">All <span><?= $counts['all']; ?></span></a>
            <a class="pill <?= $filter === 'unread' ? 'active' : ''; ?>" href="<?= url('pages/notifications.php?filter=unread'); ?>">Unread <span><?= $counts['unread']; ?></span></a>
            <a class="pill <?= $filter === 'order' ? 'active' : ''; ?>" href="<?= url('pages/notifications.php?filter=order'); ?>">Orders <span><?= $counts['order']; ?></span></a>
            <a class="pill <?= $filter === 'loan' ? 'active' : ''; ?>" href="<?= url('pages/notifications.php?filter=loan'); ?>">Loans <span><?= $counts['loan']; ?></span></a>
            <a class="pill <?= $filter === 'price' ? 'active' : ''; ?>" href="<?= url('pages/notifications.php?filter=price'); ?>">Market <span><?= $counts['price']; ?></span></a>
        </div>

        <div class="notification-list">
            <?php if (!$notifications): ?>
                <div class="card" style="text-align:center">No notifications in this category.</div>
            <?php else: ?>
                <?php foreach ($notifications as $n): ?>
                    <div class="notification-card <?= $n['is_read'] ? '' : 'unread'; ?>">
                        <div class="notification-icon"><i class="fas fa-bell"></i></div>
                        <div class="notification-content">
                            <h4><?= htmlspecialchars($n['title']); ?></h4>
                            <p><?= htmlspecialchars($n['message']); ?></p>
                            <span><?= date('M j, Y g:i A', strtotime($n['created_at'])); ?></span>
                        </div>
                        <form method="post" action="<?= url('app/actions/notification_delete.php'); ?>">
                            <input type="hidden" name="notification_id" value="<?= (int)$n['id']; ?>">
                            <button class="icon-btn" type="submit" aria-label="Delete"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="card" style="margin-top:2rem">
            <h3>Notification Settings</h3>
            <div class="settings-list">
                <label><span>Email notifications</span><input type="checkbox" checked></label>
                <label><span>SMS notifications</span><input type="checkbox" checked></label>
                <label><span>Push notifications</span><input type="checkbox"></label>
                <label><span>Marketing updates</span><input type="checkbox"></label>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../app/includes/footer.php'; ?>
</body>
</html>
