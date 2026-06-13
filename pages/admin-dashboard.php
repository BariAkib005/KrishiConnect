<?php
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/includes/helpers.php';
require_once __DIR__ . '/../app/includes/db.php';
$user = require_role('admin');
$name = $user['full_name'] ?? 'Admin';
$pdo = db();

// --- Headline counts (all real) ---
$totalUsers     = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$newUsersWeek   = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)')->fetchColumn();
$farmers        = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'farmer'")->fetchColumn();
$buyers         = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'buyer'")->fetchColumn();
$others         = max(0, $totalUsers - $farmers - $buyers);
$activeListings = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active' AND product_status = 'approved'")->fetchColumn();
$pendingProducts = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE product_status = 'pending'")->fetchColumn();
$openDisputes   = (int)$pdo->query("SELECT COUNT(*) FROM disputes WHERE status = 'open'")->fetchColumn();
$inReviewDisputes = (int)$pdo->query("SELECT COUNT(*) FROM disputes WHERE status = 'in_review'")->fetchColumn();
$totalDisbursed = (float)$pdo->query("SELECT COALESCE(SUM(approved_amount), 0) FROM loans WHERE status IN ('active', 'closed')")->fetchColumn();
$activeLoans    = (int)$pdo->query("SELECT COUNT(*) FROM loans WHERE status = 'active'")->fetchColumn();
$closedLoans    = (int)$pdo->query("SELECT COUNT(*) FROM loans WHERE status = 'closed'")->fetchColumn();

$rep = $pdo->query(
    "SELECT COALESCE(SUM(status = 'paid'), 0) AS paid,
            COALESCE(SUM(status = 'late'), 0) AS late,
            COALESCE(SUM(status = 'due'), 0) AS due,
            COALESCE(SUM(status = 'paid' AND paid_at IS NOT NULL AND paid_at <= due_date), 0) AS ontime
     FROM loan_payments"
)->fetch();
$settled   = (int)$rep['paid'] + (int)$rep['late'];
$onTimePct = $settled > 0 ? (int)round(100 * (int)$rep['ontime'] / $settled) : 100;

// Pie proportions (real).
$pct = static fn(int $part, int $total): int => $total > 0 ? (int)round(100 * $part / $total) : 0;
$fPct = $pct($farmers, $totalUsers);
$bPct = $pct($buyers, $totalUsers);
$loanCount = $activeLoans + $closedLoans;
$activePct = $pct($activeLoans, $loanCount);
$totalEmi = (int)$rep['paid'] + (int)$rep['late'] + (int)$rep['due'];
$paidPct = $pct((int)$rep['paid'], $totalEmi);
$latePct = $pct((int)$rep['late'], $totalEmi);

$fmtBdt = static function (float $v): string {
    if ($v >= 10000000) return number_format($v / 10000000, 2) . ' Cr';
    if ($v >= 100000)   return number_format($v / 100000, 2) . ' L';
    if ($v >= 1000)     return number_format($v / 1000, 1) . 'K';
    return number_format($v);
};

