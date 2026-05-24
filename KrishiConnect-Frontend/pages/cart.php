<?php
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/includes/cart.php';
require_once __DIR__ . '/../app/includes/helpers.php';

require_login();
$user = current_user();
$items = get_cart_items((int)$user['id']);
$totals = cart_totals($items);
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart — KrishiConnect</title>
    <link rel="stylesheet" href="<?= asset_url('css/styles.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<?php require __DIR__ . '/../app/includes/header.php'; ?>

<section class="section">
    <div class="container">
        <div class="cart-header">
            <div>
                <h1>Shopping Cart</h1>
                <p><?= count($items); ?> items in your cart</p>
            </div>
            <?php if ($error === 'empty'): ?>
                <div class="alert">Your cart is empty.</div>
            <?php endif; ?>
        </div>

        <div class="cart-grid">
            <div class="cart-items">
                <?php if (!$items): ?>
                    <div class="card" style="text-align:center;padding:3rem">
                        <h3>Your cart is empty</h3>
                        <p style="color:var(--gray);margin:0.75rem 0 1.5rem">Add some products to get started</p>
                        <a class="btn btn-primary" href="<?= url('pages/marketplace.php'); ?>">Browse Products</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <div class="cart-item">
                            <div class="cart-thumb">
                                <img src="<?= asset_url($item['image_path'] ?: 'images/vegetables/tomato.jpg'); ?>" alt="<?= htmlspecialchars($item['name']); ?>">
                            </div>
                            <div class="cart-info">
                                <div class="cart-info-head">
                                    <div>
                                        <h3><?= htmlspecialchars($item['name']); ?></h3>
                                        <p>by <?= htmlspecialchars($item['farmer_name']); ?></p>
                                    </div>
                                    <form method="post" action="<?= url('app/actions/cart_remove.php'); ?>">
                                        <input type="hidden" name="cart_item_id" value="<?= (int)$item['cart_item_id']; ?>">
                                        <button class="icon-btn" type="submit" aria-label="Remove">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                                <div class="cart-info-body">
                                    <form method="post" action="<?= url('app/actions/cart_update.php'); ?>" class="qty-control">
                                        <input type="hidden" name="cart_item_id" value="<?= (int)$item['cart_item_id']; ?>">
                                        <button type="submit" name="quantity" value="<?= max(1, (float)$item['quantity'] - 1); ?>" class="qty-btn">-</button>
                                        <span><?= (float)$item['quantity']; ?> <?= htmlspecialchars($item['unit']); ?></span>
                                        <button type="submit" name="quantity" value="<?= (float)$item['quantity'] + 1; ?>" class="qty-btn">+</button>
                                    </form>
                                    <div class="cart-price">
                                        <div class="line">৳<?= number_format((float)$item['unit_price'] * (float)$item['quantity'], 0); ?></div>
                                        <div class="unit">৳<?= number_format((float)$item['unit_price'], 0); ?>/<?= htmlspecialchars($item['unit']); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ($items): ?>
            <div class="cart-summary">
                <div class="summary-box">
                    <h3>Order Summary</h3>
                    <div class="summary-row"><span>Subtotal</span><span>৳<?= number_format($totals['subtotal'], 0); ?></span></div>
                    <div class="summary-row"><span>Delivery</span><span>৳<?= number_format($totals['delivery'], 0); ?></span></div>
                    <div class="summary-row total"><span>Total</span><span>৳<?= number_format($totals['total'], 0); ?></span></div>
                    <a class="btn btn-primary btn-block" href="<?= url('pages/checkout.php'); ?>">Proceed to Checkout</a>
                    <a class="btn btn-outline btn-block" href="<?= url('pages/marketplace.php'); ?>">Continue Shopping</a>
                </div>
                <div class="summary-box" style="margin-top:1.5rem">
                    <h4>Why shop with us?</h4>
                    <ul class="trust-list">
                        <li><i class="fas fa-check"></i> Direct from verified farmers</li>
                        <li><i class="fas fa-check"></i> Quality guarantee</li>
                        <li><i class="fas fa-check"></i> Secure payment</li>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../app/includes/footer.php'; ?>
</body>
</html>
