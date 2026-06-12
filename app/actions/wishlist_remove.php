<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/wishlist.php';

$user = require_role('buyer');
require_csrf_token($_POST['csrf_token'] ?? null, 'app', 'pages/wishlist.php?error=csrf');
$productId = (int)($_POST['product_id'] ?? 0);

if ($productId > 0) {
    remove_from_wishlist((int)$user['id'], $productId);
}

redirect('pages/wishlist.php');