// --- Recent activity (newest registrations, listings, orders) ---
$activities = [];
foreach ($pdo->query("SELECT full_name, role, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll() as $r) {
    $activities[] = ['time' => $r['created_at'], 'user' => $r['full_name'], 'role' => $r['role'], 'type' => 'Registration', 'details' => 'New ' . $r['role'] . ' account'];
}
foreach ($pdo->query("SELECT p.name, p.created_at, u.full_name, u.role FROM products p JOIN users u ON u.id = p.farmer_id ORDER BY p.created_at DESC LIMIT 5")->fetchAll() as $r) {
    $activities[] = ['time' => $r['created_at'], 'user' => $r['full_name'], 'role' => $r['role'], 'type' => 'New Listing', 'details' => 'Listed ' . $r['name']];
}
foreach ($pdo->query("SELECT o.total_amount, o.placed_at, u.full_name, u.role FROM orders o JOIN users u ON u.id = o.buyer_id ORDER BY o.placed_at DESC LIMIT 5")->fetchAll() as $r) {
    $activities[] = ['time' => $r['placed_at'], 'user' => $r['full_name'], 'role' => $r['role'], 'type' => 'New Order', 'details' => 'Ordered BDT ' . number_format((float)$r['total_amount'], 0) . ' of produce'];
}
usort($activities, static fn(array $a, array $b): int => strcmp((string)$b['time'], (string)$a['time']));
$activities = array_slice($activities, 0, 6);

$roleBadge = static function (string $role): string {
    return match ($role) {
        'farmer' => 'badge-success',
        'buyer' => 'badge-info',
        'finance' => 'badge-warning',
        default => 'badge-info',
    };
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — KrishiConnect</title>
    <link rel="stylesheet" href="<?= asset_url('css/styles.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="<?= url('index.php'); ?>" class="logo">
                <span class="brand-mark"><i class="fas fa-seedling"></i></span>
                <span>KrishiConnect</span>
            </a>
        </div>
        <nav class="sidebar-nav">
            <a href="<?= url('pages/admin-dashboard.php'); ?>" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="<?= url('pages/user-moderation.php'); ?>"><i class="fas fa-users"></i> User Management</a>
            <a href="<?= url('admin/loan_management.php'); ?>"><i class="fas fa-hand-holding-usd"></i> Loan Management</a>
            <a href="<?= url('pages/product-approval.php'); ?>"><i class="fas fa-clipboard-check"></i> Product Approval</a>
            <a href="<?= url('pages/marketplace.php'); ?>"><i class="fas fa-store"></i> Marketplace</a>
            <a href="<?= url('pages/reporting.php'); ?>"><i class="fas fa-chart-bar"></i> Reports</a>
            <a href="<?= url('pages/settings.php'); ?>"><i class="fas fa-cog"></i> Settings</a>
            <a href="<?= url('app/actions/logout.php'); ?>" style="margin-top:2rem;opacity:.6"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <main class="main-content">
        <div class="dash-header">
            <div><h1>Admin Dashboard</h1><p>System performance overview and management</p></div>
            <div class="meta"><i class="fas fa-shield-alt" style="color:var(--gold)"></i> Admin: <?= htmlspecialchars($name); ?> · <?= date('M j, Y · g:i A'); ?></div>
        </div>

        <div class="dash-cards">
            <div class="dash-card">
                <div class="info"><p>Total Users</p><h3><?= number_format($totalUsers); ?></h3><div class="sub">+<?= number_format($newUsersWeek); ?> this week</div></div>
                <div class="icon green"><i class="fas fa-users"></i></div>
            </div>
            <div class="dash-card">
                <div class="info"><p>Active Listings</p><h3><?= number_format($activeListings); ?></h3><div class="sub"><?= number_format($pendingProducts); ?> pending approval</div></div>
                <div class="icon gold"><i class="fas fa-shopping-basket"></i></div>
            </div>
            <div class="dash-card">
                <div class="info"><p>Open Disputes</p><h3><?= number_format($openDisputes); ?></h3><div class="sub"><?= number_format($inReviewDisputes); ?> in review</div></div>
                <div class="icon navy"><i class="fas fa-exclamation-triangle"></i></div>
            </div>
            <div class="dash-card">
                <div class="info"><p>Active Loans</p><h3><?= number_format($activeLoans); ?></h3><div class="sub">BDT <?= $fmtBdt($totalDisbursed); ?> disbursed</div></div>
                <div class="icon red"><i class="fas fa-hand-holding-usd"></i></div>
            </div>
        </div>

        <div class="table-wrap">
            <h2><i class="fas fa-stream" style="color:var(--emerald);margin-right:8px"></i> Recent Activity</h2>
            <table>
                <thead><tr><th>When</th><th>User</th><th>Role</th><th>Type</th><th>Details</th></tr></thead>
                <tbody>
                    <?php if (!$activities): ?>
                        <tr><td colspan="5" class="empty-cell">No activity yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($activities as $a): ?>
                        <tr>
                            <td><?= date('M j, g:i A', strtotime((string)$a['time'])); ?></td>
                            <td><?= htmlspecialchars($a['user']); ?></td>
                            <td><span class="badge-status <?= $roleBadge($a['role']); ?>"><?= htmlspecialchars(ucfirst($a['role'])); ?></span></td>
                            <td><?= htmlspecialchars($a['type']); ?></td>
                            <td><?= htmlspecialchars($a['details']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <h2 style="font-size:1.2rem;margin:1.5rem 0 1rem"><i class="fas fa-chart-pie" style="color:var(--emerald);margin-right:8px"></i> Analytics Overview</h2>
        <div class="dash-cards">
            <div class="card" style="text-align:center">
                <div style="width:120px;height:120px;border-radius:50%;background:conic-gradient(var(--emerald) 0% <?= $fPct; ?>%,var(--gold) <?= $fPct; ?>% <?= $fPct + $bPct; ?>%,var(--navy) <?= $fPct + $bPct; ?>% 100%);margin:0 auto 1rem;display:flex;align-items:center;justify-content:center"><div style="width:80px;height:80px;border-radius:50%;background:var(--white);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.1rem"><?= number_format($totalUsers); ?></div></div>
                <h3 style="font-size:1rem">User Distribution</h3>
                <p style="color:var(--gray);font-size:.8rem;margin-top:.25rem"><?= $fPct; ?>% Farmers · <?= $bPct; ?>% Buyers · <?= max(0, 100 - $fPct - $bPct); ?>% Other</p>
            </div>
            <div class="card" style="text-align:center">
                <div style="width:120px;height:120px;border-radius:50%;background:conic-gradient(var(--emerald) 0% <?= $activePct; ?>%,var(--gold) <?= $activePct; ?>% 100%);margin:0 auto 1rem;display:flex;align-items:center;justify-content:center"><div style="width:80px;height:80px;border-radius:50%;background:var(--white);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1rem">৳<?= $fmtBdt($totalDisbursed); ?></div></div>
                <h3 style="font-size:1rem">Loans Disbursed</h3>
                <p style="color:var(--gray);font-size:.8rem;margin-top:.25rem"><?= number_format($activeLoans); ?> active · <?= number_format($closedLoans); ?> closed</p>
            </div>
            <div class="card" style="text-align:center">
                <div style="width:120px;height:120px;border-radius:50%;background:conic-gradient(var(--emerald) 0% <?= $paidPct; ?>%,var(--gold) <?= $paidPct; ?>% <?= 100 - $latePct; ?>%,var(--red) <?= 100 - $latePct; ?>% 100%);margin:0 auto 1rem;display:flex;align-items:center;justify-content:center"><div style="width:80px;height:80px;border-radius:50%;background:var(--white);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.1rem"><?= $onTimePct; ?>%</div></div>
                <h3 style="font-size:1rem">On-time Repayment</h3>
                <p style="color:var(--gray);font-size:.8rem;margin-top:.25rem"><?= number_format((int)$rep['paid']); ?> paid · <?= number_format((int)$rep['due']); ?> due · <?= number_format((int)$rep['late']); ?> late</p>
            </div>
        </div>
    </main>
</div>

</body>
</html>
