<?php
require_once __DIR__ . '/../app/includes/auth.php';

if (empty($_SESSION['user_type']) && ($_SESSION['role'] ?? '') === 'admin') {
    $_SESSION['user_type'] = 'admin';
}

$hasAdminSession = ($_SESSION['logged_in'] ?? false) === true
    && ($_SESSION['user_type'] ?? '') === 'admin';

if (!$hasAdminSession) {
    redirect('pages/admin_login.php?error=unauthorized');
}

$admin = current_user();
if (!$admin || $admin['role'] !== 'admin' || $admin['status'] !== 'active') {
    destroy_current_session();
    redirect('pages/admin_login.php?error=unauthorized');
}

$pdo = db();
$flash = $_GET['status'] ?? '';

function h(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function money_fmt(mixed $value): string
{
    return 'BDT ' . number_format((float)$value, 2);
}

function table_exists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) AS total
         FROM INFORMATION_SCHEMA.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    $stmt->execute([$table]);

    return (int)($stmt->fetch()['total'] ?? 0) > 0;
}

function column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) AS total
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$table, $column]);

    return (int)($stmt->fetch()['total'] ?? 0) > 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token($_POST['csrf_token'] ?? null, 'admin_loan_management', 'admin/loan_management.php?status=csrf');

    $action = $_POST['action'] ?? '';
    $targetUserId = (int)($_POST['target_user_id'] ?? 0);

    if ($action === 'flag_account' && $targetUserId > 0 && $targetUserId !== (int)$admin['id']) {
        $stmt = $pdo->prepare(
            'UPDATE users
             SET status = :status
             WHERE id = :id AND role = :role'
        );
        $stmt->execute([
            ':status' => 'suspended',
            ':id' => $targetUserId,
            ':role' => 'farmer',
        ]);

        write_security_log(
            (int)$admin['id'],
            'loan_account_flagged',
            sprintf('Farmer user ID %d was flagged from admin loan management.', $targetUserId)
        );

        redirect('admin/loan_management.php?status=flagged');
    }

    redirect('admin/loan_management.php?status=ignored');
}

$loanAmountColumn = column_exists($pdo, 'loan_applications', 'amount') ? 'amount' : 'requested_amount';
$durationColumn = column_exists($pdo, 'loan_applications', 'duration_months') ? 'duration_months' : 'tenure_months';

$totalDisbursedStmt = $pdo->query(
    "SELECT COALESCE(SUM({$loanAmountColumn}), 0) AS total
     FROM loan_applications
     WHERE status IN ('disbursed', 'repaid')"
);
$totalDisbursed = (float)($totalDisbursedStmt->fetch()['total'] ?? 0);

$activeLoansStmt = $pdo->query(
    "SELECT COUNT(id) AS total
     FROM loan_applications
     WHERE status = 'disbursed'"
);
$activeLoans = (int)($activeLoansStmt->fetch()['total'] ?? 0);

$repaymentTable = table_exists($pdo, 'loan_repayments') ? 'loan_repayments' : 'loan_payments';
$repaymentAmountColumn = column_exists($pdo, $repaymentTable, 'amount') ? 'amount' : null;

if ($repaymentAmountColumn !== null) {
    $totalRepaidStmt = $pdo->query("SELECT COALESCE(SUM({$repaymentAmountColumn}), 0) AS total FROM {$repaymentTable}");
    $totalRepaid = (float)($totalRepaidStmt->fetch()['total'] ?? 0);
} else {
    $totalRepaid = 0.0;
}

$farmerTable = table_exists($pdo, 'farmers') ? 'farmers' : 'farmer_profiles';
$farmerJoinColumn = $farmerTable === 'farmers' && column_exists($pdo, 'farmers', 'id') && !column_exists($pdo, 'farmers', 'user_id')
    ? 'id'
    : 'user_id';
$farmerDistrictColumn = column_exists($pdo, $farmerTable, 'district')
    ? 'district'
    : (column_exists($pdo, $farmerTable, 'location') ? 'location' : null);
$districtSelect = $farmerDistrictColumn
    ? "f.{$farmerDistrictColumn} AS district_location"
    : "la.location AS district_location";

$rejectionSelect = column_exists($pdo, 'loan_applications', 'rejection_reason')
    ? 'la.rejection_reason AS rejection_reason'
    : "'' AS rejection_reason";

