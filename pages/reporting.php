<?php
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/includes/helpers.php';
require_once __DIR__ . '/../app/includes/db.php';

require_role('admin');
$pdo = db();

$totalRevenue   = (float)$pdo->query("SELECT COALESCE(SUM(oi.quantity * oi.unit_price), 0) FROM order_items oi")->fetchColumn();
$totalOrders    = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$activeUsers    = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
$loansDisbursed = (float)$pdo->query("SELECT COALESCE(SUM(approved_amount), 0) FROM loans WHERE status IN ('active', 'closed')")->fetchColumn();

// Revenue per month (most recent six, shown chronologically).
$monthly = $pdo->query(
    "SELECT DATE_FORMAT(o.placed_at, '%b') AS label,
            DATE_FORMAT(o.placed_at, '%Y-%m') AS ym,
            COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS revenue
     FROM orders o
     JOIN order_items oi ON oi.order_id = o.id
     GROUP BY ym, label
     ORDER BY ym DESC
     LIMIT 6"
)->fetchAll();
$monthly = array_reverse($monthly);
$maxRevenue = 0.0;
foreach ($monthly as $m) {
    $maxRevenue = max($maxRevenue, (float)$m['revenue']);
}

$topProducts = $pdo->query(
    "SELECT p.name, COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS revenue
     FROM order_items oi
     JOIN products p ON p.id = oi.product_id
     GROUP BY p.id, p.name
     ORDER BY revenue DESC
     LIMIT 5"
)->fetchAll();

$topFarmers = $pdo->query(
    "SELECT u.full_name, COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS revenue
     FROM order_items oi
     JOIN products p ON p.id = oi.product_id
     JOIN users u ON u.id = p.farmer_id
     GROUP BY u.id, u.full_name
     ORDER BY revenue DESC
     LIMIT 5"
)->fetchAll();

$fmtBdt = static function (float $v): string {
    if ($v >= 10000000) return number_format($v / 10000000, 2) . ' Cr';
    if ($v >= 100000)   return number_format($v / 100000, 2) . ' L';
    if ($v >= 1000)     return number_format($v / 1000, 1) . 'K';
    return number_format($v);
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics — KrishiConnect</title>
    <link rel="stylesheet" href="<?= asset_url('css/styles.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<?php require __DIR__ . '/../app/includes/header.php'; ?>

<section class="section">
    <div class="container">
        <div class="dash-header">
            <div><h1>Reports &amp; Analytics</h1><p>Comprehensive platform performance insights</p></div>
        </div>

        <div class="moderation-stats">
            <div class="stat-card"><div class="stat-icon green"><i class="fas fa-wallet"></i></div><div class="stat-value">৳<?= $fmtBdt($totalRevenue); ?></div><div class="stat-label">Total Revenue</div></div>
            <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-box"></i></div><div class="stat-value"><?= number_format($totalOrders); ?></div><div class="stat-label">Total Orders</div></div>
            <div class="stat-card"><div class="stat-icon green"><i class="fas fa-users"></i></div><div class="stat-value"><?= number_format($activeUsers); ?></div><div class="stat-label">Active Users</div></div>
            <div class="stat-card"><div class="stat-icon orange"><i class="fas fa-hand-holding-usd"></i></div><div class="stat-value">৳<?= $fmtBdt($loansDisbursed); ?></div><div class="stat-label">Loans Disbursed</div></div>
        </div>

        <div class="card" style="margin-top:2rem">
            <h2>Revenue Breakdown</h2>
            <div class="revenue-bars">
                <?php if (!$monthly): ?>
                    <p style="color:var(--gray)">No revenue recorded yet — it will appear here once buyers place orders.</p>
                <?php else: ?>
                    <?php foreach ($monthly as $m): ?>
                        <?php $width = $maxRevenue > 0 ? (int)round(100 * (float)$m['revenue'] / $maxRevenue) : 0; ?>
                        <div class="bar-row"><span><?= htmlspecialchars($m['label']); ?></span><div class="bar"><div style="width:<?= $width; ?>%"></div></div><span>৳<?= $fmtBdt((float)$m['revenue']); ?></span></div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="cart-grid" style="margin-top:2rem">
            <div class="card">
                <h2>Top Products</h2>
                <ul class="rank-list">
                    <?php if (!$topProducts): ?>
                        <li><span class="muted">No sales yet</span></li>
                    <?php else: ?>
                        <?php foreach ($topProducts as $p): ?>
                            <li><span><?= htmlspecialchars($p['name']); ?></span><strong>৳<?= $fmtBdt((float)$p['revenue']); ?></strong></li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="card">
                <h2>Top Farmers</h2>
                <ul class="rank-list">
                    <?php if (!$topFarmers): ?>
                        <li><span class="muted">No sales yet</span></li>
                    <?php else: ?>
                        <?php foreach ($topFarmers as $f): ?>
                            <li><span><?= htmlspecialchars($f['full_name']); ?></span><strong>৳<?= $fmtBdt((float)$f['revenue']); ?></strong></li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../app/includes/footer.php'; ?>
</body>
</html>
