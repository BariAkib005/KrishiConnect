<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/wishlist.php';

$user = require_role('buyer');
$productId = (int)($_POST['product_id'] ?? 0);
$returnTo = trim($_POST['return_to'] ?? 'pages/wishlist.php');

if ($productId > 0) {
    add_to_wishlist((int)$user['id'], $productId);
}

redirect($returnTo);