$applicationsStmt = $pdo->query(
    "SELECT la.id,
            la.farmer_id,
            la.{$loanAmountColumn} AS requested_amount,
            la.purpose,
            la.{$durationColumn} AS duration_months,
            la.status,
            {$rejectionSelect},
            u.full_name AS farmer_name,
            {$districtSelect}
     FROM loan_applications la
     JOIN users u ON u.id = la.farmer_id
     LEFT JOIN {$farmerTable} f ON f.{$farmerJoinColumn} = la.farmer_id
     ORDER BY la.submitted_at DESC, la.id DESC"
);
$applications = $applicationsStmt->fetchAll();

// Master Loan Applications is split into two logical sections.
$approvedLoans = array_values(array_filter($applications, static fn(array $a): bool => $a['status'] === 'approved'));
$rejectedLoans = array_values(array_filter($applications, static fn(array $a): bool => $a['status'] === 'rejected'));

$repaymentSelects = ['id'];
foreach (['transaction_id', 'transaction_ref', 'reference_no'] as $candidate) {
    if (column_exists($pdo, $repaymentTable, $candidate)) {
        $repaymentSelects[] = "{$candidate} AS transaction_id";
        break;
    }
}
if (!in_array('transaction_id', array_map(static fn($item) => trim(substr($item, -14)), $repaymentSelects), true)) {
    $repaymentSelects[] = "CONCAT('TXN-', id) AS transaction_id";
}
$repaymentSelects[] = "{$repaymentAmountColumn} AS amount";

foreach (['paid_at', 'created_at', 'payment_date', 'due_date'] as $candidate) {
    if (column_exists($pdo, $repaymentTable, $candidate)) {
        $repaymentDateColumn = $candidate;
        break;
    }
}
$repaymentDateColumn ??= 'id';
$repaymentSelects[] = "{$repaymentDateColumn} AS logged_at";

if ($repaymentTable === 'loan_repayments') {
    $loanIdColumn = column_exists($pdo, $repaymentTable, 'loan_application_id') ? 'loan_application_id' : 'loan_id';
    $repaymentsSql =
        'SELECT ' . implode(', ', $repaymentSelects) . ",
                {$loanIdColumn} AS loan_reference
         FROM {$repaymentTable}
         ORDER BY {$repaymentDateColumn} DESC, id DESC
         LIMIT 10";
} else {
    $repaymentsSql =
        'SELECT ' . implode(', ', $repaymentSelects) . ',
                loan_id AS loan_reference
         FROM loan_payments
         ORDER BY ' . $repaymentDateColumn . ' DESC, id DESC
         LIMIT 10';
}
$repayments = $pdo->query($repaymentsSql)->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Loan Management - KrishiConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg bg-success navbar-dark">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-semibold" href="<?= h(url('pages/admin-dashboard.php')); ?>">KrishiConnect Admin</a>
        <div class="d-flex align-items-center gap-3 text-white">
            <span><?= h($admin['full_name'] ?? 'Admin'); ?></span>
            <a class="btn btn-outline-light btn-sm" href="<?= h(url('app/actions/logout.php')); ?>">Logout</a>
        </div>
    </div>
</nav>

