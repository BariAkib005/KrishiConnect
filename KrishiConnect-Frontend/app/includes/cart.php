<?php

require_once __DIR__ . '/db.php';

function get_or_create_cart_id(int $userId): int
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id FROM carts WHERE user_id = ? AND status = "open" LIMIT 1');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if ($row) {
        return (int)$row['id'];
    }

    $stmt = $pdo->prepare('INSERT INTO carts (user_id, status) VALUES (?, "open")');
    $stmt->execute([$userId]);

    return (int)$pdo->lastInsertId();
}

function get_cart_items(int $userId): array
{
    $pdo = db();
    $stmt = $pdo->prepare(
        'SELECT ci.id AS cart_item_id, ci.quantity, ci.unit_price,
                p.id AS product_id, p.name, p.unit, p.quantity_available, p.price,
                u.full_name AS farmer_name,
                (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS image_path
         FROM carts c
         JOIN cart_items ci ON ci.cart_id = c.id
         JOIN products p ON p.id = ci.product_id
         JOIN users u ON u.id = p.farmer_id
         WHERE c.user_id = ? AND c.status = "open"
         ORDER BY ci.id DESC'
    );
    $stmt->execute([$userId]);

    return $stmt->fetchAll();
}

function cart_totals(array $items): array
{
    $subtotal = 0;
    foreach ($items as $item) {
        $subtotal += (float)$item['unit_price'] * (float)$item['quantity'];
    }

    $delivery = $subtotal > 0 ? 150 : 0;

    return [
        'subtotal' => $subtotal,
        'delivery' => $delivery,
        'total' => $subtotal + $delivery,
    ];
}

function add_to_cart(int $userId, int $productId, float $qty = 1): void
{
    $pdo = db();
    $cartId = get_or_create_cart_id($userId);

    $productStmt = $pdo->prepare('SELECT price, quantity_available FROM products WHERE id = ? AND status = "active"');
    $productStmt->execute([$productId]);
    $product = $productStmt->fetch();
    if (!$product) {
        return;
    }

    $qty = max(1, $qty);
    $qty = min($qty, (float)$product['quantity_available']);

    $stmt = $pdo->prepare('SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ?');
    $stmt->execute([$cartId, $productId]);
    $existing = $stmt->fetch();

    if ($existing) {
        $newQty = min((float)$product['quantity_available'], (float)$existing['quantity'] + $qty);
        $update = $pdo->prepare('UPDATE cart_items SET quantity = ? WHERE id = ?');
        $update->execute([$newQty, $existing['id']]);
        return;
    }

    $insert = $pdo->prepare('INSERT INTO cart_items (cart_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)');
    $insert->execute([$cartId, $productId, $qty, $product['price']]);
}

function update_cart_item(int $userId, int $cartItemId, float $qty): void
{
    $pdo = db();
    $qty = max(1, $qty);

    $stmt = $pdo->prepare(
        'SELECT ci.id, p.quantity_available
         FROM cart_items ci
         JOIN carts c ON c.id = ci.cart_id
         JOIN products p ON p.id = ci.product_id
         WHERE ci.id = ? AND c.user_id = ? AND c.status = "open"'
    );
    $stmt->execute([$cartItemId, $userId]);
    $row = $stmt->fetch();
    if (!$row) {
        return;
    }

    $qty = min($qty, (float)$row['quantity_available']);
    $update = $pdo->prepare('UPDATE cart_items SET quantity = ? WHERE id = ?');
    $update->execute([$qty, $cartItemId]);
}

function remove_cart_item(int $userId, int $cartItemId): void
{
    $pdo = db();
    $stmt = $pdo->prepare(
        'DELETE ci FROM cart_items ci
         JOIN carts c ON c.id = ci.cart_id
         WHERE ci.id = ? AND c.user_id = ? AND c.status = "open"'
    );
    $stmt->execute([$cartItemId, $userId]);
}
