<?php
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/includes/helpers.php';
require_once __DIR__ . '/../app/includes/db.php';

$user = require_role('finance');
$name = $user['full_name'] ?? 'Finance Officer';
$pdo = db();

$stats = $pdo->query(
    'SELECT
        SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) AS pending_reviews,
        SUM(CASE WHEN status = "approved" AND DATE(submitted_at) = CURDATE() THEN 1 ELSE 0 END) AS approved_today,
        SUM(CASE WHEN risk_level = "high" AND status = "pending" THEN 1 ELSE 0 END) AS flagged
     FROM loan_applications'
)->fetch() ?: ['pending_reviews' => 0, 'approved_today' => 0, 'flagged' => 0];

$disbursement = $pdo->query('SELECT COALESCE(SUM(approved_amount), 0) AS total FROM loans WHERE status IN ("active", "closed")')->fetch();
$pendingApps = $pdo->query(
    'SELECT la.*, u.full_name AS farmer_name
     FROM loan_applications la
     JOIN users u ON u.id = la.farmer_id
     WHERE la.status = "pending"
     ORDER BY la.submitted_at DESC
     LIMIT 5'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Dashboard - KrishiConnect</title>
    <link rel="stylesheet" href="<?= asset_url('css/styles.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<div class="dashboard-layout">
    <?php $active = 'dashboard'; require __DIR__ . '/../app/includes/finance_sidebar.php'; ?>

    <main class="main-content">
        <div class="dash-header">
            <div><h1>Loan Operations</h1><p>Review applications, monitor disbursements, and record repayments.</p></div>
            <div class="meta"><i class="fas fa-user"></i> <?= htmlspecialchars($name); ?></div>
        </div>

        <div class="dash-cards">
            <div class="dash-card">
                <div class="info"><p>Pending Reviews</p><h3><?= number_format((int)$stats['pending_reviews']); ?></h3><div class="sub">Loan applications awaiting review</div></div>
                <div class="icon gold"><i class="fas fa-clock"></i></div>
            </div>
            <div class="dash-card">
                <div class="info"><p>Approved Today</p><h3><?= number_format((int)$stats['approved_today']); ?></h3><div class="sub">Applications approved today</div></div>
                <div class="icon green"><i class="fas fa-check-circle"></i></div>
            </div>
            <div class="dash-card">
                <div class="info"><p>Total Disbursed</p><h3>BDT <?= number_format((float)$disbursement['total'], 0); ?></h3><div class="sub">Active and closed loans</div></div>
                <div class="icon navy"><i class="fas fa-money-bill-wave"></i></div>
            </div>
            <div class="dash-card">
                <div class="info"><p>Flagged</p><h3><?= number_format((int)$stats['flagged']); ?></h3><div class="sub">High risk pending files</div></div>
                <div class="icon red"><i class="fas fa-exclamation-triangle"></i></div>
            </div>
        </div>

        <div class="table-wrap">
            <h2><i class="fas fa-file-invoice" style="color:var(--emerald);margin-right:8px"></i> Applications Queue</h2>
            <table>
                <thead><tr><th>ID</th><th>Farmer</th><th>Amount</th><th>Purpose</th><th>Risk</th><th>Action</th></tr></thead>
                <tbody>
                    <?php if (!$pendingApps): ?>
                        <tr><td colspan="7" class="empty-cell">No pending loan applications.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($pendingApps as $app): ?>
                        <tr>
                            <td>LN<?= (int)$app['id']; ?></td>
                            <td><?= htmlspecialchars($app['farmer_name']); ?></td>
                            <td>BDT <?= number_format((float)$app['requested_amount'], 0); ?></td>
                            <td><?= htmlspecialchars($app['purpose']); ?></td>
                            <td><span class="badge-status <?= $app['risk_level'] === 'high' ? 'badge-danger' : ($app['risk_level'] === 'low' ? 'badge-success' : 'badge-warning'); ?>"><?= htmlspecialchars($app['risk_level']); ?></span></td>
                            <td><a href="<?= url('pages/loan-management.php'); ?>" class="btn btn-primary btn-sm">Review</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2 style="font-size:1.1rem;margin-bottom:1rem"><i class="fas fa-shield-alt" style="color:var(--gold);margin-right:8px"></i> Risk Signals</h2>
            <div class="dash-cards">
                <div class="card"><h3 style="font-size:1rem;margin-bottom:.5rem">High Exposure</h3><p style="color:var(--gray);font-size:.9rem"><?= number_format((int)$stats['flagged']); ?> pending applicants are currently marked high risk.</p></div>
                <div class="card"><h3 style="font-size:1rem;margin-bottom:.5rem">Repayment Register</h3><p style="color:var(--gray);font-size:.9rem">Use the disbursements page to mark paid, due, and late installments.</p></div>
                <div class="card"><h3 style="font-size:1rem;margin-bottom:.5rem">Due Follow-up</h3><p style="color:var(--gray);font-size:.9rem">Outstanding balances are grouped by farmer and loan.</p></div>
            </div>
        </div>
    </main>
</div>

</body>
</html>
