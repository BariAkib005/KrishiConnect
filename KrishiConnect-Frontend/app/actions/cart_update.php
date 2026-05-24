<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cart.php';

require_login();
$user = current_user();

$cartItemId = (int)($_POST['cart_item_id'] ?? 0);
$qty = (float)($_POST['quantity'] ?? 1);

if ($cartItemId > 0 && $user) {
    update_cart_item((int)$user['id'], $cartItemId, $qty);
}

redirect('pages/cart.php');
