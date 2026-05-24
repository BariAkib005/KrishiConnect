<?php
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/includes/helpers.php';
require_once __DIR__ . '/../app/includes/db.php';

$user = require_role('finance');
$tab = $_GET['tab'] ?? 'pending';

$pdo = db();
$stmt = $pdo->prepare(
    'SELECT la.*, u.full_name AS farmer_name
     FROM loan_applications la
     JOIN users u ON u.id = la.farmer_id
     WHERE la.status = ?
     ORDER BY la.submitted_at DESC'
);
$stmt->execute([$tab]);
$applications = $stmt->fetchAll();

$countStmt = $pdo->query('SELECT status, COUNT(*) AS total FROM loan_applications GROUP BY status');
$counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
foreach ($countStmt->fetchAll() as $row) {
    $counts[$row['status']] = (int)$row['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Management - KrishiConnect</title>
    <link rel="stylesheet" href="<?= asset_url('css/styles.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<?php require __DIR__ . '/../app/includes/header.php'; ?>

<section class="section">
    <div class="container">
        <div class="dash-header">
            <div><h1>Loan Management</h1><p>Review and process loan applications</p></div>
        </div>

        <div class="tabs">
            <a class="tab <?= $tab === 'pending' ? 'active' : ''; ?>" href="<?= url('pages/loan-management.php?tab=pending'); ?>">Pending (<?= $counts['pending']; ?>)</a>
            <a class="tab <?= $tab === 'approved' ? 'active' : ''; ?>" href="<?= url('pages/loan-management.php?tab=approved'); ?>">Approved (<?= $counts['approved']; ?>)</a>
            <a class="tab <?= $tab === 'rejected' ? 'active' : ''; ?>" href="<?= url('pages/loan-management.php?tab=rejected'); ?>">Rejected (<?= $counts['rejected']; ?>)</a>
        </div>

        <div class="loan-cards">
            <?php if (!$applications): ?>
                <div class="card" style="text-align:center">No applications in this tab.</div>
            <?php else: ?>
                <?php foreach ($applications as $app): ?>
                    <div class="card">
                        <div class="card-head">
                            <div>
                                <h3><?= htmlspecialchars($app['farmer_name']); ?></h3>
                                <p><?= htmlspecialchars($app['location'] ?? ''); ?> - <?= htmlspecialchars($app['farm_size'] ?? ''); ?></p>
                            </div>
                            <span class="badge-status <?= $app['risk_level'] === 'high' ? 'badge-danger' : ($app['risk_level'] === 'low' ? 'badge-success' : 'badge-warning'); ?>">
                                <?= htmlspecialchars($app['risk_level']); ?> risk
                            </span>
                        </div>
                        <div class="card-body">
                            <div><strong>Amount:</strong> BDT <?= number_format((float)$app['requested_amount'], 0); ?></div>
                            <div><strong>Tenure:</strong> <?= (int)($app['tenure_months'] ?? 0); ?> months</div>
                            <div><strong>Purpose:</strong> <?= htmlspecialchars($app['purpose'] ?? ''); ?></div>
                            <div><strong>Submitted:</strong> <?= date('M j, Y', strtotime($app['submitted_at'])); ?></div>
                        </div>
                        <?php if ($tab === 'pending'): ?>
                            <div class="card-actions">
                                <form method="post" action="<?= url('app/actions/loan_review.php'); ?>">
                                    <input type="hidden" name="application_id" value="<?= (int)$app['id']; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button class="btn btn-primary btn-sm" type="submit">Approve</button>
                                </form>
                                <form method="post" action="<?= url('app/actions/loan_review.php'); ?>">
                                    <input type="hidden" name="application_id" value="<?= (int)$app['id']; ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button class="btn btn-outline btn-sm" type="submit" style="color:var(--red);border-color:var(--red)">Reject</button>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="card-actions">
                                <span class="badge-status <?= $tab === 'approved' ? 'badge-success' : 'badge-danger'; ?>">
                                    <?= ucfirst($tab); ?>
                                </span>
                            </div>
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

