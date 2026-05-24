<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cart.php';

require_login();
$user = current_user();

$productId = (int)($_POST['product_id'] ?? 0);
$qty = (float)($_POST['quantity'] ?? 1);

if ($productId > 0 && $user) {
    add_to_cart((int)$user['id'], $productId, $qty);
}

redirect('pages/cart.php');
