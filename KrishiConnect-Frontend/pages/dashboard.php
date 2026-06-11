<?php
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/includes/helpers.php';
require_once __DIR__ . '/../app/includes/db.php';

require_login();

$user = current_user();
if (($user['role'] ?? '') !== 'farmer') {
    $destinations = [
        'buyer' => 'pages/buyer-dashboard.php',
        'finance' => 'pages/finance-dashboard.php',
        'admin' => 'pages/admin-dashboard.php',
    ];
    redirect($destinations[$user['role'] ?? ''] ?? 'index.php');
}

$pdo = db();
$name = $user['full_name'] ?? 'Farmer';
$productCreated = ($_GET['product_created'] ?? '') === '1';
$productError = $_GET['product_error'] ?? '';

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();

$productStmt = $pdo->prepare(
    'SELECT p.*, c.name AS category_name,
        (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS image_path
     FROM products p
     JOIN categories c ON c.id = p.category_id
     WHERE p.farmer_id = ?
     ORDER BY p.created_at DESC'
);
$productStmt->execute([$user['id']]);
$products = $productStmt->fetchAll();

$totalListed = count($products);
$activeListings = count(array_filter($products, static fn(array $product): bool => $product['status'] === 'active'));

$ordersStmt = $pdo->prepare(
    'SELECT COUNT(DISTINCT o.id) AS order_count, COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS revenue
     FROM orders o
     JOIN order_items oi ON oi.order_id = o.id
     JOIN products p ON p.id = oi.product_id
     WHERE p.farmer_id = ?'
);
$ordersStmt->execute([$user['id']]);
$orderStats = $ordersStmt->fetch() ?: ['order_count' => 0, 'revenue' => 0];

$loanStmt = $pdo->prepare('SELECT COALESCE(SUM(principal), 0) AS balance FROM loans WHERE farmer_id = ? AND status IN ("pending", "active")');
$loanStmt->execute([$user['id']]);
$loanBalance = (float)($loanStmt->fetch()['balance'] ?? 0);

$errorMessages = [
    'invalid' => 'Please fill in product name, category, price, quantity, and unit correctly.',
    'category' => 'Please choose a valid category.',
    'image' => 'The image upload failed. Try again.',
    'image_size' => 'Product image must be 3MB or smaller.',
    'image_type' => 'Product image must be JPG, PNG, or WEBP.',
    'image_save' => 'Could not save the product image.',
    'save' => 'Could not save this product. Please try again.',
    'role' => 'Only farmer accounts can list produce.',
];
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
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="<?= url('index.php'); ?>" class="logo">
                <span class="brand-mark"><i class="fas fa-seedling"></i></span>
                <span>KrishiConnect</span>
            </a>
        </div>
        <nav class="sidebar-nav">
            <a href="#dashboard" class="active"><i class="fas fa-home"></i> Dashboard</a>
            <a href="#list-produce"><i class="fas fa-plus-circle"></i> List Produce</a>
            <a href="#inventory"><i class="fas fa-warehouse"></i> Inventory</a>
            <a href="#profile"><i class="fas fa-user"></i> My Profile</a>
            <a href="<?= url('pages/loan-application.php'); ?>"><i class="fas fa-file-signature"></i> Apply for Loan</a>
            <a href="<?= url('pages/loan-tracking.php'); ?>"><i class="fas fa-hand-holding-usd"></i> Loan Tracking</a>
            <a href="#sales"><i class="fas fa-chart-line"></i> Sales</a>
            <a href="#market-insights"><i class="fas fa-lightbulb"></i> Market Insights</a>
            <a href="<?= url('pages/settings.php'); ?>"><i class="fas fa-cog"></i> Settings</a>
            <a href="<?= url('app/actions/logout.php'); ?>" style="margin-top:2rem;opacity:.6"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <main class="main-content" id="dashboard">
        <div class="dash-header">
            <div>
                <h1>Welcome, <?= htmlspecialchars($name); ?></h1>
                <p>List produce, track inventory, and manage your farm sales.</p>
            </div>
            <a href="#list-produce" class="btn btn-accent"><i class="fas fa-plus"></i> Add Produce</a>
        </div>

        <?php if ($productCreated): ?>
            <div class="notice success"><i class="fas fa-check-circle"></i> Produce listed successfully and is now visible in the marketplace.</div>
        <?php elseif ($productError !== ''): ?>
            <div class="notice error"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($errorMessages[$productError] ?? 'Something went wrong.'); ?></div>
        <?php endif; ?>

        <div class="dash-cards">
            <div class="dash-card">
                <div class="info"><p>Total Crops Listed</p><h3><?= number_format($totalListed); ?></h3><div class="sub"><?= number_format($activeListings); ?> active listings</div></div>
                <div class="icon green"><i class="fas fa-seedling"></i></div>
            </div>
            <div class="dash-card">
                <div class="info"><p>Orders Received</p><h3><?= number_format((int)$orderStats['order_count']); ?></h3><div class="sub">From marketplace buyers</div></div>
                <div class="icon gold"><i class="fas fa-shopping-cart"></i></div>
            </div>
            <div class="dash-card">
                <div class="info"><p>Total Revenue</p><h3>BDT <?= number_format((float)$orderStats['revenue'], 0); ?></h3><div class="sub">Based on completed order items</div></div>
                <div class="icon navy"><i class="fas fa-money-bill-wave"></i></div>
            </div>
            <div class="dash-card">
                <div class="info"><p>Loan Balance</p><h3>BDT <?= number_format($loanBalance, 0); ?></h3><div class="sub"><a href="<?= url('pages/loan-application.php'); ?>" style="color:var(--emerald);font-weight:600">Apply for support</a></div></div>
                <div class="icon red"><i class="fas fa-leaf"></i></div>
            </div>
        </div>

        <section class="card dashboard-section farmer-loan-cta" id="loan-support">
            <div>
                <h2><i class="fas fa-file-signature"></i> Need seasonal capital?</h2>
                <p>Apply for crop, equipment, or farm development financing directly from your farmer dashboard.</p>
            </div>
            <div class="design-actions">
                <a href="<?= url('pages/loan-application.php'); ?>" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Apply for Loan</a>
                <a href="<?= url('pages/loan-tracking.php'); ?>" class="btn btn-outline"><i class="fas fa-chart-line"></i> Track Loans</a>
            </div>
        </section>

        <section class="card dashboard-section produce-panel" id="list-produce">
            <div class="panel-heading">
                <div>
                    <h2><i class="fas fa-basket-shopping"></i> List Your Produce</h2>
                    <p>Add fresh crops to the marketplace with price, stock, category, and an optional product photo.</p>
                </div>
                <span class="badge-status badge-success">Marketplace Ready</span>
            </div>

            <form method="post" action="<?= url('app/actions/product_create.php'); ?>" enctype="multipart/form-data" class="produce-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('product_create'), ENT_QUOTES); ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Produce Name</label>
                        <input type="text" id="name" name="name" placeholder="Tomato, Potato, Brinjal..." required>
                    </div>
                    <div class="form-group">
                        <label for="variety">Variety</label>
                        <input type="text" id="variety" name="variety" placeholder="Hybrid, Local, Organic">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Choose category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= (int)$category['id']; ?>"><?= htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="unit">Unit</label>
                        <select id="unit" name="unit" required>
                            <option value="kg">kg</option>
                            <option value="pc">pc</option>
                            <option value="bundle">bundle</option>
                            <option value="maund">maund</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Price per Unit (BDT)</label>
                        <input type="number" id="price" name="price" min="1" step="0.01" placeholder="60" required>
                    </div>
                    <div class="form-group">
                        <label for="quantity_available">Available Quantity</label>
                        <input type="number" id="quantity_available" name="quantity_available" min="1" step="0.01" placeholder="400" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" placeholder="Mention harvest date, quality, delivery notes, or organic practices."></textarea>
                </div>

                <div class="form-row align-end">
                    <div class="form-group">
                        <label for="product_image">Product Image</label>
                        <input type="file" id="product_image" name="product_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                        <p class="field-hint">Optional. JPG, PNG, or WEBP up to 3MB.</p>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-cloud-arrow-up"></i> Publish Produce</button>
                </div>
            </form>
        </section>

        <div class="card dashboard-section" id="profile" style="margin-bottom:2rem">
            <h2 style="font-size:1.2rem;margin-bottom:1.25rem"><i class="fas fa-tractor" style="color:var(--emerald);margin-right:8px"></i> Farm Profile</h2>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1.25rem">
                <div><p style="font-size:.8rem;color:var(--gray);text-transform:uppercase;letter-spacing:.5px">Farm Size</p><p style="font-weight:600;font-size:1.1rem">2.5 Acres</p></div>
                <div><p style="font-size:.8rem;color:var(--gray);text-transform:uppercase;letter-spacing:.5px">Location</p><p style="font-weight:600;font-size:1.1rem">Rangpur District</p></div>
                <div><p style="font-size:.8rem;color:var(--gray);text-transform:uppercase;letter-spacing:.5px">Main Crops</p><p style="font-weight:600;font-size:1.1rem"><?= $products ? htmlspecialchars(implode(', ', array_slice(array_column($products, 'name'), 0, 3))) : 'Not listed yet'; ?></p></div>
                <div><p style="font-size:.8rem;color:var(--gray);text-transform:uppercase;letter-spacing:.5px">Credit Score</p><p style="font-weight:600;font-size:1.1rem;color:var(--gold)"><i class="fas fa-star"></i> 4.5 / 5</p></div>
            </div>
        </div>

        <div class="table-wrap dashboard-section" id="inventory">
            <h2><i class="fas fa-warehouse" style="color:var(--emerald);margin-right:8px"></i> Inventory</h2>
            <table>
                <thead><tr><th>Crop</th><th>Category</th><th>Available</th><th>Unit Price</th><th>Status</th><th>Marketplace</th></tr></thead>
                <tbody>
                    <?php if (!$products): ?>
                        <tr><td colspan="6" class="empty-cell">No produce listed yet. Use the form above to publish your first crop.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <div class="inventory-product">
                                    <img src="<?= asset_url($product['image_path'] ?: 'images/vegetables/tomato.jpg'); ?>" alt="<?= htmlspecialchars($product['name']); ?>">
                                    <div>
                                        <strong><?= htmlspecialchars($product['name']); ?></strong>
                                        <span><?= htmlspecialchars($product['variety'] ?: 'Standard'); ?></span>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($product['category_name']); ?></td>
                            <td><?= number_format((float)$product['quantity_available'], 2); ?> <?= htmlspecialchars($product['unit']); ?></td>
                            <td>BDT <?= number_format((float)$product['price'], 2); ?>/<?= htmlspecialchars($product['unit']); ?></td>
                            <td><span class="badge-status <?= $product['product_status'] === 'approved' ? 'badge-success' : 'badge-warning'; ?>"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $product['product_status']))); ?></span></td>
                            <td><a href="<?= url('pages/product.php?id=' . (int)$product['id']); ?>" class="btn btn-outline btn-sm">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="table-wrap dashboard-section" id="sales">
            <h2><i class="fas fa-receipt" style="color:var(--emerald);margin-right:8px"></i> Recent Sales</h2>
            <table>
                <thead><tr><th>Order ID</th><th>Product</th><th>Qty</th><th>Buyer</th><th>Date</th><th>Amount</th><th>Status</th></tr></thead>
                <tbody>
                    <tr><td>#ORD-7865</td><td>Potato (Alu)</td><td>100 kg</td><td>Dhaka Food Market</td><td>10 May 2026</td><td>BDT 2,500</td><td><span class="badge-status badge-success">Completed</span></td></tr>
                    <tr><td>#ORD-7856</td><td>Brinjal (Begun)</td><td>50 kg</td><td>Green Grocery</td><td>8 May 2026</td><td>BDT 2,000</td><td><span class="badge-status badge-success">Completed</span></td></tr>
                    <tr><td>#ORD-7832</td><td>Tomato</td><td>75 kg</td><td>Fresh Mart</td><td>5 May 2026</td><td>BDT 4,500</td><td><span class="badge-status badge-warning">Shipping</span></td></tr>
                </tbody>
            </table>
        </div>

        <h2 class="dashboard-section" id="market-insights" style="font-size:1.2rem;margin:1.5rem 0 1rem"><i class="fas fa-lightbulb" style="color:var(--gold);margin-right:8px"></i> Market Insights</h2>
        <div class="dash-cards">
            <div class="card"><h3 style="font-size:1rem;margin-bottom:.5rem">Price Trends</h3><p style="color:var(--gray);font-size:.9rem">Potato prices are increasing this month.</p></div>
            <div class="card"><h3 style="font-size:1rem;margin-bottom:.5rem">Seasonal Forecast</h3><p style="color:var(--gray);font-size:.9rem">Prepare for monsoon season crops by June.</p></div>
            <div class="card"><h3 style="font-size:1rem;margin-bottom:.5rem">Demand Hotspots</h3><p style="color:var(--gray);font-size:.9rem">High demand for organic vegetables in Dhaka.</p></div>
        </div>
    </main>
</div>

<script>
document.querySelectorAll('.sidebar-nav a[href^="#"]').forEach((link) => {
    link.addEventListener('click', () => {
        document.querySelectorAll('.sidebar-nav a').forEach((item) => item.classList.remove('active'));
        link.classList.add('active');
    });
});
</script>

</body>
</html>
