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
 *   2. It creates the `krishiconnect_db` database automatically if it does not
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
    if (!$hasUsers) {
        $sqlDir = dirname(__DIR__) . '/sql';
        db_run_sql_file($pdo, $sqlDir . '/schema.sql');
        db_run_sql_file($pdo, $sqlDir . '/seed.sql');
    }

    db_ensure_security_schema($pdo);
    db_ensure_demo_data($pdo);
    db_ensure_performance_indexes($pdo);
}

function db_column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) AS total
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = ?
           AND COLUMN_NAME = ?'
    );
    $stmt->execute([$table, $column]);

    return (int)($stmt->fetch()['total'] ?? 0) > 0;
}

function db_table_exists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) AS total
         FROM INFORMATION_SCHEMA.TABLES
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = ?'
    );
    $stmt->execute([$table]);

    return (int)($stmt->fetch()['total'] ?? 0) > 0;
}

function db_index_exists(PDO $pdo, string $table, string $index): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) AS total
         FROM INFORMATION_SCHEMA.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = ?
           AND INDEX_NAME = ?'
    );
    $stmt->execute([$table, $index]);

    return (int)($stmt->fetch()['total'] ?? 0) > 0;
}

/**
 * Composite indexes for the hot read paths (marketplace listing, message
 * threads, notification feed, repayment ledger). Created idempotently so this
 * is safe on both fresh and existing databases, and across MySQL/MariaDB which
 * differ on "CREATE INDEX IF NOT EXISTS" support.
 */
function db_ensure_performance_indexes(PDO $pdo): void
{
    $indexes = [
        ['products', 'idx_products_listing', '(status, product_status, created_at)'],
        ['messages', 'idx_messages_conversation_created', '(conversation_id, created_at)'],
        ['notifications', 'idx_notifications_user_feed', '(user_id, is_read, created_at)'],
        ['loan_payments', 'idx_loan_payments_loan_status', '(loan_id, status)'],
    ];

    foreach ($indexes as [$table, $index, $columns]) {
        if (db_table_exists($pdo, $table) && !db_index_exists($pdo, $table, $index)) {
            $pdo->exec("CREATE INDEX {$index} ON {$table} {$columns}");
        }
    }
}

function db_ensure_security_schema(PDO $pdo): void
{
    if (!db_column_exists($pdo, 'users', 'admin_pin_hash')) {
        $pdo->exec('ALTER TABLE users ADD COLUMN admin_pin_hash VARCHAR(255) DEFAULT NULL AFTER password_hash');
    }

    if (!db_column_exists($pdo, 'products', 'product_status')) {
        $pdo->exec('ALTER TABLE products ADD COLUMN product_status ENUM("pending","approved","rejected") NOT NULL DEFAULT "pending" AFTER rating');
        $pdo->exec('UPDATE products SET product_status = "approved" WHERE status = "active"');
    }

    if (!db_table_exists($pdo, 'security_logs')) {
        $pdo->exec(
            'CREATE TABLE security_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                action VARCHAR(80) NOT NULL,
                details TEXT NULL,
                ip_address VARCHAR(45) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_security_logs_action_created (action, created_at),
                INDEX idx_security_logs_user_created (user_id, created_at),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            )'
        );
    }
}

/**
 * Idempotent, runs-on-every-boot migration + demo seeding.
 *
 * It guarantees the columns the newer PHP pages rely on exist, and that the
 * demo accounts (10 farmers, a buyer, a finance officer) plus a populated
 * marketplace are present on any machine — fresh clone or existing database.
 */
function db_ensure_demo_data(PDO $pdo): void
{
    db_ensure_demo_columns($pdo);
    db_ensure_buyer_addresses($pdo);
    db_ensure_payment_columns($pdo);
    db_seed_demo_users($pdo);
    db_seed_sample_products($pdo);
}

/**
 * Per-order gateway transaction id, used to correlate SSLCommerz callbacks
 * (which only carry the transaction id, never the buyer's session) back to the
 * order. Added idempotently so it works on existing databases too.
 */
function db_ensure_payment_columns(PDO $pdo): void
{
    if (!db_column_exists($pdo, 'orders', 'tran_id')) {
        $pdo->exec('ALTER TABLE orders ADD COLUMN tran_id VARCHAR(64) DEFAULT NULL');
    }
}

/**
 * Saved delivery details so a buyer only types their address/phone once.
 * One row per buyer; created on demand and kept current at each checkout.
 */
