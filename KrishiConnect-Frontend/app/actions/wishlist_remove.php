<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/wishlist.php';

$user = require_role('buyer');
$productId = (int)($_POST['product_id'] ?? 0);

if ($productId > 0) {
    remove_from_wishlist((int)$user['id'], $productId);
}

redirect('pages/wishlist.php');
