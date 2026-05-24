<?php

function app_config(): array
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/config.php';
    }

    return $config;
}

function base_url(): string
{
    $base = app_config()['base_url'] ?? '';
    if ($base === null) {
        return detect_base_url();
    }

    return rtrim($base, '/');
}

function detect_base_url(): string
{
    if (PHP_SAPI === 'cli-server') {
        return '';
    }

    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    foreach (['/pages/', '/app/'] as $segment) {
        $position = strpos($script, $segment);
        if ($position !== false) {
            return rtrim(substr($script, 0, $position), '/');
        }
    }

    $directory = rtrim(str_replace('\\', '/', dirname($script)), '/');
    return $directory === '/' ? '' : $directory;
}

function url(string $path): string
{
    $base = base_url();
    $path = '/' . ltrim($path, '/');
    return $base . $path;
}

function asset_url(string $path): string
{
    return url($path);
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}
