<?php

/**
 * Thin SSLCommerz client (sandbox + live) using plain cURL — no Composer
 * dependency. Drives the hosted-checkout flow:
 *
 *   initiate -> redirect buyer to GatewayPageURL -> gateway POSTs back to our
 *   success/fail/cancel URLs -> we re-validate server-to-server with val_id
 *   before marking the order paid.
 *
 * The app NEVER trusts the amount/status posted by the browser; the order is
 * only marked paid after the validation API confirms a VALID/VALIDATED payment
 * for the matching transaction id and amount.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

function sslcommerz_config(): array
{
    $config = app_config();
    return ($config['sslcommerz'] ?? []) + ['store_id' => '', 'store_pass' => '', 'sandbox' => true];
}

/** True only once real credentials are configured; otherwise checkout uses the mock. */
function sslcommerz_enabled(): bool
{
    $config = sslcommerz_config();
    return trim((string)$config['store_id']) !== '' && trim((string)$config['store_pass']) !== '';
}

function sslcommerz_base_url(): string
{
    return !empty(sslcommerz_config()['sandbox'])
        ? 'https://sandbox.sslcommerz.com'
        : 'https://securepay.sslcommerz.com';
}

/**
 * @return array{ok:bool,redirect?:string,error?:string}
 */
function sslcommerz_initiate(array $payload): array
{
    $config = sslcommerz_config();
    $fields = $payload + [
        'store_id' => $config['store_id'],
        'store_passwd' => $config['store_pass'],
    ];

    $response = sslcommerz_request(sslcommerz_base_url() . '/gwprocess/v4/api.php', $fields);
    if ($response === null) {
        return ['ok' => false, 'error' => 'Could not reach the payment gateway.'];
    }

    if (($response['status'] ?? '') === 'SUCCESS' && !empty($response['GatewayPageURL'])) {
        return ['ok' => true, 'redirect' => (string)$response['GatewayPageURL']];
    }

    return ['ok' => false, 'error' => (string)($response['failedreason'] ?? 'Gateway initialisation failed.')];
}

/**
 * Server-to-server validation of a completed transaction.
 *
 * @return array|null The decoded validation response, or null on transport failure.
 */
function sslcommerz_validate(string $valId): ?array
{
    $config = sslcommerz_config();
    $query = http_build_query([
        'val_id' => $valId,
        'store_id' => $config['store_id'],
        'store_passwd' => $config['store_pass'],
        'v' => 1,
        'format' => 'json',
    ]);

    return sslcommerz_request(sslcommerz_base_url() . '/validator/api/validationserverAPI.php?' . $query, null);
}

/**
 * Confirm a validation response genuinely matches the order before trusting it.
 */
function sslcommerz_validation_matches(?array $validation, array $order): bool
{
    if (!$validation) {
        return false;
    }

    $status = $validation['status'] ?? '';
    $amountOk = abs((float)($validation['amount'] ?? 0) - (float)$order['total_amount']) < 0.01;

    return in_array($status, ['VALID', 'VALIDATED'], true)
        && ($validation['tran_id'] ?? '') === ($order['tran_id'] ?? '~')
        && strtoupper((string)($validation['currency'] ?? '')) === 'BDT'
        && $amountOk;
}

/**
 * Idempotently mark an order paid + record the payment. Safe to call from both
 * the browser success redirect and the server-to-server IPN.
 */
function sslcommerz_mark_order_paid(PDO $pdo, array $order, string $method, string $transactionRef): void
{
    $orderId = (int)$order['id'];

    $check = $pdo->prepare('SELECT payment_status FROM orders WHERE id = ?');
    $check->execute([$orderId]);
    if (($check->fetch()['payment_status'] ?? '') === 'paid') {
        return; // already processed
    }

    $pdo->prepare(
        'INSERT INTO payments (order_id, amount, method, status, transaction_ref, paid_at)
         VALUES (?, ?, ?, "success", ?, NOW())'
    )->execute([
        $orderId,
        (float)$order['total_amount'],
        $method !== '' ? $method : 'SSLCommerz',
        $transactionRef !== '' ? $transactionRef : (string)($order['tran_id'] ?? ''),
    ]);

    $pdo->prepare('UPDATE orders SET payment_status = "paid", status = "confirmed", payment_method = ? WHERE id = ?')
        ->execute([$method !== '' ? $method : 'SSLCommerz', $orderId]);
}

