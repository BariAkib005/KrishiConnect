<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$user = require_role('admin');
require_csrf_token($_POST['csrf_token'] ?? null, 'product_flag', 'pages/marketplace.php');

$productId = (int)($_POST['product_id'] ?? 0);
$returnTo = $_POST['return_to'] ?? 'pages/marketplace.php';
// Only allow returning to internal pages.
if (!preg_match('#^pages/[a-z0-9_\-]+\.php#i', $returnTo)) {
    $returnTo = 'pages/marketplace.php';
}

if ($productId <= 0) {
    redirect($returnTo);
}

$pdo = db();
$stmt = $pdo->prepare('SELECT id, name FROM products WHERE id = ?');
$stmt->execute([$productId]);
$product = $stmt->fetch();
if (!$product) {
    redirect($returnTo);
}

// Flagging pulls the product out of the live marketplace feed for review.
$pdo->prepare('UPDATE products SET product_status = "rejected", status = "inactive" WHERE id = ?')->execute([$productId]);

write_security_log(
    (int)$user['id'],
    'product_flagged',
    sprintf('Admin flagged product #%d (%s) and removed it from the marketplace.', $productId, $product['name'])
);

$separator = str_contains($returnTo, '?') ? '&' : '?';
redirect($returnTo . $separator . 'flagged=1');
