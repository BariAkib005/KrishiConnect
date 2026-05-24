<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_login();
$user = current_user();
$action = trim($_POST['action'] ?? '');
$pdo = db();
$pdo->exec(
    "CREATE TABLE IF NOT EXISTS user_settings (
        user_id INT PRIMARY KEY,
        email_notifications TINYINT(1) NOT NULL DEFAULT 1,
        sms_notifications TINYINT(1) NOT NULL DEFAULT 1,
        language VARCHAR(40) NOT NULL DEFAULT 'English',
        region VARCHAR(80) NOT NULL DEFAULT 'Bangladesh',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )"
);
$pdo->exec(
    "CREATE TABLE IF NOT EXISTS user_payment_methods (
        user_id INT PRIMARY KEY,
        method VARCHAR(40) NOT NULL DEFAULT 'bKash',
        account_number VARCHAR(80) DEFAULT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )"
);

if (!$user) {
    redirect('pages/login.php');
}

if ($action === 'profile') {
    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $extra = trim($_POST['extra'] ?? '');

    if ($fullName === '') {
        redirect('pages/settings.php?error=profile#profile');
    }

    $stmt = $pdo->prepare('UPDATE users SET full_name = ?, phone = ? WHERE id = ?');
    $stmt->execute([$fullName, $phone, (int)$user['id']]);

    if ($user['role'] === 'buyer') {
        $profile = $pdo->prepare(
            'INSERT INTO buyer_profiles (user_id, company_name, address)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), address = VALUES(address)'
        );
        $profile->execute([(int)$user['id'], $extra, $address]);
    } elseif ($user['role'] === 'farmer') {
        $profile = $pdo->prepare(
            'INSERT INTO farmer_profiles (user_id, farm_name, location)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE farm_name = VALUES(farm_name), location = VALUES(location)'
        );
        $profile->execute([(int)$user['id'], $extra, $address]);
    } elseif ($user['role'] === 'finance') {
        $profile = $pdo->prepare(
            'INSERT INTO finance_profiles (user_id, institution, designation)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE institution = VALUES(institution), designation = VALUES(designation)'
        );
        $profile->execute([(int)$user['id'], $extra, $address]);
    }

    redirect('pages/settings.php?saved=profile#profile');
}

if ($action === 'password') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([(int)$user['id']]);
    $row = $stmt->fetch();

    if (!$row || !password_verify($current, $row['password_hash']) || strlen($new) < 8 || $new !== $confirm) {
        redirect('pages/settings.php?error=password#security');
    }

    $hash = password_hash($new, PASSWORD_DEFAULT);
    $upd = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $upd->execute([$hash, (int)$user['id']]);

    redirect('pages/settings.php?saved=password#security');
}

if ($action === 'preferences') {
    $stmt = $pdo->prepare(
        'INSERT INTO user_settings (user_id, email_notifications, sms_notifications, language, region)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            email_notifications = VALUES(email_notifications),
            sms_notifications = VALUES(sms_notifications),
            language = VALUES(language),
            region = VALUES(region)'
    );
    $stmt->execute([
        (int)$user['id'],
        isset($_POST['email_notifications']) ? 1 : 0,
        isset($_POST['sms_notifications']) ? 1 : 0,
        trim($_POST['language'] ?? 'English'),
        trim($_POST['region'] ?? 'Bangladesh'),
    ]);
    redirect('pages/settings.php?saved=preferences#preferences');
}

if ($action === 'payment') {
    $stmt = $pdo->prepare(
        'INSERT INTO user_payment_methods (user_id, method, account_number)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE method = VALUES(method), account_number = VALUES(account_number)'
    );
    $stmt->execute([
        (int)$user['id'],
        trim($_POST['payment_method'] ?? 'bKash'),
        trim($_POST['payment_account'] ?? ''),
    ]);
    redirect('pages/settings.php?saved=payment#payment');
}

redirect('pages/settings.php');
