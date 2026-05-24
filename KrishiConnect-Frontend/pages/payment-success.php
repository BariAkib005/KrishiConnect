<?php
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/includes/helpers.php';
require_once __DIR__ . '/../app/includes/db.php';

require_login();
$user = current_user();
$orderId = (int)($_GET['order_id'] ?? 0);

$pdo = db();
$stmt = $pdo->prepare('SELECT id, payment_method, status, placed_at FROM orders WHERE id = ? AND buyer_id = ?');
$stmt->execute([$orderId, (int)$user['id']]);
$order = $stmt->fetch();
if (!$order) {
    redirect('pages/marketplace.php');
}

$orderNumber = 'KC' . str_pad((string)$orderId, 5, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success — KrishiConnect</title>
    <link rel="stylesheet" href="<?= asset_url('css/styles.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<?php require __DIR__ . '/../app/includes/header.php'; ?>

<section class="section">
    <div class="container" style="max-width:820px">
        <div class="success-card">
            <div class="success-icon"><i class="fas fa-check"></i></div>
            <h1>Payment Successful!</h1>
            <p>Your order has been placed successfully.</p>

            <div class="card" style="margin:2rem 0;text-align:left">
                <div class="summary-row"><span>Order Number</span><span><?= htmlspecialchars($orderNumber); ?></span></div>
                <div class="summary-row"><span>Order Date</span><span><?= date('F j, Y', strtotime($order['placed_at'])); ?></span></div>
                <div class="summary-row"><span>Estimated Delivery</span><span><?= date('F j', strtotime('+4 days')); ?> - <?= date('F j, Y', strtotime('+6 days')); ?></span></div>
                <div class="summary-row"><span>Payment Method</span><span><?= htmlspecialchars(ucfirst($order['payment_method'])); ?></span></div>
                <div class="summary-row"><span>Order Status</span><span class="badge-status badge-warning"><?= htmlspecialchars($order['status']); ?></span></div>
            </div>

            <div class="action-row">
                <a class="btn btn-primary" href="<?= url('pages/order-tracking.php?order_id=' . $orderId); ?>">Track Order</a>
                <a class="btn btn-outline" href="<?= url('index.php'); ?>">Back to Home</a>
            </div>
            <a href="<?= url('pages/marketplace.php'); ?>" style="display:inline-block;margin-top:1rem">Continue Shopping</a>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../app/includes/footer.php'; ?>
</body>
</html>
