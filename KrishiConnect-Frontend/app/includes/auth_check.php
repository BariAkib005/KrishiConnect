<?php
require_once __DIR__ . '/auth.php';

/**
 * Include this file at the top of protected pages.
 *
 * Example:
 *   $allowed_user_types = ['admin'];
 *   require_once __DIR__ . '/../app/includes/auth_check.php';
 */
function auth_check(array|string $allowedUserTypes): array
{
    $allowedUserTypes = is_array($allowedUserTypes) ? $allowedUserTypes : [$allowedUserTypes];
    return require_roles($allowedUserTypes);
}

if (isset($allowed_user_types)) {
    auth_check($allowed_user_types);
}