/** Record a failed attempt (for the audit trail) without touching the order state. */
function sslcommerz_record_failure(PDO $pdo, array $order, string $reason): void
{
    $pdo->prepare(
        'INSERT INTO payments (order_id, amount, method, status, transaction_ref)
         VALUES (?, ?, "SSLCommerz", "failed", ?)'
    )->execute([
        (int)$order['id'],
        (float)$order['total_amount'],
        substr($reason, 0, 120),
    ]);
}

/* ---------------------------------------------------------------------------
 * Loan repayment helpers.
 *
 * These reuse the exact same gateway transport/validation as the order flow
 * above, but key off `loan_payments` instead of `orders`. The buyer checkout
 * path is intentionally left untouched.
 * ------------------------------------------------------------------------- */

/**
 * Generic server-to-server validation check: the response must be
 * VALID/VALIDATED, for the same tran_id, in BDT, and for the expected amount.
 */
function sslcommerz_validation_ok(?array $validation, string $tranId, float $amount): bool
{
    if (!$validation) {
        return false;
    }

    $status = $validation['status'] ?? '';
    $amountOk = abs((float)($validation['amount'] ?? 0) - $amount) < 0.01;

    return in_array($status, ['VALID', 'VALIDATED'], true)
        && ($validation['tran_id'] ?? '') === $tranId
        && strtoupper((string)($validation['currency'] ?? '')) === 'BDT'
        && $amountOk;
}

/**
 * Idempotently mark a single loan installment paid, then close the loan once
 * every installment is settled (mirrors the direct-repay logic in
 * loan_repay.php). Safe to call from both the success redirect and the IPN.
 *
 * @param array $payment Row with at least `id` and `loan_id`.
 */
function sslcommerz_mark_loan_payment_paid(PDO $pdo, array $payment): void
{
    $paymentId = (int)$payment['id'];
    $loanId = (int)$payment['loan_id'];

    $check = $pdo->prepare('SELECT status FROM loan_payments WHERE id = ?');
    $check->execute([$paymentId]);
    if (($check->fetch()['status'] ?? '') === 'paid') {
        return; // already processed
    }

    $pdo->prepare('UPDATE loan_payments SET status = "paid", paid_at = ? WHERE id = ?')
        ->execute([date('Y-m-d'), $paymentId]);

    $remaining = $pdo->prepare('SELECT COUNT(*) AS c FROM loan_payments WHERE loan_id = ? AND status <> "paid"');
    $remaining->execute([$loanId]);
    if ((int)($remaining->fetch()['c'] ?? 0) === 0) {
        $pdo->prepare('UPDATE loans SET status = "closed" WHERE id = ?')->execute([$loanId]);
    }
}

/**
 * Perform a cURL request and decode the JSON body.
 *
 * @param array|null $postFields POST form fields, or null for a GET request.
 */
function sslcommerz_request(string $url, ?array $postFields): ?array
{
    if (!function_exists('curl_init')) {
        return null;
    }

    $ch = curl_init();
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 15,
        // NOTE: peer verification is relaxed only for the sandbox because XAMPP
        // on Windows frequently ships without a configured CA bundle. The live
        // endpoints keep full certificate verification.
        CURLOPT_SSL_VERIFYPEER => empty(sslcommerz_config()['sandbox']),
        CURLOPT_SSL_VERIFYHOST => 2,
    ];
    if ($postFields !== null) {
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = http_build_query($postFields);
    }
    curl_setopt_array($ch, $options);

    $body = curl_exec($ch);
    $failed = $body === false || curl_errno($ch) !== 0;
    curl_close($ch);

    if ($failed) {
        return null;
    }

    $decoded = json_decode((string)$body, true);
    return is_array($decoded) ? $decoded : null;
}