function db_ensure_buyer_addresses(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS buyer_addresses (
            user_id INT PRIMARY KEY,
            full_name VARCHAR(120) DEFAULT NULL,
            phone VARCHAR(40) DEFAULT NULL,
            email VARCHAR(160) DEFAULT NULL,
            street VARCHAR(200) DEFAULT NULL,
            city VARCHAR(120) DEFAULT NULL,
            state VARCHAR(120) DEFAULT NULL,
            postal VARCHAR(40) DEFAULT NULL,
            landmark VARCHAR(200) DEFAULT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )'
    );
}

function db_ensure_demo_columns(PDO $pdo): void
{
    // `rejection_reason` is added last and used as the "migration complete"
    // marker so the steady-state cost of this function is a single query.
    if (db_column_exists($pdo, 'loan_applications', 'rejection_reason')) {
        return;
    }

    $productColumns = [
        'local_name' => 'ALTER TABLE products ADD COLUMN local_name VARCHAR(120) DEFAULT NULL AFTER name',
        'image_url'  => 'ALTER TABLE products ADD COLUMN image_url VARCHAR(255) DEFAULT NULL AFTER description',
        'is_organic' => 'ALTER TABLE products ADD COLUMN is_organic TINYINT(1) NOT NULL DEFAULT 0',
        'is_featured' => 'ALTER TABLE products ADD COLUMN is_featured TINYINT(1) NOT NULL DEFAULT 0',
        'updated_at' => 'ALTER TABLE products ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
    ];
    foreach ($productColumns as $column => $sql) {
        if (!db_column_exists($pdo, 'products', $column)) {
            $pdo->exec($sql);
        }
    }

    $farmerColumns = [
        'bank_name'      => 'ALTER TABLE farmer_profiles ADD COLUMN bank_name VARCHAR(120) DEFAULT NULL',
        'bank_account'   => 'ALTER TABLE farmer_profiles ADD COLUMN bank_account VARCHAR(120) DEFAULT NULL',
        'bank_branch'    => 'ALTER TABLE farmer_profiles ADD COLUMN bank_branch VARCHAR(120) DEFAULT NULL',
        'monthly_income' => 'ALTER TABLE farmer_profiles ADD COLUMN monthly_income DECIMAL(12,2) NOT NULL DEFAULT 0',
    ];
    foreach ($farmerColumns as $column => $sql) {
        if (!db_column_exists($pdo, 'farmer_profiles', $column)) {
            $pdo->exec($sql);
        }
    }

    // Completion marker — keep this ALTER last.
    $pdo->exec('ALTER TABLE loan_applications ADD COLUMN rejection_reason VARCHAR(255) DEFAULT NULL AFTER status');
}

/**
 * The demo farmers/buyer/finance officer used by the login "Demo Credentials"
 * card. All share the password 12345678.
 */
