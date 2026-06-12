<?php

require_once __DIR__ . '/db.php';

function get_or_create_wishlist_id(int $userId): int
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id FROM wishlists WHERE user_id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if ($row) {
        return (int)$row['id'];
    }

    $stmt = $pdo->prepare('INSERT INTO wishlists (user_id) VALUES (?)');
    $stmt->execute([$userId]);

    return (int)$pdo->lastInsertId();
}

function add_to_wishlist(int $userId, int $productId): void
{
    $pdo = db();
    $wishlistId = get_or_create_wishlist_id($userId);

    $productStmt = $pdo->prepare('SELECT id FROM products WHERE id = ? AND status = "active" AND product_status = "approved"');
    $productStmt->execute([$productId]);
    if (!$productStmt->fetch()) {
        return;
    }

    $existing = $pdo->prepare('SELECT id FROM wishlist_items WHERE wishlist_id = ? AND product_id = ?');
    $existing->execute([$wishlistId, $productId]);
    if ($existing->fetch()) {
        return;
    }

    $insert = $pdo->prepare('INSERT INTO wishlist_items (wishlist_id, product_id) VALUES (?, ?)');
    $insert->execute([$wishlistId, $productId]);
}

function remove_from_wishlist(int $userId, int $productId): void
{
    $pdo = db();
    $stmt = $pdo->prepare(
        'DELETE wi FROM wishlist_items wi
         JOIN wishlists w ON w.id = wi.wishlist_id
         WHERE w.user_id = ? AND wi.product_id = ?'
    );
    $stmt->execute([$userId, $productId]);
}

function get_wishlist_items(int $userId): array
{
    $pdo = db();
    $stmt = $pdo->prepare(
        'SELECT p.*, c.name AS category_name, u.full_name AS farmer_name,
                (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS image_path
         FROM wishlists w
         JOIN wishlist_items wi ON wi.wishlist_id = w.id
         JOIN products p ON p.id = wi.product_id
         JOIN categories c ON c.id = p.category_id
         JOIN users u ON u.id = p.farmer_id
         WHERE w.user_id = ? AND p.status = "active" AND p.product_status = "approved"
         ORDER BY wi.id DESC'
    );
    $stmt->execute([$userId]);

    return $stmt->fetchAll();
}

function wishlist_count(int $userId): int
{
    $pdo = db();
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) AS total
         FROM wishlists w
         JOIN wishlist_items wi ON wi.wishlist_id = w.id
         WHERE w.user_id = ?'
    );
    $stmt->execute([$userId]);
    return (int)($stmt->fetch()['total'] ?? 0);
}
