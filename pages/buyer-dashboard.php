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

// Real order metrics for this buyer.
$orderStatStmt = db()->prepare(
    'SELECT
        COUNT(*) AS total_orders,
        COALESCE(SUM(status IN ("pending","confirmed","packed","shipped")), 0) AS active_orders
     FROM orders WHERE buyer_id = ?'
);
$orderStatStmt->execute([(int)$user['id']]);
$orderStats = $orderStatStmt->fetch() ?: ['total_orders' => 0, 'active_orders' => 0];

$recentOrdersStmt = db()->prepare(
    'SELECT o.id, o.placed_at, o.status, o.total_amount,
            (SELECT p.name FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = o.id LIMIT 1) AS first_item,
            (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
     FROM orders o
     WHERE o.buyer_id = ?
     ORDER BY o.placed_at DESC
     LIMIT 5'
);
$recentOrdersStmt->execute([(int)$user['id']]);
$recentOrders = $recentOrdersStmt->fetchAll();
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
    <?php $active = 'dashboard'; require __DIR__ . '/../app/includes/buyer_sidebar.php'; ?>

    <main class="main-content">
        <div class="dash-header">
            <div><h1>Good morning, <?= htmlspecialchars($name); ?></h1><p>Fresh harvests from verified farmers, delivered to your door.</p></div>
            <div class="meta"><i class="fas fa-clock"></i> Last login: Today at 10:15 AM</div>
        </div>

        <div class="dash-cards">
            <a class="dash-card dash-card-link" href="<?= url('pages/order_history.php'); ?>">
                <div class="info"><p>Active Orders</p><h3><?= number_format((int)$orderStats['active_orders']); ?></h3><div class="sub">In progress</div></div>
                <div class="icon green"><i class="fas fa-shipping-fast"></i></div>
            </a>
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
            <a class="dash-card dash-card-link" href="<?= url('pages/order_history.php'); ?>">
                <div class="info"><p>Total Orders</p><h3><?= number_format((int)$orderStats['total_orders']); ?></h3><div class="sub">View order history</div></div>
                <div class="icon red"><i class="fas fa-history"></i></div>
            </a>
        </div>

        <div class="table-wrap">
            <div class="panel-heading" style="display:flex;justify-content:space-between;align-items:center">
                <h2><i class="fas fa-clipboard-list" style="color:var(--emerald);margin-right:8px"></i> Recent Orders</h2>
                <a href="<?= url('pages/order_history.php'); ?>" class="btn btn-outline btn-sm">View all</a>
            </div>
            <table>
                <thead><tr><th>Order ID</th><th>Items</th><th>Order Date</th><th>Amount</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    <?php if (!$recentOrders): ?>
                        <tr><td colspan="6" class="empty-cell">No orders yet. Visit the <a href="<?= url('pages/marketplace.php'); ?>">marketplace</a> to place your first order.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($recentOrders as $order): ?>
                        <?php
                            $itemLabel = $order['first_item'] ?? 'Order';
                            if ((int)$order['item_count'] > 1) {
                                $itemLabel .= ' +' . ((int)$order['item_count'] - 1) . ' more';
                            }
                            $statusClass = in_array($order['status'], ['delivered', 'confirmed'], true) ? 'badge-success' : ($order['status'] === 'cancelled' ? 'badge-danger' : 'badge-warning');
                        ?>
                        <tr>
                            <td>#KC<?= str_pad((string)$order['id'], 5, '0', STR_PAD_LEFT); ?></td>
                            <td><?= htmlspecialchars($itemLabel); ?></td>
                            <td><?= date('M j, Y', strtotime($order['placed_at'])); ?></td>
                            <td>BDT <?= number_format((float)$order['total_amount'], 0); ?></td>
                            <td><span class="badge-status <?= $statusClass; ?>"><?= htmlspecialchars(ucfirst($order['status'])); ?></span></td>
                            <td><a href="<?= url('pages/order-tracking.php?order_id=' . (int)$order['id']); ?>" class="btn btn-outline btn-sm">Track</a></td>
                        </tr>
                    <?php endforeach; ?>
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
                            <form method="post" action="<?= url('app/actions/cart_add.php'); ?>"><?= csrf_field('app'); ?>
                                <input type="hidden" name="product_id" value="<?= (int)$product['id']; ?>">
                                <input type="hidden" name="quantity" value="1">
                                <button class="btn btn-primary btn-sm" type="submit">Add to Cart</button>
                            </form>
                            <form method="post" action="<?= url('app/actions/wishlist_add.php'); ?>"><?= csrf_field('app'); ?>
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
