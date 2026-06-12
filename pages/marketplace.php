<?php
$title = 'Marketplace - KrishiConnect';
$active = 'marketplace';
require_once __DIR__ . '/../app/includes/header.php';
require_once __DIR__ . '/../app/includes/db.php';

$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');

// $user is populated by header.php. Purchase actions are buyer-only; admins
// get oversight controls (Flag Product) instead of cart/checkout.
$viewerRole = $user['role'] ?? 'guest';
$flashFlag = $_GET['flagged'] ?? '';

$pdo = db();
$categoryRows = $pdo->query('SELECT id, name, slug FROM categories ORDER BY name')->fetchAll();

$sql = 'SELECT p.*, c.name AS category_name,
        (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS image_path
        FROM products p
        JOIN categories c ON c.id = p.category_id
        WHERE p.status = "active" AND p.product_status = "approved"';
$params = [];

if ($search !== '') {
    $sql .= ' AND p.name LIKE ?';
    $params[] = '%' . $search . '%';
}

if ($category !== '') {
    $sql .= ' AND c.slug = ?';
    $params[] = $category;
}

$sql .= ' ORDER BY p.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<section class="section">
    <div class="container">
        <div class="section-header">
            <span class="section-label">Marketplace</span>
            <h2>Fresh Produce, Directly From Farmers</h2>
            <p>Browse verified listings and buy straight from the source.</p>
        </div>

        <?php if ($viewerRole === 'admin'): ?>
            <div class="notice"><i class="fas fa-shield-halved"></i> Admin oversight mode — purchasing is disabled. You can review listings and flag any product for removal.</div>
        <?php endif; ?>
        <?php if ($flashFlag === '1'): ?>
            <div class="notice success"><i class="fas fa-flag"></i> Product flagged and removed from the marketplace feed.</div>
        <?php endif; ?>
        <div class="filter-bar">
            <form method="get" action="<?= url('pages/marketplace.php'); ?>">
                <div class="form-group">
                    <label for="search">Search</label>
                    <input type="text" id="search" name="search" value="<?= htmlspecialchars($search); ?>" placeholder="Search crops, farmers, regions...">
                </div>
                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categoryRows as $row): ?>
                            <option value="<?= htmlspecialchars($row['slug']); ?>" <?= $category === $row['slug'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($row['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn-primary" type="submit">Filter</button>
            </form>
        </div>

        <div class="product-grid">
            <?php if (!$products): ?>
                <div class="card" style="grid-column:1/-1;text-align:center">No products found. Try another search.</div>
            <?php endif; ?>
            <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <div class="img-wrap">
                        <img src="<?= product_image_src($product); ?>" alt="<?= htmlspecialchars($product['name']); ?>">
                        <span class="badge"><?= htmlspecialchars($product['category_name']); ?></span>
                        <div style="position:absolute;top:10px;right:10px;display:flex;flex-direction:column;gap:4px;align-items:flex-end">
                            <?php if (!empty($product['is_featured'])): ?><span class="badge-status badge-warning"><i class="fas fa-star"></i> Featured</span><?php endif; ?>
                            <?php if (!empty($product['is_organic'])): ?><span class="badge-status badge-success"><i class="fas fa-leaf"></i> Organic</span><?php endif; ?>
                        </div>
                    </div>
                    <div class="details">
                        <h3><?= htmlspecialchars($product['name']); ?></h3>
                        <p class="origin"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($product['local_name'] ?? '') ?: 'Verified Farm'; ?></p>
                        <div class="price-row">
                            <span class="price">BDT <?= number_format((float)$product['price'], 0); ?>/<?= htmlspecialchars($product['unit']); ?></span>
                            <span class="rating"><i class="fas fa-star"></i> <?= number_format((float)$product['rating'], 1); ?></span>
                        </div>
                        <div class="actions">
                            <a href="<?= url('pages/product.php?id=' . $product['id']); ?>" class="btn btn-outline">View</a>
                            <?php if ($viewerRole === 'admin'): ?>
                                <form method="post" action="<?= url('app/actions/product_flag.php'); ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('product_flag'), ENT_QUOTES); ?>">
                                    <input type="hidden" name="product_id" value="<?= (int)$product['id']; ?>">
                                    <input type="hidden" name="return_to" value="pages/marketplace.php">
                                    <button class="btn btn-primary" type="submit" style="background:var(--red);border-color:var(--red)"><i class="fas fa-flag"></i> Flag Product</button>
                                </form>
                            <?php elseif ($viewerRole === 'buyer'): ?>
                                <form method="post" action="<?= url('app/actions/cart_add.php'); ?>"><?= csrf_field('app'); ?>
                                    <input type="hidden" name="product_id" value="<?= (int)$product['id']; ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button class="btn btn-primary" type="submit">Add to Cart</button>
                                </form>
                                <form method="post" action="<?= url('app/actions/wishlist_add.php'); ?>"><?= csrf_field('app'); ?>
                                    <input type="hidden" name="product_id" value="<?= (int)$product['id']; ?>">
                                    <input type="hidden" name="return_to" value="pages/marketplace.php">
                                    <button class="btn btn-outline" type="submit" aria-label="Save to wishlist"><i class="fas fa-heart"></i></button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>

