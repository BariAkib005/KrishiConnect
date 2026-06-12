<?php
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/includes/helpers.php';
require_once __DIR__ . '/../app/includes/db.php';

$user = require_roles(['farmer', 'buyer']);
$pdo = db();
$isFarmer = $user['role'] === 'farmer';

if ($isFarmer) {
    // Orders buyers placed for this farmer's products.
    $stmt = $pdo->prepare(
        'SELECT o.id AS order_id, o.placed_at, o.status, o.payment_status,
                p.name AS product_name, oi.quantity, p.unit, oi.unit_price,
                cp.full_name AS counterpart
         FROM orders o
         JOIN order_items oi ON oi.order_id = o.id
         JOIN products p ON p.id = oi.product_id
         JOIN users cp ON cp.id = o.buyer_id
         WHERE p.farmer_id = ?
         ORDER BY o.placed_at DESC, o.id DESC'
    );
} else {
    // The buyer's own orders.
    $stmt = $pdo->prepare(
        'SELECT o.id AS order_id, o.placed_at, o.status, o.payment_status,
                p.name AS product_name, oi.quantity, p.unit, oi.unit_price,
                cp.full_name AS counterpart
         FROM orders o
         JOIN order_items oi ON oi.order_id = o.id
         JOIN products p ON p.id = oi.product_id
         JOIN users cp ON cp.id = p.farmer_id
         WHERE o.buyer_id = ?
         ORDER BY o.placed_at DESC, o.id DESC'
    );
}
$stmt->execute([(int)$user['id']]);
$rows = $stmt->fetchAll();

$pageTitle = $isFarmer ? 'Sales' : 'Order History';
$counterpartLabel = $isFarmer ? 'Buyer' : 'Farmer';

function order_status_badge(string $status): string
{
    return in_array($status, ['delivered', 'confirmed'], true)
        ? 'badge-success'
        : ($status === 'cancelled' ? 'badge-danger' : 'badge-warning');
}

$ordersTable = static function (array $rows, string $counterpartLabel, bool $isFarmer): void {
    ?>
    <div class="table-wrap">
        <h2><i class="fas fa-receipt" style="color:var(--emerald);margin-right:8px"></i> <?= $isFarmer ? 'Orders for Your Products' : 'Your Orders'; ?></h2>
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Order Date</th>
                    <th>Product</th>
                    <th><?= htmlspecialchars($counterpartLabel); ?></th>
                    <th>Qty</th>
                    <th>Unit Price</th>
                    <th>Subtotal</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$rows): ?>
                    <tr><td colspan="8" class="empty-cell"><?= $isFarmer ? 'No orders yet. Buyers will appear here once they purchase your produce.' : 'You have not placed any orders yet.'; ?></td></tr>
                <?php endif; ?>
                <?php foreach ($rows as $row): ?>
                    <?php $subtotal = (float)$row['unit_price'] * (float)$row['quantity']; ?>
                    <tr>
                        <td>#KC<?= str_pad((string)$row['order_id'], 5, '0', STR_PAD_LEFT); ?></td>
                        <td><?= date('M j, Y', strtotime($row['placed_at'])); ?></td>
                        <td><?= htmlspecialchars($row['product_name']); ?></td>
                        <td><?= htmlspecialchars($row['counterpart']); ?></td>
                        <td><?= number_format((float)$row['quantity'], 0); ?> <?= htmlspecialchars($row['unit']); ?></td>
                        <td>BDT <?= number_format((float)$row['unit_price'], 2); ?></td>
                        <td>BDT <?= number_format($subtotal, 2); ?></td>
                        <td><span class="badge-status <?= order_status_badge($row['status']); ?>"><?= htmlspecialchars(ucfirst($row['status'])); ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle); ?> - KrishiConnect</title>
    <link rel="stylesheet" href="<?= asset_url('css/styles.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<?php if ($isFarmer): ?>
<div class="dashboard-layout">
    <?php $active = 'sales'; require __DIR__ . '/../app/includes/farmer_sidebar.php'; ?>
    <main class="main-content">
        <div class="dash-header">
            <div><h1>Sales</h1><p>Track every order buyers have placed for your produce.</p></div>
        </div>
        <?php $ordersTable($rows, $counterpartLabel, true); ?>
    </main>
</div>
<?php else: ?>
<?php require __DIR__ . '/../app/includes/header.php'; ?>
<section class="section">
    <div class="container">
        <div class="dash-header">
            <div><h1>Order History</h1><p>Every order you have placed on KrishiConnect.</p></div>
            <a href="<?= url('pages/marketplace.php'); ?>" class="btn btn-outline btn-sm"><i class="fas fa-store"></i> Continue Shopping</a>
        </div>
        <?php $ordersTable($rows, $counterpartLabel, false); ?>
    </div>
</section>
<?php require __DIR__ . '/../app/includes/footer.php'; ?>
<?php endif; ?>

</body>
</html>