function db_seed_demo_users(PDO $pdo): void
{
    // Sentinel: if the finance demo account exists, the batch already ran.
    $exists = $pdo->query("SELECT id FROM users WHERE email = 'finance1@krishiconnect.com' LIMIT 1")->fetch();
    if ($exists) {
        return;
    }

    $passwordHash = password_hash('12345678', PASSWORD_DEFAULT);

    // [full_name, email, phone, land_area (acres), location, monthly_income]
    $farmers = [
        ['Rahim Uddin',     'farmer1@krishiconnect.com',  '01711000001', 2.5, 'Bogura',      20000],
        ['Karim Mia',       'farmer2@krishiconnect.com',  '01711000002', 3.0, 'Rajshahi',    30000],
        ['Abdul Karim',     'farmer3@krishiconnect.com',  '01711000003', 1.8, 'Dinajpur',    18000],
        ['Jamal Hossain',   'farmer4@krishiconnect.com',  '01711000004', 4.2, 'Jessore',     45000],
        ['Nurul Islam',     'farmer5@krishiconnect.com',  '01711000005', 5.0, 'Sylhet',      60000],
        ['Shahidul Alam',   'farmer6@krishiconnect.com',  '01711000006', 2.0, 'Rangpur',     24000],
        ['Mofiz Uddin',     'farmer7@krishiconnect.com',  '01711000007', 3.5, 'Cumilla',     38000],
        ['Sultana Begum',   'farmer8@krishiconnect.com',  '01711000008', 1.5, 'Khulna',      15000],
        ['Ayesha Siddiqua', 'farmer9@krishiconnect.com',  '01711000009', 2.8, 'Mymensingh',  52000],
        ['Rahima Khatun',   'farmer10@krishiconnect.com', '01711000010', 6.0, 'Barisal',     75000],
    ];

    $insertUser = $pdo->prepare(
        'INSERT INTO users (full_name, email, phone, role, password_hash, status)
         VALUES (?, ?, ?, ?, ?, "active")'
    );
    $insertFarmerProfile = $pdo->prepare(
        'INSERT INTO farmer_profiles (user_id, farm_name, location, land_area, soil_type, irrigation, kyc_status, bank_name, bank_account, bank_branch, monthly_income)
         VALUES (?, ?, ?, ?, ?, ?, "verified", ?, ?, ?, ?)'
    );

    foreach ($farmers as $index => [$name, $email, $phone, $landArea, $location, $income]) {
        if ($pdo->query('SELECT id FROM users WHERE email = ' . $pdo->quote($email) . ' LIMIT 1')->fetch()) {
            continue;
        }
        $insertUser->execute([$name, $email, $phone, 'farmer', $passwordHash]);
        $farmerId = (int)$pdo->lastInsertId();
        $insertFarmerProfile->execute([
            $farmerId,
            $name . "'s Farm",
            $location,
            $landArea,
            ['Loamy', 'Clay', 'Sandy Loam', 'Silt'][$index % 4],
            ['Tube well', 'Canal', 'Rain-fed', 'Drip'][$index % 4],
            ['Agrani Bank', 'Sonali Bank', 'Krishi Bank', 'Janata Bank'][$index % 4],
            '20' . str_pad((string)($index + 1), 8, '0', STR_PAD_LEFT),
            $location . ' Branch',
            $income,
        ]);
    }

    // Demo buyer
    if (!$pdo->query("SELECT id FROM users WHERE email = 'buyer1@krishiconnect.com' LIMIT 1")->fetch()) {
        $insertUser->execute(['Anik Chowdhury', 'buyer1@krishiconnect.com', '01822000001', 'buyer', $passwordHash]);
        $buyerId = (int)$pdo->lastInsertId();
        $pdo->prepare('INSERT INTO buyer_profiles (user_id, company_name, address) VALUES (?, ?, ?)')
            ->execute([$buyerId, 'Fresh Basket Trading', 'Mirpur, Dhaka']);
    }

    // Demo finance officer
    if (!$pdo->query("SELECT id FROM users WHERE email = 'finance1@krishiconnect.com' LIMIT 1")->fetch()) {
        $insertUser->execute(['Tahmina Akter', 'finance1@krishiconnect.com', '01933000001', 'finance', $passwordHash]);
        $financeId = (int)$pdo->lastInsertId();
        $pdo->prepare('INSERT INTO finance_profiles (user_id, institution, designation) VALUES (?, ?, ?)')
            ->execute([$financeId, 'KrishiConnect Microfinance', 'Loan Officer']);
    }
}

/**
 * Seed a realistic, approved-and-live marketplace catalog (16 items across the
 * four categories). Resolves category and farmer foreign keys dynamically so it
 * works regardless of the auto-increment ids on a given machine.
 */
