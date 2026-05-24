<?php
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/includes/helpers.php';
require_once __DIR__ . '/../app/includes/db.php';

require_login();
$user = current_user();
$orderId = (int)($_GET['order_id'] ?? 0);

$pdo = db();
$stmt = $pdo->prepare('SELECT id, placed_at, status, total_amount, shipping_address FROM orders WHERE id = ? AND buyer_id = ?');
$stmt->execute([$orderId, (int)$user['id']]);
$order = $stmt->fetch();
if (!$order) {
    redirect('pages/buyer-dashboard.php');
}

$itemStmt = $pdo->prepare(
    'SELECT p.name, oi.quantity, p.unit, oi.unit_price, u.full_name AS farmer_name
     FROM order_items oi
     JOIN products p ON p.id = oi.product_id
     JOIN users u ON u.id = p.farmer_id
     WHERE oi.order_id = ?'
);
$itemStmt->execute([$orderId]);
$items = $itemStmt->fetchAll();

$statusFlow = [
    'pending' => 1,
    'confirmed' => 2,
    'packed' => 3,
    'shipped' => 4,
    'delivered' => 5,
];
$step = $statusFlow[$order['status']] ?? 1;
$orderNumber = 'KC' . str_pad((string)$orderId, 5, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Tracking — KrishiConnect</title>
    <link rel="stylesheet" href="<?= asset_url('css/styles.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<?php require __DIR__ . '/../app/includes/header.php'; ?>

<section class="section">
    <div class="container">
        <div class="tracking-header">
            <div>
                <h1>Order Tracking</h1>
                <p>Track your order in real-time</p>
            </div>
            <a class="btn btn-outline btn-sm" href="#">Download Invoice</a>
        </div>

        <div class="tracking-grid">
            <div class="card">
                <div class="status-banner">
                    <div>
                        <div class="label">Order #<?= htmlspecialchars($orderNumber); ?></div>
                        <h2><?= ucfirst(htmlspecialchars($order['status'])); ?></h2>
                    </div>
                    <i class="fas fa-truck"></i>
                </div>

                <div class="timeline">
                    <div class="timeline-item <?= $step >= 1 ? 'done' : ''; ?>">
                        <div class="dot"><i class="fas fa-check"></i></div>
                        <div>
                            <h4>Order Placed</h4>
                            <p><?= date('F j, Y g:i A', strtotime($order['placed_at'])); ?></p>
                        </div>
                    </div>
                    <div class="timeline-item <?= $step >= 2 ? 'done' : ''; ?>">
                        <div class="dot"><i class="fas fa-check"></i></div>
                        <div>
                            <h4>Order Confirmed</h4>
                            <p>Processing started</p>
                        </div>
                    </div>
                    <div class="timeline-item <?= $step >= 3 ? 'done' : ''; ?>">
                        <div class="dot"><i class="fas fa-box"></i></div>
                        <div>
                            <h4>Packed &amp; Ready</h4>
                            <p>Preparing for shipment</p>
                        </div>
                    </div>
                    <div class="timeline-item <?= $step >= 4 ? 'done' : ''; ?>">
                        <div class="dot"><i class="fas fa-truck"></i></div>
                        <div>
                            <h4>In Transit</h4>
                            <p>On the way to your location</p>
                        </div>
                    </div>
                    <div class="timeline-item <?= $step >= 5 ? 'done' : ''; ?>">
                        <div class="dot"><i class="fas fa-check"></i></div>
                        <div>
                            <h4>Delivered</h4>
                            <p>Delivered to your address</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tracking-side">
                <div class="card">
                    <h3>Order Items</h3>
                    <?php foreach ($items as $item): ?>
                        <div class="summary-row">
                            <span><?= htmlspecialchars($item['name']); ?> (<?= $item['quantity']; ?> <?= htmlspecialchars($item['unit']); ?>)</span>
                            <span>৳<?= number_format((float)$item['unit_price'] * (float)$item['quantity'], 0); ?></span>
                        </div>
                    <?php endforeach; ?>
                    <div class="summary-row total"><span>Total</span><span>৳<?= number_format((float)$order['total_amount'], 0); ?></span></div>
                </div>
                <div class="card" style="margin-top:1.5rem">
                    <h3>Shipping Address</h3>
                    <p style="color:var(--gray)"><?= htmlspecialchars($order['shipping_address']); ?></p>
                </div>
                <div class="card" style="margin-top:1.5rem">
                    <h3>Need Help?</h3>
                    <div class="form-row">
                        <button class="btn btn-outline btn-block">Report an Issue</button>
                        <button class="btn btn-outline btn-block">Change Address</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../app/includes/footer.php'; ?>
</body>
</html>
