<?php
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/includes/helpers.php';
require_once __DIR__ . '/../app/includes/db.php';

$user = require_role('farmer');
$pdo = db();
$name = $user['full_name'] ?? 'Farmer';

// --- Farm profile summary ---
$profileStmt = $pdo->prepare('SELECT farm_name, location, land_area, soil_type FROM farmer_profiles WHERE user_id = ?');
$profileStmt->execute([(int)$user['id']]);
$profile = $profileStmt->fetch() ?: [];

// --- Product / inventory summary ---
$productStmt = $pdo->prepare(
    'SELECT p.id, p.name, p.status, p.product_status
     FROM products p WHERE p.farmer_id = ? ORDER BY p.created_at DESC'
);
$productStmt->execute([(int)$user['id']]);
$products = $productStmt->fetchAll();
$totalListed = count($products);
$activeListings = count(array_filter($products, static fn(array $p): bool => $p['status'] === 'active' && $p['product_status'] === 'approved'));

// --- Order / revenue summary ---
$ordersStmt = $pdo->prepare(
    'SELECT COUNT(DISTINCT o.id) AS order_count, COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS revenue
     FROM orders o
     JOIN order_items oi ON oi.order_id = o.id
     JOIN products p ON p.id = oi.product_id
     WHERE p.farmer_id = ?'
);
$ordersStmt->execute([(int)$user['id']]);
$orderStats = $ordersStmt->fetch() ?: ['order_count' => 0, 'revenue' => 0];

// --- Recent sales (real orders for this farmer's products) ---
$salesStmt = $pdo->prepare(
    'SELECT o.id AS order_id, o.placed_at, o.status, p.name AS product_name,
            oi.quantity, p.unit, oi.unit_price, b.full_name AS buyer_name
     FROM orders o
     JOIN order_items oi ON oi.order_id = o.id
     JOIN products p ON p.id = oi.product_id
     JOIN users b ON b.id = o.buyer_id
     WHERE p.farmer_id = ?
     ORDER BY o.placed_at DESC
     LIMIT 5'
);
$salesStmt->execute([(int)$user['id']]);
$recentSales = $salesStmt->fetchAll();

