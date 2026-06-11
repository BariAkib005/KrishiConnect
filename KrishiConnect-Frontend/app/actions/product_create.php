<?php
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_csrf_token($_POST['csrf_token'] ?? null, 'product_create', 'pages/dashboard.php?product_error=invalid#list-produce');

$user = current_user();
if (!$user || $user['role'] !== 'farmer') {
    redirect('pages/dashboard.php?product_error=role');
}

$name = trim($_POST['name'] ?? '');
$variety = trim($_POST['variety'] ?? '');
$description = trim($_POST['description'] ?? '');
$categoryId = (int)($_POST['category_id'] ?? 0);
$price = (float)($_POST['price'] ?? 0);
$unit = trim($_POST['unit'] ?? 'kg');
$quantity = (float)($_POST['quantity_available'] ?? 0);

$allowedUnits = ['kg', 'pc', 'bundle', 'maund'];
if ($name === '' || $categoryId <= 0 || $price <= 0 || $quantity <= 0 || !in_array($unit, $allowedUnits, true)) {
    redirect('pages/dashboard.php?product_error=invalid#list-produce');
}

$pdo = db();
$categoryStmt = $pdo->prepare('SELECT id FROM categories WHERE id = ?');
$categoryStmt->execute([$categoryId]);
if (!$categoryStmt->fetch()) {
    redirect('pages/dashboard.php?product_error=category#list-produce');
}

$imagePath = null;
if (!empty($_FILES['product_image']) && ($_FILES['product_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['product_image']['error'] !== UPLOAD_ERR_OK) {
        redirect('pages/dashboard.php?product_error=image#list-produce');
    }

    if ((int)$_FILES['product_image']['size'] > 3 * 1024 * 1024) {
        redirect('pages/dashboard.php?product_error=image_size#list-produce');
    }

    $extension = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($extension, $allowedExtensions, true)) {
        redirect('pages/dashboard.php?product_error=image_type#list-produce');
    }

    $uploadDir = dirname(__DIR__, 2) . '/images/produce';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    $fileName = 'produce-' . $user['id'] . '-' . bin2hex(random_bytes(8)) . '.' . $extension;
    $target = $uploadDir . '/' . $fileName;
    if (!move_uploaded_file($_FILES['product_image']['tmp_name'], $target)) {
        redirect('pages/dashboard.php?product_error=image_save#list-produce');
    }

    $imagePath = 'images/produce/' . $fileName;
}

if ($imagePath === null) {
    $fallbacks = [
        'tomato' => 'images/vegetables/tomato.jpg',
        'brinjal' => 'images/vegetables/brinjal.jpg',
        'potato' => 'images/vegetables/potato.jpg',
        'okra' => 'images/vegetables/okra.jpg',
        'spinach' => 'images/vegetables/spinach.jpg',
        'cauliflower' => 'images/vegetables/cauliflower.jpg',
    ];

    $needle = strtolower($name);
    foreach ($fallbacks as $crop => $path) {
        if (str_contains($needle, $crop)) {
            $imagePath = $path;
            break;
        }
    }

    $imagePath ??= 'images/vegetables/tomato.jpg';
}

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare(
        'INSERT INTO products (farmer_id, category_id, name, variety, description, price, unit, quantity_available, rating, product_status, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?)'
    );
    $stmt->execute([
        $user['id'],
        $categoryId,
        $name,
        $variety !== '' ? $variety : null,
        $description !== '' ? $description : null,
        $price,
        $unit,
        $quantity,
        'pending',
        'inactive',
    ]);

    $productId = (int)$pdo->lastInsertId();
    $imageStmt = $pdo->prepare('INSERT INTO product_images (product_id, image_path, is_primary) VALUES (?, ?, 1)');
    $imageStmt->execute([$productId, $imagePath]);

    $pdo->commit();
} catch (Throwable $exception) {
    $pdo->rollBack();
    redirect('pages/dashboard.php?product_error=save#list-produce');
}

redirect('pages/dashboard.php?product_created=1#inventory');
