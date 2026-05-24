<?php
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/includes/helpers.php';
require_once __DIR__ . '/../app/includes/wishlist.php';

$user = require_role('buyer');
$items = get_wishlist_items((int)$user['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wishlist - KrishiConnect</title>
    <link rel="stylesheet" href="<?= asset_url('css/styles.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<?php require __DIR__ . '/../app/includes/header.php'; ?>

<section class="section">
    <div class="container">
        <div class="cart-header">
            <div>
                <h1>Wishlist</h1>
                <p><?= count($items); ?> saved products</p>
            </div>
            <a class="btn btn-outline" href="<?= url('pages/marketplace.php'); ?>"><i class="fas fa-store"></i> Browse Marketplace</a>
        </div>

        <?php if (!$items): ?>
            <div class="card" style="text-align:center;padding:3rem">
                <h3>No wishlist items yet</h3>
                <p style="color:var(--gray);margin:.75rem 0 1.5rem">Save products from the marketplace to compare and buy later.</p>
                <a class="btn btn-primary" href="<?= url('pages/marketplace.php'); ?>">Find Products</a>
            </div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($items as $product): ?>
                    <div class="product-card">
                        <div class="img-wrap">
                            <img src="<?= asset_url($product['image_path'] ?: 'images/vegetables/tomato.jpg'); ?>" alt="<?= htmlspecialchars($product['name']); ?>">
                            <span class="badge"><?= htmlspecialchars($product['category_name']); ?></span>
                        </div>
                        <div class="details">
                            <h3><?= htmlspecialchars($product['name']); ?></h3>
                            <p class="origin"><i class="fas fa-user"></i> <?= htmlspecialchars($product['farmer_name']); ?></p>
                            <div class="price-row">
                                <span class="price">BDT <?= number_format((float)$product['price'], 0); ?>/<?= htmlspecialchars($product['unit']); ?></span>
                                <span class="rating"><i class="fas fa-star"></i> <?= number_format((float)$product['rating'], 1); ?></span>
                            </div>
                            <div class="actions">
                                <form method="post" action="<?= url('app/actions/cart_add.php'); ?>">
                                    <input type="hidden" name="product_id" value="<?= (int)$product['id']; ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button class="btn btn-primary" type="submit">Add to Cart</button>
                                </form>
                                <form method="post" action="<?= url('app/actions/wishlist_remove.php'); ?>">
                                    <input type="hidden" name="product_id" value="<?= (int)$product['id']; ?>">
                                    <button class="btn btn-outline" type="submit"><i class="fas fa-heart-crack"></i> Remove</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../app/includes/footer.php'; ?>
</body>
</html>