// --- Loan due summary ---
$totalDue = farmer_total_due((int)$user['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmer Dashboard - KrishiConnect</title>
    <link rel="stylesheet" href="<?= asset_url('css/styles.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<div class="dashboard-layout">
    <?php $active = 'dashboard'; require __DIR__ . '/../app/includes/farmer_sidebar.php'; ?>

    <main class="main-content">
        <div class="dash-header">
            <div>
                <h1>Welcome, <?= htmlspecialchars($name); ?></h1>
                <p>Here is a quick snapshot of your farm, sales, and market outlook.</p>
            </div>
            <a href="<?= url('pages/manage_products.php#add'); ?>" class="btn btn-accent"><i class="fas fa-plus"></i> Add Produce</a>
        </div>

        <!-- Summary cards -->
        <div class="dash-cards">
            <div class="dash-card">
                <div class="info"><p>Total Crops Listed</p><h3><?= number_format($totalListed); ?></h3><div class="sub"><?= number_format($activeListings); ?> live in marketplace</div></div>
                <div class="icon green"><i class="fas fa-seedling"></i></div>
            </div>
            <div class="dash-card">
                <div class="info"><p>Orders Received</p><h3><?= number_format((int)$orderStats['order_count']); ?></h3><div class="sub">From marketplace buyers</div></div>
                <div class="icon gold"><i class="fas fa-shopping-cart"></i></div>
            </div>
            <div class="dash-card">
                <div class="info"><p>Total Revenue</p><h3>BDT <?= number_format((float)$orderStats['revenue'], 0); ?></h3><div class="sub">From completed order items</div></div>
                <div class="icon navy"><i class="fas fa-money-bill-wave"></i></div>
            </div>
            <div class="dash-card">
                <div class="info"><p>Outstanding Loan Due</p><h3>BDT <?= number_format($totalDue, 0); ?></h3><div class="sub"><a href="<?= url('pages/repayment_ledger.php'); ?>" style="color:var(--emerald);font-weight:600">Track repayments</a></div></div>
                <div class="icon red"><i class="fas fa-hand-holding-usd"></i></div>
            </div>
        </div>

        <!-- Farm profile summary -->
        <div class="card dashboard-section" style="margin-bottom:2rem">
            <div class="panel-heading" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem">
                <h2 style="font-size:1.2rem"><i class="fas fa-tractor" style="color:var(--emerald);margin-right:8px"></i> Farm Profile</h2>
                <a href="<?= url('pages/farmer_profile.php'); ?>" class="btn btn-outline btn-sm">View full profile</a>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1.25rem">
                <div><p style="font-size:.8rem;color:var(--gray);text-transform:uppercase;letter-spacing:.5px">Farm Name</p><p style="font-weight:600;font-size:1.1rem"><?= htmlspecialchars($profile['farm_name'] ?? ($name . "'s Farm")); ?></p></div>
                <div><p style="font-size:.8rem;color:var(--gray);text-transform:uppercase;letter-spacing:.5px">Farm Size</p><p style="font-weight:600;font-size:1.1rem"><?= $profile ? number_format((float)($profile['land_area'] ?? 0), 1) . ' Acres' : 'Not set'; ?></p></div>
                <div><p style="font-size:.8rem;color:var(--gray);text-transform:uppercase;letter-spacing:.5px">Location</p><p style="font-weight:600;font-size:1.1rem"><?= htmlspecialchars($profile['location'] ?? 'Not set'); ?></p></div>
                <div><p style="font-size:.8rem;color:var(--gray);text-transform:uppercase;letter-spacing:.5px">Soil Type</p><p style="font-weight:600;font-size:1.1rem"><?= htmlspecialchars($profile['soil_type'] ?? 'Not set'); ?></p></div>
            </div>
        </div>

        <!-- Recent sales -->
        <div class="table-wrap dashboard-section">
            <div class="panel-heading" style="display:flex;justify-content:space-between;align-items:center">
                <h2><i class="fas fa-receipt" style="color:var(--emerald);margin-right:8px"></i> Recent Sales</h2>
                <a href="<?= url('pages/order_history.php'); ?>" class="btn btn-outline btn-sm">All sales</a>
            </div>
            <table>
                <thead><tr><th>Order</th><th>Product</th><th>Qty</th><th>Buyer</th><th>Date</th><th>Amount</th><th>Status</th></tr></thead>
                <tbody>
                    <?php if (!$recentSales): ?>
                        <tr><td colspan="7" class="empty-cell">No sales yet. Approved products will sell here once buyers order them.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($recentSales as $sale): ?>
                        <tr>
                            <td>#KC<?= str_pad((string)$sale['order_id'], 5, '0', STR_PAD_LEFT); ?></td>
                            <td><?= htmlspecialchars($sale['product_name']); ?></td>
                            <td><?= number_format((float)$sale['quantity'], 0); ?> <?= htmlspecialchars($sale['unit']); ?></td>
                            <td><?= htmlspecialchars($sale['buyer_name']); ?></td>
                            <td><?= date('M j, Y', strtotime($sale['placed_at'])); ?></td>
                            <td>BDT <?= number_format((float)$sale['unit_price'] * (float)$sale['quantity'], 0); ?></td>
                            <td><span class="badge-status <?= in_array($sale['status'], ['delivered', 'confirmed'], true) ? 'badge-success' : 'badge-warning'; ?>"><?= htmlspecialchars(ucfirst($sale['status'])); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Market insights -->
        <h2 class="dashboard-section" style="font-size:1.2rem;margin:1.5rem 0 1rem"><i class="fas fa-lightbulb" style="color:var(--gold);margin-right:8px"></i> Market Insights</h2>
        <div class="dash-cards">
            <div class="card"><h3 style="font-size:1rem;margin-bottom:.5rem"><i class="fas fa-arrow-trend-up" style="color:var(--emerald)"></i> Price Trends</h3><p style="color:var(--gray);font-size:.9rem">Potato and onion prices are rising this month — good time to list stock.</p></div>
            <div class="card"><h3 style="font-size:1rem;margin-bottom:.5rem"><i class="fas fa-cloud-sun-rain" style="color:var(--gold)"></i> Seasonal Forecast</h3><p style="color:var(--gray);font-size:.9rem">Prepare monsoon crops by June. Ensure drainage for low-lying plots.</p></div>
            <a class="card dash-card-link" href="<?= url('pages/smart_agri.php'); ?>"><h3 style="font-size:1rem;margin-bottom:.5rem"><i class="fas fa-seedling" style="color:var(--navy)"></i> Smart Agri</h3><p style="color:var(--gray);font-size:.9rem">View weather alerts and AI crop recommendations &rarr;</p></a>
        </div>
    </main>
</div>

</body>
</html>
