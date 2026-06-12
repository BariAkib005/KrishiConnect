<?php
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/includes/helpers.php';
require_once __DIR__ . '/../app/includes/db.php';

$user = require_role('admin');
$pdo = db();

$flash = $_GET['status'] ?? '';
$requestedFilter = $_GET['filter'] ?? 'pending';
$filter = in_array($requestedFilter, ['pending', 'approved', 'rejected'], true) ? $requestedFilter : 'pending';

$stmt = $pdo->prepare(
    'SELECT p.*, c.name AS category_name, u.full_name AS farmer_name,
        (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS image_path
     FROM products p
     JOIN categories c ON c.id = p.category_id
     JOIN users u ON u.id = p.farmer_id
     WHERE p.product_status = ?
     ORDER BY p.created_at DESC'
);
$stmt->execute([$filter]);
$products = $stmt->fetchAll();

$counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
foreach ($pdo->query('SELECT product_status, COUNT(*) AS total FROM products GROUP BY product_status')->fetchAll() as $row) {
    $counts[$row['product_status']] = (int)$row['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Approval — KrishiConnect</title>
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
            <a href="<?= url('pages/admin-dashboard.php'); ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="<?= url('pages/user-moderation.php'); ?>"><i class="fas fa-users"></i> User Management</a>
            <a href="<?= url('admin/loan_management.php'); ?>"><i class="fas fa-hand-holding-usd"></i> Loan Management</a>
            <a href="<?= url('pages/product-approval.php'); ?>" class="active"><i class="fas fa-clipboard-check"></i> Product Approval</a>
            <a href="<?= url('pages/marketplace.php'); ?>"><i class="fas fa-store"></i> Marketplace</a>
            <a href="<?= url('pages/reporting.php'); ?>"><i class="fas fa-chart-bar"></i> Reports</a>
            <a href="<?= url('app/actions/logout.php'); ?>" style="margin-top:2rem;opacity:.6"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <main class="main-content">
        <div class="dash-header">
            <div><h1>Product Approval</h1><p>Review farmer submissions before they go live in the marketplace.</p></div>
        </div>

        <?php if ($flash === 'approved'): ?>
            <div class="notice success"><i class="fas fa-check-circle"></i> Product approved and published to the marketplace.</div>
        <?php elseif ($flash === 'rejected'): ?>
            <div class="notice error"><i class="fas fa-ban"></i> Product rejected and kept out of the marketplace.</div>
        <?php elseif ($flash === 'invalid'): ?>
            <div class="notice error"><i class="fas fa-circle-exclamation"></i> Could not process that product.</div>
        <?php endif; ?>

        <div class="tabs">
            <a class="tab <?= $filter === 'pending' ? 'active' : ''; ?>" href="<?= url('pages/product-approval.php?filter=pending'); ?>">Pending (<?= $counts['pending']; ?>)</a>
            <a class="tab <?= $filter === 'approved' ? 'active' : ''; ?>" href="<?= url('pages/product-approval.php?filter=approved'); ?>">Approved (<?= $counts['approved']; ?>)</a>
            <a class="tab <?= $filter === 'rejected' ? 'active' : ''; ?>" href="<?= url('pages/product-approval.php?filter=rejected'); ?>">Rejected (<?= $counts['rejected']; ?>)</a>
        </div>

        <div class="table-wrap">
            <h2><i class="fas fa-clipboard-check" style="color:var(--emerald);margin-right:8px"></i> <?= ucfirst($filter); ?> Products</h2>
            <table>
                <thead><tr><th>Product</th><th>Farmer</th><th>Category</th><th>Price</th><th>Stock</th><th>Submitted</th><th>Action</th></tr></thead>
                <tbody>
                    <?php if (!$products): ?>
                        <tr><td colspan="7" class="empty-cell">No <?= htmlspecialchars($filter); ?> products.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <div class="inventory-product">
                                    <img src="<?= product_image_src($product); ?>" alt="<?= htmlspecialchars($product['name']); ?>">
                                    <div>
                                        <strong><?= htmlspecialchars($product['name']); ?></strong>
                                        <span><?= htmlspecialchars($product['variety'] ?: 'Standard'); ?></span>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($product['farmer_name']); ?></td>
                            <td><?= htmlspecialchars($product['category_name']); ?></td>
                            <td>BDT <?= number_format((float)$product['price'], 2); ?>/<?= htmlspecialchars($product['unit']); ?></td>
                            <td><?= number_format((float)$product['quantity_available'], 0); ?> <?= htmlspecialchars($product['unit']); ?></td>
                            <td><?= date('M j, Y', strtotime($product['created_at'])); ?></td>
                            <td>
                                <?php if ($filter === 'pending'): ?>
                                    <div style="display:flex;gap:6px">
                                        <form method="post" action="<?= url('app/actions/product_review.php'); ?>">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('product_review'), ENT_QUOTES); ?>">
                                            <input type="hidden" name="product_id" value="<?= (int)$product['id']; ?>">
                                            <input type="hidden" name="decision" value="approve">
                                            <button class="btn btn-primary btn-sm" type="submit">Approve</button>
                                        </form>
                                        <form method="post" action="<?= url('app/actions/product_review.php'); ?>">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('product_review'), ENT_QUOTES); ?>">
                                            <input type="hidden" name="product_id" value="<?= (int)$product['id']; ?>">
                                            <input type="hidden" name="decision" value="reject">
                                            <button class="btn btn-outline btn-sm" type="submit" style="color:var(--red);border-color:var(--red)">Reject</button>
                                        </form>
                                    </div>
                                <?php elseif ($filter === 'rejected'): ?>
                                    <form method="post" action="<?= url('app/actions/product_review.php'); ?>">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('product_review'), ENT_QUOTES); ?>">
                                        <input type="hidden" name="product_id" value="<?= (int)$product['id']; ?>">
                                        <input type="hidden" name="decision" value="approve">
                                        <button class="btn btn-outline btn-sm" type="submit">Re-approve</button>
                                    </form>
                                <?php else: ?>
                                    <span class="badge-status badge-success">Live</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

</body>
</html>
