<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

function secure_session_start(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    $sessionPath = dirname(__DIR__) . '/storage/sessions';
    if (!is_dir($sessionPath)) {
        mkdir($sessionPath, 0775, true);
    }

    if (is_dir($sessionPath) && is_writable($sessionPath)) {
        session_save_path($sessionPath);
    }

    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? null) === '443');

    session_name('KRISHICONNECTSESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();

    if (empty($_SESSION['created_at'])) {
        $_SESSION['created_at'] = time();
    }

    if (empty($_SESSION['last_regenerated_at']) || time() - (int)$_SESSION['last_regenerated_at'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['last_regenerated_at'] = time();
    }
}

secure_session_start();

function csrf_token(string $key = 'default'): string
{
    if (empty($_SESSION['csrf_tokens'][$key])) {
        $_SESSION['csrf_tokens'][$key] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_tokens'][$key];
}

function verify_csrf_token(?string $token, string $key = 'default'): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_tokens'][$key])
        && hash_equals($_SESSION['csrf_tokens'][$key], $token);
}

/**
 * Render a hidden CSRF field for a form. Most authenticated forms share the
 * default 'app' key; pages that already use a dedicated key (login, product
 * actions, etc.) pass their own.
 */
function csrf_field(string $key = 'app'): string
{
    return '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars(csrf_token($key), ENT_QUOTES) . '">';
}

function require_csrf_token(?string $token, string $key = 'default', string $redirectPath = 'pages/login.php?error=csrf'): void
{
    if (!verify_csrf_token($token, $key)) {
        redirect($redirectPath);
    }
}

function client_ip_address(): string
{
    $candidates = [
        $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null,
        $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
        $_SERVER['REMOTE_ADDR'] ?? null,
    ];

    foreach ($candidates as $candidate) {
        if (!$candidate) {
            continue;
        }

        $ip = trim(explode(',', $candidate)[0]);
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
    }

    return 'unknown';
}

function write_security_log(?int $userId, string $action, string $details = '', ?string $ipAddress = null): void
{
    $pdo = db();
    $stmt = $pdo->prepare(
        'INSERT INTO security_logs (user_id, action, details, ip_address)
         VALUES (:user_id, :action, :details, :ip_address)'
    );
    $stmt->execute([
        ':user_id' => $userId,
        ':action' => $action,
        ':details' => $details,
        ':ip_address' => $ipAddress ?? client_ip_address(),
    ]);
}

function current_user(): ?array
{
    if (empty($_SESSION['logged_in']) || empty($_SESSION['user_id'])) {
        return null;
    }

    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, full_name, email, role, status FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function destroy_current_session(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => (bool)$params['secure'],
            'httponly' => (bool)$params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }

    session_destroy();
}

function require_login(): void
{
    $user = current_user();
    if (!$user || $user['status'] !== 'active') {
        destroy_current_session();
        redirect('pages/login.php?error=unauthorized');
    }
}

function dashboard_path_for_role(?string $role): string
{
    return match ($role) {
        'admin' => 'pages/admin-dashboard.php',
        'finance' => 'pages/finance-dashboard.php',
        'buyer' => 'pages/buyer-dashboard.php',
        'farmer' => 'pages/dashboard.php',
        default => 'index.php',
    };
}

function current_dashboard_path(): string
{
    $user = current_user();
    return dashboard_path_for_role($user['role'] ?? null);
}

function require_role(string $role): array
{
    return require_roles([$role]);
}

function require_roles(array|string $roles): array
{
    require_login();

    $roles = is_array($roles) ? $roles : [$roles];
    $user = current_user();
    if (!$user || !in_array($user['role'], $roles, true)) {
        destroy_current_session();
        redirect('pages/login.php?error=unauthorized');
    }

    return $user;
}

function login_user(array $user): void
{
    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['full_name'] ?? $user['email'] ?? '';
    $_SESSION['role'] = $user['role'];
    $_SESSION['user_type'] = $user['role'];
    $_SESSION['logged_in'] = true;
    $_SESSION['last_regenerated_at'] = time();
}

function logout_user(): void
{
    destroy_current_session();
}
