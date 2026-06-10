<?php

/**
 * Database access layer.
 *
 * This is intentionally tolerant of different local setups so the project
 * runs on any teammate's machine with zero manual configuration:
 *
 *   1. It auto-detects the MySQL/MariaDB port. XAMPP installs vary between
 *      3306 (default) and 3307, so we try the configured port first and then
 *      fall back to the common alternatives.
 *   2. It creates the `krishiconnect` database automatically if it does not
 *      exist yet (fresh clone), then loads the schema and seed data.
 *
 * Everything can still be overridden with the KRISHI_DB_* environment
 * variables (see config.php).
 */

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = require __DIR__ . '/config.php';
    $db = $config['db'];

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 3,
    ];

    $lastError = null;
    foreach (db_candidate_ports($db['port']) as $port) {
        try {
            try {
                $pdo = db_connect($db, $port, $options, true);
            } catch (PDOException $e) {
                // Server reachable on this port but the database is missing
                // (typical on a fresh clone). Create it, then reconnect.
                if (!db_is_unknown_database($e)) {
                    throw $e;
                }
                $server = db_connect($db, $port, $options, false);
                db_create_database($server, $db['name'], $db['charset']);
                $pdo = db_connect($db, $port, $options, true);
            }

            db_ensure_schema($pdo);
            return $pdo;
        } catch (PDOException $e) {
            // Connection refused / access denied on this port. Remember the
            // error and try the next candidate.
            $lastError = $e;
        }
    }

    db_connection_failed($db, db_candidate_ports($db['port']), $lastError);
}

/**
 * Build the ordered list of ports to try: the configured one first, then the
 * usual XAMPP defaults. Duplicates are removed while preserving order.
 *
 * @return string[]
 */
function db_candidate_ports(string $configured): array
{
    $ports = [];
    foreach ([$configured, '3306', '3307', '3308'] as $port) {
        $port = trim((string) $port);
        if ($port !== '' && !in_array($port, $ports, true)) {
            $ports[] = $port;
        }
    }

    return $ports;
}

function db_connect(array $db, string $port, array $options, bool $withDatabase): PDO
{
    if ($withDatabase) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $db['host'],
            $port,
            $db['name'],
            $db['charset']
        );
    } else {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;charset=%s',
            $db['host'],
            $port,
            $db['charset']
        );
    }

    return new PDO($dsn, $db['user'], $db['pass'], $options);
}

function db_is_unknown_database(PDOException $e): bool
{
    // MySQL/MariaDB error 1049 = "Unknown database".
    return ($e->errorInfo[1] ?? null) === 1049
        || str_contains($e->getMessage(), 'Unknown database');
}

function db_create_database(PDO $server, string $name, string $charset): void
{
    // Identifiers cannot be bound as parameters, so sanitise defensively.
    $safeName = preg_replace('/[^A-Za-z0-9_]/', '', $name);
    $safeCharset = preg_replace('/[^A-Za-z0-9_]/', '', $charset);
    $server->exec("CREATE DATABASE IF NOT EXISTS `{$safeName}` CHARACTER SET {$safeCharset}");
}

/**
 * Load the schema and seed data the first time we see an empty database.
 */
function db_ensure_schema(PDO $pdo): void
{
    $hasUsers = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
    if ($hasUsers) {
        return;
    }

    $sqlDir = dirname(__DIR__) . '/sql';
    db_run_sql_file($pdo, $sqlDir . '/schema.sql');
    db_run_sql_file($pdo, $sqlDir . '/seed.sql');
}

function db_run_sql_file(PDO $pdo, string $file): void
{
    if (!is_file($file)) {
        return;
    }

    $sql = file_get_contents($file);
    if ($sql === false || trim($sql) === '') {
        return;
    }

    // The schema/seed files use ';' only as statement terminators (never
    // inside string literals), so a simple split is safe here.
    foreach (explode(';', $sql) as $statement) {
        $statement = trim($statement);
        if ($statement !== '') {
            $pdo->exec($statement);
        }
    }
}

function db_connection_failed(array $db, array $ports, ?PDOException $error): void
{
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }

    $portList = htmlspecialchars(implode(', ', $ports), ENT_QUOTES);
    $host = htmlspecialchars($db['host'], ENT_QUOTES);
    $detail = htmlspecialchars($error ? $error->getMessage() : 'No MySQL server responded.', ENT_QUOTES);

    echo <<<HTML
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Database connection failed</title>
    <style>
        body { font-family: system-ui, Arial, sans-serif; background: #f7f7f7; color: #222; margin: 0; padding: 3rem; }
        .box { max-width: 640px; margin: 0 auto; background: #fff; border: 1px solid #e2e2e2; border-radius: 12px; padding: 2rem; box-shadow: 0 8px 24px rgba(0,0,0,.06); }
        h1 { color: #b00020; font-size: 1.4rem; margin-top: 0; }
        code { background: #f0f0f0; padding: .1rem .35rem; border-radius: 4px; }
        ol { line-height: 1.7; }
        .detail { margin-top: 1.5rem; font-size: .85rem; color: #777; word-break: break-word; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Could not connect to the database</h1>
        <p>KrishiConnect tried to reach MySQL on <strong>{$host}</strong> using port(s) <strong>{$portList}</strong> but none responded.</p>
        <ol>
            <li>Open the <strong>XAMPP Control Panel</strong> and make sure <strong>MySQL</strong> is running (green).</li>
            <li>If your MySQL uses a different port, set it once: <code>set KRISHI_DB_PORT=YOUR_PORT</code> before starting the server.</li>
            <li>If your MySQL root user has a password, set <code>KRISHI_DB_PASS</code> as well.</li>
        </ol>
        <p class="detail">Technical detail: {$detail}</p>
    </div>
</body>
</html>
HTML;

    exit;
}
