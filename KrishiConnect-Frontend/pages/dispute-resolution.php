<?php
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/includes/helpers.php';
require_once __DIR__ . '/../app/includes/db.php';

require_login();
$user = current_user();
if (($user['role'] ?? '') !== 'admin') {
    redirect('pages/admin-dashboard.php');
}

$status = $_GET['status'] ?? 'open';
$pdo = db();

$stmt = $pdo->prepare(
    'SELECT d.id, d.status, d.description, d.created_at, d.resolution, o.id AS order_id, o.total_amount,
            u.full_name AS complainant
     FROM disputes d
     JOIN orders o ON o.id = d.order_id
     JOIN users u ON u.id = d.opened_by
     WHERE d.status = ?
     ORDER BY d.created_at DESC'
);
$stmt->execute([$status]);
$disputes = $stmt->fetchAll();

$countStmt = $pdo->query('SELECT status, COUNT(*) AS total FROM disputes GROUP BY status');
$counts = ['open' => 0, 'in_review' => 0, 'resolved' => 0];
foreach ($countStmt->fetchAll() as $row) {
    $counts[$row['status']] = (int)$row['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dispute Resolution — KrishiConnect</title>
    <link rel="stylesheet" href="<?= asset_url('css/styles.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<?php require __DIR__ . '/../app/includes/header.php'; ?>

<section class="section">
    <div class="container">
        <div class="dash-header">
            <div><h1>Dispute Resolution</h1><p>Manage and resolve disputes between users</p></div>
        </div>

        <div class="tabs">
            <a class="tab <?= $status === 'open' ? 'active' : ''; ?>" href="<?= url('pages/dispute-resolution.php?status=open'); ?>">Open (<?= $counts['open']; ?>)</a>
            <a class="tab <?= $status === 'in_review' ? 'active' : ''; ?>" href="<?= url('pages/dispute-resolution.php?status=in_review'); ?>">In Review (<?= $counts['in_review']; ?>)</a>
            <a class="tab <?= $status === 'resolved' ? 'active' : ''; ?>" href="<?= url('pages/dispute-resolution.php?status=resolved'); ?>">Resolved (<?= $counts['resolved']; ?>)</a>
        </div>

        <div class="dispute-list">
            <?php if (!$disputes): ?>
                <div class="card" style="text-align:center">No disputes in this tab.</div>
            <?php else: ?>
                <?php foreach ($disputes as $d): ?>
                    <div class="card">
                        <div class="card-head">
                            <div>
                                <h3>Dispute #DS<?= (int)$d['id']; ?></h3>
                                <p>Order #KC<?= (int)$d['order_id']; ?> · <?= date('M j, Y', strtotime($d['created_at'])); ?></p>
                            </div>
                            <span class="badge-status <?= $d['status'] === 'resolved' ? 'badge-success' : ($d['status'] === 'in_review' ? 'badge-info' : 'badge-warning'); ?>">
                                <?= htmlspecialchars(str_replace('_', ' ', $d['status'])); ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <div><strong>Complainant:</strong> <?= htmlspecialchars($d['complainant']); ?></div>
                            <div><strong>Amount:</strong> ৳<?= number_format((float)$d['total_amount'], 0); ?></div>
                            <div><strong>Description:</strong> <?= htmlspecialchars($d['description']); ?></div>
                        </div>
                        <?php if ($d['status'] !== 'resolved'): ?>
                            <div class="card-actions">
                                <?php if ($d['status'] === 'open'): ?>
                                    <form method="post" action="<?= url('app/actions/dispute_update.php'); ?>">
                                        <input type="hidden" name="dispute_id" value="<?= (int)$d['id']; ?>">
                                        <input type="hidden" name="status" value="in_review">
                                        <button class="btn btn-outline btn-sm" type="submit">Start Review</button>
                                    </form>
                                <?php endif; ?>
                                <form method="post" action="<?= url('app/actions/dispute_update.php'); ?>">
                                    <input type="hidden" name="dispute_id" value="<?= (int)$d['id']; ?>">
                                    <input type="hidden" name="status" value="resolved">
                                    <button class="btn btn-primary btn-sm" type="submit">Mark Resolved</button>
                                </form>
                            </div>
                        <?php else: ?>
                            <?php if (!empty($d['resolution'])): ?>
                                <div class="resolution-note">Resolution: <?= htmlspecialchars($d['resolution']); ?></div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../app/includes/footer.php'; ?>
</body>
</html>