<main class="container-fluid px-4 py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Loan Management</h1>
            <p class="text-secondary mb-0">Platform-wide microfinance oversight and repayment monitoring.</p>
        </div>
        <a class="btn btn-outline-success" href="<?= h(url('pages/admin-dashboard.php')); ?>">
            <i class="fa-solid fa-arrow-left me-1"></i> Dashboard
        </a>
    </div>

    <?php if ($flash === 'flagged'): ?>
        <div class="alert alert-success">Farmer account flagged and security log recorded.</div>
    <?php elseif ($flash === 'csrf'): ?>
        <div class="alert alert-danger">Security token expired. Please try again.</div>
    <?php elseif ($flash === 'ignored'): ?>
        <div class="alert alert-warning">No valid action was submitted.</div>
    <?php endif; ?>

    <section class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-secondary small text-uppercase fw-semibold">Total Disbursed Loans</div>
                    <div class="display-6 fw-bold"><?= h(money_fmt($totalDisbursed)); ?></div>
                    <div class="text-secondary small">Applications marked disbursed or repaid</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-secondary small text-uppercase fw-semibold">Total Repaid Amount</div>
                    <div class="display-6 fw-bold"><?= h(money_fmt($totalRepaid)); ?></div>
                    <div class="text-secondary small">All repayment records across the platform</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-secondary small text-uppercase fw-semibold">Active Loans</div>
                    <div class="display-6 fw-bold"><?= h(number_format($activeLoans)); ?></div>
                    <div class="text-secondary small">Applications currently disbursed</div>
                </div>
            </div>
        </div>
    </section>

    <h2 class="h4 mb-3">Master Loan Applications</h2>

    <!-- Section 1: Approved Loans -->
    <section class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
            <h2 class="h5 mb-0"><i class="fa-solid fa-circle-check text-success me-2"></i>Approved Loans</h2>
            <span class="badge text-bg-success rounded-pill"><?= count($approvedLoans); ?></span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-success">
                    <tr>
                        <th>Loan ID</th>
                        <th>Farmer Name</th>
                        <th>District Location</th>
                        <th>Requested Amount</th>
                        <th>Purpose</th>
                        <th>Duration (Months)</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$approvedLoans): ?>
                        <tr><td colspan="8" class="text-center text-secondary py-4">No approved loans yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($approvedLoans as $application): ?>
                        <tr>
                            <td>#LN-<?= h(str_pad((string)$application['id'], 5, '0', STR_PAD_LEFT)); ?></td>
                            <td><?= h($application['farmer_name']); ?></td>
                            <td><?= h($application['district_location'] ?: 'Not provided'); ?></td>
                            <td><?= h(money_fmt($application['requested_amount'])); ?></td>
                            <td><?= h($application['purpose'] ?: 'Not provided'); ?></td>
                            <td><?= h($application['duration_months'] ?: 'N/A'); ?></td>
                            <td><span class="badge text-bg-success">Approved</span></td>
                            <td>
                                <div class="d-flex justify-content-end gap-2">
                                    <form method="post" action="<?= h(url('admin/loan_management.php')); ?>">
                                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token('admin_loan_management')); ?>">
                                        <input type="hidden" name="action" value="flag_account">
                                        <input type="hidden" name="target_user_id" value="<?= (int)$application['farmer_id']; ?>">
                                        <button class="btn btn-outline-danger btn-sm" type="submit">Flag Account</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Section 2: Rejected Loans (with rejection reason) -->
    <section class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
            <h2 class="h5 mb-0"><i class="fa-solid fa-circle-xmark text-danger me-2"></i>Rejected Loans</h2>
            <span class="badge text-bg-danger rounded-pill"><?= count($rejectedLoans); ?></span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-danger">
                    <tr>
                        <th>Loan ID</th>
                        <th>Farmer Name</th>
                        <th>District Location</th>
                        <th>Requested Amount</th>
                        <th>Purpose</th>
                        <th>Duration (Months)</th>
                        <th>Reason of Rejection</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$rejectedLoans): ?>
                        <tr><td colspan="8" class="text-center text-secondary py-4">No rejected loans.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($rejectedLoans as $application): ?>
                        <tr>
                            <td>#LN-<?= h(str_pad((string)$application['id'], 5, '0', STR_PAD_LEFT)); ?></td>
                            <td><?= h($application['farmer_name']); ?></td>
                            <td><?= h($application['district_location'] ?: 'Not provided'); ?></td>
                            <td><?= h(money_fmt($application['requested_amount'])); ?></td>
                            <td><?= h($application['purpose'] ?: 'Not provided'); ?></td>
                            <td><?= h($application['duration_months'] ?: 'N/A'); ?></td>
                            <td><?= h($application['rejection_reason'] ?: 'No reason recorded'); ?></td>
                            <td>
                                <div class="d-flex justify-content-end gap-2">
                                    <form method="post" action="<?= h(url('admin/loan_management.php')); ?>">
                                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token('admin_loan_management')); ?>">
                                        <input type="hidden" name="action" value="flag_account">
                                        <input type="hidden" name="target_user_id" value="<?= (int)$application['farmer_id']; ?>">
                                        <button class="btn btn-outline-danger btn-sm" type="submit">Flag Account</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h2 class="h5 mb-0">Global Repayment Log</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>Loan Reference</th>
                        <th>Amount</th>
                        <th>Logged At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$repayments): ?>
                        <tr>
                            <td colspan="4" class="text-center text-secondary py-4">No repayment records found.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($repayments as $repayment): ?>
                        <tr>
                            <td><?= h($repayment['transaction_id']); ?></td>
                            <td>#<?= h($repayment['loan_reference']); ?></td>
                            <td><?= h(money_fmt($repayment['amount'])); ?></td>
                            <td><?= h($repayment['logged_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>
