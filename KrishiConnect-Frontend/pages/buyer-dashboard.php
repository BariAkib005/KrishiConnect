<?php
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/includes/helpers.php';
require_once __DIR__ . '/../app/includes/cart.php';
require_once __DIR__ . '/../app/includes/wishlist.php';
require_once __DIR__ . '/../app/includes/db.php';

$user = require_role('buyer');
$name = $user['full_name'] ?? 'Buyer';
$cartItems = get_cart_items((int)$user['id']);
$wishlistTotal = wishlist_count((int)$user['id']);
$unreadStmt = db()->prepare(
    'SELECT COUNT(*) AS total
     FROM messages m
     JOIN conversation_participants cp ON cp.conversation_id = m.conversation_id
     WHERE cp.user_id = ? AND m.sender_id <> ? AND m.read_at IS NULL'
);
$unreadStmt->execute([(int)$user['id'], (int)$user['id']]);
$unreadMessages = (int)($unreadStmt->fetch()['total'] ?? 0);
$recommended = db()->query(
    'SELECT p.*, c.name AS category_name,
        (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS image_path
     FROM products p
     JOIN categories c ON c.id = p.category_id
     WHERE p.status = "active" AND p.product_status = "approved"
     ORDER BY p.rating DESC, p.created_at DESC
     LIMIT 4'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buyer Dashboard - KrishiConnect</title>
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
            <a href="<?= url('pages/buyer-dashboard.php'); ?>" class="active"><i class="fas fa-home"></i> Dashboard</a>
            <a href="<?= url('pages/settings.php#profile'); ?>"><i class="fas fa-user"></i> My Profile</a>
            <a href="<?= url('pages/marketplace.php'); ?>"><i class="fas fa-store"></i> Marketplace</a>
            <a href="<?= url('pages/cart.php'); ?>"><i class="fas fa-shopping-cart"></i> Shopping Cart</a>
            <a href="<?= url('pages/order-tracking.php'); ?>"><i class="fas fa-history"></i> Order History</a>
            <a href="<?= url('pages/wishlist.php'); ?>"><i class="fas fa-heart"></i> Wishlist</a>
            <a href="<?= url('pages/messaging.php'); ?>"><i class="fas fa-comments"></i> Messages</a>
            <a href="<?= url('pages/settings.php'); ?>"><i class="fas fa-cog"></i> Settings</a>
            <a href="<?= url('app/actions/logout.php'); ?>" style="margin-top:2rem;opacity:.6"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <main class="main-content">
        <div class="dash-header">
            <div><h1>Good morning, <?= htmlspecialchars($name); ?></h1><p>Fresh harvests from verified farmers, delivered to your door.</p></div>
            <div class="meta"><i class="fas fa-clock"></i> Last login: Today at 10:15 AM</div>
        </div>

        <div class="dash-cards">
            <div class="dash-card">
                <div class="info"><p>Active Orders</p><h3>4</h3><div class="sub">2 shipping today</div></div>
                <div class="icon green"><i class="fas fa-shipping-fast"></i></div>
            </div>
            <div class="dash-card">
                <div class="info"><p>Cart Items</p><h3><?= count($cartItems); ?></h3><div class="sub"><a href="<?= url('pages/cart.php'); ?>" style="color:var(--emerald);font-weight:600">Pending checkout</a></div></div>
                <div class="icon gold"><i class="fas fa-shopping-cart"></i></div>
            </div>
            <div class="dash-card">
                <div class="info"><p>Wishlisted</p><h3><?= $wishlistTotal; ?></h3><div class="sub"><a href="<?= url('pages/wishlist.php'); ?>" style="color:var(--emerald);font-weight:600">View saved products</a></div></div>
                <div class="icon navy"><i class="fas fa-heart"></i></div>
            </div>
            <a class="dash-card dash-card-link" href="<?= url('pages/messaging.php'); ?>">
                <div class="info"><p>Messages</p><h3><?= $unreadMessages; ?></h3><div class="sub">Unread farmer replies</div></div>
                <div class="icon green"><i class="fas fa-comments"></i></div>
            </a>
            <div class="dash-card">
                <div class="info"><p>Past Orders</p><h3>38</h3><div class="sub">Last 6 months</div></div>
                <div class="icon red"><i class="fas fa-history"></i></div>
            </div>
        </div>

        <div class="table-wrap">
            <h2><i class="fas fa-clipboard-list" style="color:var(--emerald);margin-right:8px"></i> Recent Orders</h2>
            <table>
                <thead><tr><th>Order ID</th><th>Items</th><th>Farmer</th><th>Order Date</th><th>Delivery</th><th>Amount</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    <tr><td>#KC-2841</td><td>Organic Wheat 25kg</td><td>Rafiq Ahmed</td><td>20 May</td><td>23 May</td><td>BDT 1,850</td><td><span class="badge-status badge-success">Delivered</span></td><td><a href="<?= url('pages/order-tracking.php'); ?>" class="btn btn-primary btn-sm">Details</a></td></tr>
                    <tr><td>#KC-2820</td><td>Toor Dal 10kg</td><td>Anita Rahman</td><td>18 May</td><td>22 May</td><td>BDT 1,200</td><td><span class="badge-status badge-success">Delivered</span></td><td><a href="<?= url('pages/order-tracking.php'); ?>" class="btn btn-primary btn-sm">Details</a></td></tr>
                    <tr><td>#KC-2799</td><td>Mustard Oil 5L</td><td>Mahesh Patil</td><td>15 May</td><td>25 May</td><td>BDT 3,500</td><td><span class="badge-status badge-warning">In Transit</span></td><td><a href="<?= url('pages/order-tracking.php'); ?>" class="btn btn-outline btn-sm">Track</a></td></tr>
                    <tr><td>#KC-2788</td><td>Mixed Vegetables</td><td>Fatima Begum</td><td>14 May</td><td>24 May</td><td>BDT 2,750</td><td><span class="badge-status badge-danger">Processing</span></td><td><a href="<?= url('pages/order-tracking.php'); ?>" class="btn btn-outline btn-sm">Track</a></td></tr>
                </tbody>
            </table>
        </div>

        <h2 style="font-size:1.2rem;margin:1.5rem 0 1rem"><i class="fas fa-thumbs-up" style="color:var(--gold);margin-right:8px"></i> Recommended for You</h2>
        <div class="product-grid">
            <?php if (!$recommended): ?>
                <div class="card" style="text-align:center">No recommendations available yet.</div>
            <?php endif; ?>
            <?php foreach ($recommended as $product): ?>
                <div class="product-card">
                    <div class="img-wrap" style="height: 160px;"><img src="<?= asset_url($product['image_path'] ?: 'images/vegetables/tomato.jpg'); ?>" alt="<?= htmlspecialchars($product['name']); ?>"></div>
                    <div class="details">
                        <h3 style="font-size:1rem"><?= htmlspecialchars($product['name']); ?></h3>
                        <div class="price-row"><span class="price" style="font-size:1rem">BDT <?= number_format((float)$product['price'], 0); ?>/<?= htmlspecialchars($product['unit']); ?></span><span class="rating"><i class="fas fa-star"></i> <?= number_format((float)$product['rating'], 1); ?></span></div>
                        <div class="actions">
                            <form method="post" action="<?= url('app/actions/cart_add.php'); ?>">
                                <input type="hidden" name="product_id" value="<?= (int)$product['id']; ?>">
                                <input type="hidden" name="quantity" value="1">
                                <button class="btn btn-primary btn-sm" type="submit">Add to Cart</button>
                            </form>
                            <form method="post" action="<?= url('app/actions/wishlist_add.php'); ?>">
                                <input type="hidden" name="product_id" value="<?= (int)$product['id']; ?>">
                                <input type="hidden" name="return_to" value="pages/buyer-dashboard.php">
                                <button class="btn btn-outline btn-sm" type="submit" aria-label="Save to wishlist"><i class="fas fa-heart"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>

</body>
</html>
