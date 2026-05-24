<?php
$title = 'Product - KrishiConnect';
$active = 'marketplace';
require_once __DIR__ . '/../app/includes/header.php';
require_once __DIR__ . '/../app/includes/db.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT p.*, c.name AS category_name, (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS image_path FROM products p JOIN categories c ON c.id = p.category_id WHERE p.id = ?');
$stmt->execute([$id]);
$product = $stmt->fetch();
?>

<section class="section">
    <div class="container">
        <?php if (!$product): ?>
            <div class="card" style="text-align:center">
                <h2>Product not found</h2>
                <p style="color:var(--gray);margin:1rem 0">The requested product is unavailable.</p>
                <a href="<?= url('pages/marketplace.php'); ?>" class="btn btn-primary">Back to Marketplace</a>
            </div>
        <?php else: ?>
            <div class="product-card" style="max-width:920px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));overflow:hidden">
                <div class="img-wrap" style="height:100%">
                    <img src="<?= asset_url($product['image_path'] ?: 'images/vegetables/tomato.jpg'); ?>" alt="<?= htmlspecialchars($product['name']); ?>">
                </div>
                <div class="details" style="padding:2rem">
                    <span class="badge"><?= htmlspecialchars($product['category_name']); ?></span>
                    <h2 style="margin-top:1rem"><?= htmlspecialchars($product['name']); ?></h2>
                    <p style="color:var(--gray);margin:1rem 0"><?= htmlspecialchars($product['description'] ?: 'Fresh produce from a verified KrishiConnect farmer.'); ?></p>
                    <div class="price-row" style="margin-bottom:1.5rem">
                        <span class="price">BDT <?= number_format((float)$product['price'], 0); ?>/<?= htmlspecialchars($product['unit']); ?></span>
                        <span class="rating"><i class="fas fa-star"></i> <?= number_format((float)$product['rating'], 1); ?></span>
                    </div>
                    <form method="post" action="<?= url('app/actions/cart_add.php'); ?>">
                        <input type="hidden" name="product_id" value="<?= (int)$product['id']; ?>">
                        <div class="form-group">
                            <label for="quantity">Quantity</label>
                            <input type="number" id="quantity" name="quantity" min="1" value="1">
                        </div>
                        <div class="design-actions">
                            <button class="btn btn-primary" type="submit">Add to Cart</button>
                            <button class="btn btn-outline" type="submit" formaction="<?= url('app/actions/wishlist_add.php'); ?>" name="return_to" value="pages/product.php?id=<?= (int)$product['id']; ?>"><i class="fas fa-heart"></i> Wishlist</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
