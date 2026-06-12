<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$user = require_role('admin');
require_csrf_token($_POST['csrf_token'] ?? null, 'product_review', 'pages/product-approval.php?status=invalid');

$productId = (int)($_POST['product_id'] ?? 0);
$decision = trim($_POST['decision'] ?? '');

if ($productId <= 0 || !in_array($decision, ['approve', 'reject'], true)) {
    redirect('pages/product-approval.php?status=invalid');
}

$pdo = db();
$stmt = $pdo->prepare('SELECT id, name FROM products WHERE id = ?');
$stmt->execute([$productId]);
$product = $stmt->fetch();
if (!$product) {
    redirect('pages/product-approval.php?status=invalid');
}

if ($decision === 'approve') {
    // Approving publishes the listing to the live marketplace.
    $pdo->prepare('UPDATE products SET product_status = "approved", status = "active" WHERE id = ?')->execute([$productId]);
    write_security_log((int)$user['id'], 'product_approved', sprintf('Approved product #%d (%s).', $productId, $product['name']));
    redirect('pages/product-approval.php?status=approved');
}

$pdo->prepare('UPDATE products SET product_status = "rejected", status = "inactive" WHERE id = ?')->execute([$productId]);
write_security_log((int)$user['id'], 'product_rejected', sprintf('Rejected product #%d (%s).', $productId, $product['name']));
redirect('pages/product-approval.php?filter=rejected&status=rejected');