function db_seed_sample_products(PDO $pdo): void
{
    // Sentinel: skip if the catalog has already been seeded.
    if ($pdo->query("SELECT id FROM products WHERE name = 'Aromatic Kalijira Rice' LIMIT 1")->fetch()) {
        return;
    }

    $categoryIds = [];
    foreach ($pdo->query('SELECT id, name FROM categories')->fetchAll() as $row) {
        $categoryIds[strtolower($row['name'])] = (int)$row['id'];
    }

    $farmerIds = [];
    foreach ($pdo->query("SELECT id, email FROM users WHERE role = 'farmer' ORDER BY id")->fetchAll() as $row) {
        $farmerIds[] = (int)$row['id'];
    }
    if (!$farmerIds || !$categoryIds) {
        return;
    }
    $pickFarmer = static fn(int $i): int => $farmerIds[$i % count($farmerIds)];

    // [category, name, local_name, variety, description, price, unit, qty, image_url, is_organic, is_featured, rating]
    $products = [
        // --- Vegetables ---
        ['Vegetables', 'Premium Bitter Gourd', 'Korola', 'Hybrid', 'Tender, dark-green bitter gourd grown in the fertile fields of Jessore. Hand-picked for export-grade quality.', 75, 'kg', 180, 'images/vegetables/bitter gourd.jpg', 1, 1, 4.6],
        ['Vegetables', 'Fresh Green Okra', 'Dherosh', 'Local', 'Crisp, fibre-free okra harvested daily from Bogura. Ideal for frying and curries.', 50, 'kg', 240, 'images/vegetables/okra.jpg', 0, 0, 4.2],
        ['Vegetables', 'Snow White Cauliflower', 'Fulkopi', 'Winter', 'Compact, milky-white cauliflower heads from the cool highlands of Rangpur.', 45, 'kg', 300, 'images/vegetables/cauliflower.jpg', 0, 1, 4.4],
        ['Vegetables', 'Red Amaranth Spinach', 'Lal Shak', 'Organic', 'Iron-rich red spinach grown organically near Mymensingh without chemical fertilisers.', 30, 'bunch', 150, 'images/vegetables/spinach.jpg', 1, 0, 4.5],
        // --- Fruits ---
        ['Fruits', 'Himsagar Mango', 'Aam', 'Himsagar', 'The king of mangoes from Rajshahi — fibreless, fragrant, and intensely sweet. Naturally ripened.', 180, 'kg', 500, 'images/vegetables/himsagar.jpg', 0, 1, 4.9],
        ['Fruits', 'Sweet Litchi', 'Lichu', 'China-3', 'Juicy, thin-skinned litchi from Dinajpur orchards, picked at peak sweetness.', 250, 'kg', 220, 'https://placehold.co/600x400/e8a317/ffffff?text=Lichu', 0, 1, 4.7],
        ['Fruits', 'Sagar Banana', 'Sagar Kola', 'Sagar', 'Premium dessert bananas from Narsingdi — smooth, creamy, and perfectly sweet.', 90, 'dozen', 400, 'images/vegetables/L14Av.jpg', 1, 0, 4.3],
        ['Fruits', 'Whole Jackfruit', 'Kathal', 'Local', 'Bangladesh national fruit — large, aromatic jackfruit from Gazipur, sold by the piece.', 300, 'piece', 80, 'images/vegetables/jack fruit.jpg', 0, 0, 4.1],
        // --- Grains ---
        ['Grains', 'Aromatic Kalijira Rice', 'Kalijira Chal', 'Kalijira', 'Fine, fragrant baby rice from Dinajpur — the premium choice for polao and payesh.', 140, 'kg', 600, 'images/vegetables/kalijira chal.jpg', 1, 1, 4.8],
        ['Grains', 'Miniket Rice', 'Miniket Chal', 'Miniket', 'Slim, polished everyday rice milled in Naogaon. Clean, sortexed, and ready to cook.', 72, 'kg', 1000, 'images/vegetables/miniket chal.jpg', 0, 1, 4.4],
        ['Grains', 'Red Lentil', 'Masoor Dal', 'Local', 'Plump, fast-cooking red lentils from Faridpur — a daily protein staple.', 130, 'kg', 350, 'images/vegetables/masoor dal.jpg', 0, 0, 4.5],
        ['Grains', 'Whole Wheat', 'Gom', 'Local', 'Stone-ground-ready wheat grain from Thakurgaon, perfect for fresh atta and ruti.', 55, 'kg', 800, 'images/vegetables/wheat.jpg', 0, 0, 4.0],
        // --- Spices ---
        ['Spices', 'Turmeric Powder', 'Holud', 'High Curcumin', 'Sun-dried, stone-milled turmeric from Bogura with deep colour and high curcumin content.', 220, 'kg', 120, 'images/vegetables/holud.jpg', 1, 1, 4.7],
        ['Spices', 'Dried Red Chili', 'Shukna Morich', 'Local', 'Fiery, sun-dried red chillies from Jessore — bold heat and rich aroma.', 320, 'kg', 90, 'images/vegetables/dried-red-chilied-peppers.jpg', 0, 1, 4.6],
        ['Spices', 'Fresh Ginger', 'Ada', 'Local', 'Pungent, juicy ginger rhizomes from the hills of Sylhet. Cleaned and graded.', 200, 'kg', 160, 'images/vegetables/ginger.jpg', 1, 0, 4.4],
        ['Spices', 'Premium Garlic', 'Roshun', 'Single Clove', 'Strong, single-clove (mono) garlic from Natore — prized for its concentrated flavour.', 240, 'kg', 140, 'images/vegetables/garlic-growing-guide_0.jpg', 0, 0, 4.3],
    ];

    $insert = $pdo->prepare(
        'INSERT INTO products
            (farmer_id, category_id, name, local_name, variety, description, image_url, price, unit, quantity_available, rating, product_status, status, is_organic, is_featured)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "approved", "active", ?, ?)'
    );
    $insertImage = $pdo->prepare('INSERT INTO product_images (product_id, image_path, is_primary) VALUES (?, ?, 1)');

    foreach ($products as $i => $p) {
        [$category, $name, $localName, $variety, $description, $price, $unit, $qty, $imageUrl, $organic, $featured, $rating] = $p;
        $categoryId = $categoryIds[strtolower($category)] ?? null;
        if ($categoryId === null) {
            continue;
        }

        $insert->execute([
            $pickFarmer($i), $categoryId, $name, $localName, $variety, $description,
            $imageUrl, $price, $unit, $qty, $rating, $organic, $featured,
        ]);

        // Local asset paths also get a product_images row for templates that
        // read the primary image relationship directly.
        if (!preg_match('#^https?://#i', $imageUrl)) {
            $insertImage->execute([(int)$pdo->lastInsertId(), $imageUrl]);
        }
    }
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
