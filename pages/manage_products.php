<?php
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/includes/helpers.php';
require_once __DIR__ . '/../app/includes/db.php';

$user = require_role('farmer');
$pdo = db();

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
$productStmt->execute([(int)$user['id']]);
$products = $productStmt->fetchAll();

$totalListed = count($products);
$approved = count(array_filter($products, static fn(array $p): bool => $p['product_status'] === 'approved'));
$pending = count(array_filter($products, static fn(array $p): bool => $p['product_status'] === 'pending'));

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
    <title>Manage Your Products - KrishiConnect</title>
    <link rel="stylesheet" href="<?= asset_url('css/styles.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<div class="dashboard-layout">
    <?php $active = 'inventory'; require __DIR__ . '/../app/includes/farmer_sidebar.php'; ?>

    <main class="main-content">
        <div class="dash-header">
            <div>
                <h1>Manage Your Products</h1>
                <p>List new produce and track your existing marketplace listings.</p>
            </div>
            <a href="#add" class="btn btn-accent"><i class="fas fa-plus"></i> List Produce</a>
        </div>

        <?php if ($productCreated): ?>
            <div class="notice success"><i class="fas fa-check-circle"></i> Produce submitted! It will appear in the marketplace once an admin approves it.</div>
        <?php elseif ($productError !== ''): ?>
            <div class="notice error"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($errorMessages[$productError] ?? 'Something went wrong.'); ?></div>
        <?php endif; ?>

        <div class="dash-cards">
            <div class="dash-card">
                <div class="info"><p>Total Products</p><h3><?= number_format($totalListed); ?></h3><div class="sub">All your listings</div></div>
                <div class="icon green"><i class="fas fa-boxes-stacked"></i></div>
            </div>
            <div class="dash-card">
                <div class="info"><p>Live in Marketplace</p><h3><?= number_format($approved); ?></h3><div class="sub">Approved &amp; selling</div></div>
                <div class="icon gold"><i class="fas fa-store"></i></div>
            </div>
            <div class="dash-card">
                <div class="info"><p>Awaiting Approval</p><h3><?= number_format($pending); ?></h3><div class="sub">Pending admin review</div></div>
                <div class="icon navy"><i class="fas fa-hourglass-half"></i></div>
            </div>
        </div>

        <!-- List Produce form -->
        <section class="card dashboard-section produce-panel" id="add">
            <div class="panel-heading">
                <div>
                    <h2><i class="fas fa-basket-shopping"></i> List Your Produce</h2>
                    <p>Add fresh crops with price, stock, category, and an optional product photo. New listings go live after admin approval.</p>
                </div>
                <span class="badge-status badge-warning">Needs Approval</span>
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
                    <button type="submit" class="btn btn-primary"><i class="fas fa-cloud-arrow-up"></i> Submit for Approval</button>
                </div>
            </form>
        </section>

        <!-- Inventory listing -->
        <div class="table-wrap dashboard-section" id="inventory">
            <h2><i class="fas fa-warehouse" style="color:var(--emerald);margin-right:8px"></i> Inventory</h2>
            <table>
                <thead><tr><th>Crop</th><th>Category</th><th>Available</th><th>Unit Price</th><th>Approval</th><th>Marketplace</th></tr></thead>
                <tbody>
                    <?php if (!$products): ?>
                        <tr><td colspan="6" class="empty-cell">No produce listed yet. Use the form above to publish your first crop.</td></tr>
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
                            <td><?= htmlspecialchars($product['category_name']); ?></td>
                            <td><?= number_format((float)$product['quantity_available'], 2); ?> <?= htmlspecialchars($product['unit']); ?></td>
                            <td>BDT <?= number_format((float)$product['price'], 2); ?>/<?= htmlspecialchars($product['unit']); ?></td>
                            <td>
                                <span class="badge-status <?= $product['product_status'] === 'approved' ? 'badge-success' : ($product['product_status'] === 'rejected' ? 'badge-danger' : 'badge-warning'); ?>">
                                    <?= htmlspecialchars(ucfirst($product['product_status'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($product['product_status'] === 'approved'): ?>
                                    <a href="<?= url('pages/product.php?id=' . (int)$product['id']); ?>" class="btn btn-outline btn-sm">View live</a>
                                <?php else: ?>
                                    <span class="muted">Hidden</span>
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
