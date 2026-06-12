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

/**
 * Fully-qualified URL (scheme + host + path). Payment gateways require absolute
 * callback URLs, unlike the relative paths url() produces.
 */
function absolute_url(string $path): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    return $scheme . '://' . $host . url($path);
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

/**
 * Resolve the best image source for a product row. Supports both the legacy
 * primary-image relationship (`image_path`) and the newer `image_url` column,
 * and leaves absolute URLs (e.g. Unsplash/placehold.co) untouched.
 */
function product_image_src(?array $product, string $fallback = 'images/vegetables/tomato.jpg'): string
{
    foreach ([$product['image_path'] ?? null, $product['image_url'] ?? null] as $candidate) {
        if (!$candidate) {
            continue;
        }
        if (preg_match('#^https?://#i', $candidate)) {
            return $candidate;
        }
        // Local asset: URL-encode each segment so filenames with spaces
        // (e.g. "bitter gourd.jpg") resolve correctly in the browser.
        return asset_url(str_replace('%2F', '/', rawurlencode($candidate)));
    }

    return asset_url(str_replace('%2F', '/', rawurlencode($fallback)));
}

/* ---------------------------------------------------------------------------
 * Loan repayment & eligibility helpers
 * ------------------------------------------------------------------------- */

/**
 * Outstanding due for a farmer = sum of every loan_payments installment that
 * has not been marked "paid" across their active loans. Falls/decreases
 * automatically as installments are repaid.
 */
function farmer_total_due(int $farmerId): float
{
    $stmt = db()->prepare(
        'SELECT COALESCE(SUM(lp.amount), 0) AS due
         FROM loan_payments lp
         JOIN loans l ON l.id = lp.loan_id
         WHERE l.farmer_id = ? AND l.status = "active" AND lp.status <> "paid"'
    );
    $stmt->execute([$farmerId]);

    return (float)($stmt->fetch()['due'] ?? 0);
}

/**
 * Per-application loan amount limits (single source of truth shared by the
 * loan application form, its client-side validation, and the server-side
 * processor). Returns [minimum, maximum].
 */
function loan_amount_bounds(): array
{
    return [5000.0, 150000.0];
}

/** Maximum outstanding due a farmer may carry, by monthly income tier. */
function loan_due_limit_for_income(float $monthlyIncome): float
{
    if ($monthlyIncome <= 25000) {
        return 50000;
    }
    if ($monthlyIncome < 50000) {
        return 70000;
    }

    return 100000;
}

/**
 * Evaluate whether a farmer may take on a new loan given the business rules:
 *   - Absolute rule: total due > 100,000 blocks any new loan.
 *   - Income-tiered ceiling caps the total outstanding due.
 *
 * @return array{total_due:float,max_due:float,absolute_cap:float,headroom:float,can_apply:bool,reason:string}
 */
function loan_eligibility(int $farmerId, float $monthlyIncome): array
{
    $absoluteCap = 100000.0;
    $totalDue = farmer_total_due($farmerId);
    $maxDue = loan_due_limit_for_income($monthlyIncome);
    $effectiveCap = min($maxDue, $absoluteCap);
    $headroom = max(0.0, $effectiveCap - $totalDue);

    $canApply = true;
    $reason = '';
    if ($totalDue > $absoluteCap) {
        $canApply = false;
        $reason = 'Your total outstanding due exceeds BDT 1,00,000, so new loans are blocked until you reduce it.';
    } elseif ($totalDue >= $maxDue) {
        $canApply = false;
        $reason = sprintf(
            'Your outstanding due (BDT %s) has reached the BDT %s ceiling for your income tier.',
            number_format($totalDue),
            number_format($maxDue)
        );
    }

    return [
        'total_due' => $totalDue,
        'max_due' => $maxDue,
        'absolute_cap' => $absoluteCap,
        'headroom' => $headroom,
        'can_apply' => $canApply,
        'reason' => $reason,
    ];
}

/** Latest stored monthly income for a farmer (profile first, then last application). */
function farmer_monthly_income(int $farmerId): float
{
    $stmt = db()->prepare('SELECT monthly_income FROM farmer_profiles WHERE user_id = ?');
    $stmt->execute([$farmerId]);
    $profileIncome = (float)($stmt->fetch()['monthly_income'] ?? 0);
    if ($profileIncome > 0) {
        return $profileIncome;
    }

    $stmt = db()->prepare('SELECT monthly_income FROM loan_applications WHERE farmer_id = ? ORDER BY submitted_at DESC LIMIT 1');
    $stmt->execute([$farmerId]);
    $raw = $stmt->fetch()['monthly_income'] ?? '';

    return (float)preg_replace('/[^0-9.]/', '', (string)$raw);
}
