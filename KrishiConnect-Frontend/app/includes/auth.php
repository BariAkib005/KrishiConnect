<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    $sessionPath = dirname(__DIR__) . '/storage/sessions';
    if (!is_dir($sessionPath)) {
        mkdir($sessionPath, 0775, true);
    }

    if (is_dir($sessionPath) && is_writable($sessionPath)) {
        session_save_path($sessionPath);
    }

    session_start();
}

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, full_name, email, role, status FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function require_login(): void
{
    $user = current_user();
    if (!$user || $user['status'] !== 'active') {
        $_SESSION = [];
        redirect('pages/login.php');
    }
}

function dashboard_path_for_role(?string $role): string
{
    return match ($role) {
        'admin' => 'pages/admin-dashboard.php',
        'finance' => 'pages/finance-dashboard.php',
        'buyer' => 'pages/buyer-dashboard.php',
        default => 'pages/dashboard.php',
    };
}

function current_dashboard_path(): string
{
    $user = current_user();
    return dashboard_path_for_role($user['role'] ?? null);
}

function require_role(string $role): array
{
    require_login();

    $user = current_user();
    if (!$user || $user['role'] !== $role) {
        redirect(dashboard_path_for_role($user['role'] ?? null));
    }

    return $user;
}

function login_user(array $user): void
{
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
}

function logout_user(): void
{
    $_SESSION = [];
    session_destroy();
}
