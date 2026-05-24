<?php
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/includes/helpers.php';
require_once __DIR__ . '/../app/includes/db.php';

$user = require_role('farmer');
$pdo = db();

$loanStmt = $pdo->prepare('SELECT * FROM loans WHERE farmer_id = ? AND status = "active" ORDER BY disbursed_at DESC LIMIT 1');
$loanStmt->execute([(int)$user['id']]);
$activeLoan = $loanStmt->fetch();

$payments = [];
if ($activeLoan) {
    $payStmt = $pdo->prepare('SELECT * FROM loan_payments WHERE loan_id = ? ORDER BY due_date ASC');
    $payStmt->execute([(int)$activeLoan['id']]);
    $payments = $payStmt->fetchAll();
}

$historyStmt = $pdo->prepare('SELECT * FROM loans WHERE farmer_id = ? AND status = "closed" ORDER BY disbursed_at DESC');
$historyStmt->execute([(int)$user['id']]);
$history = $historyStmt->fetchAll();

$paidEmis = 0;
$totalRemaining = 0;
$totalPaid = 0;
foreach ($payments as $p) {
    if ($p['status'] === 'paid') {
        $paidEmis++;
        $totalPaid += (float)$p['amount'];
    } else {
        $totalRemaining += (float)$p['amount'];
    }
}
$tenure = $activeLoan ? (int)$activeLoan['tenure_months'] : 0;
$progress = $tenure > 0 ? round(($paidEmis / $tenure) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Tracking - KrishiConnect</title>
    <link rel="stylesheet" href="<?= asset_url('css/styles.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<?php require __DIR__ . '/../app/includes/header.php'; ?>

<section class="section">
    <div class="container">
        <div class="tracking-header">
            <div>
                <h1>Loan Tracking</h1>
                <p>Monitor your loan status and repayment schedule</p>
            </div>
        </div>

        <?php if (!$activeLoan): ?>
            <div class="card" style="text-align:center">
                <h3>No active loans</h3>
                <p style="color:var(--gray);margin-top:.5rem">Apply for a loan to get started.</p>
                <a class="btn btn-primary" href="<?= url('pages/loan-application.php'); ?>" style="margin-top:1rem">Apply for Loan</a>
            </div>
        <?php else: ?>
            <div class="status-banner" style="margin-bottom:2rem">
                <div>
                    <div class="label">Active Loan</div>
                    <h2>BDT <?= number_format((float)$activeLoan['principal'], 0); ?></h2>
                    <div class="label">Loan ID: LN<?= (int)$activeLoan['id']; ?></div>
                </div>
                <div style="text-align:right">
                    <div class="label"><?= number_format((float)$activeLoan['interest_rate'], 1); ?>% p.a.</div>
                    <div class="label">Tenure: <?= (int)$activeLoan['tenure_months']; ?> months</div>
                </div>
            </div>

            <div class="card" style="margin-bottom:2rem">
                <h3 style="margin-bottom:1rem">Repayment Progress</h3>
                <div class="summary-row"><span><?= $paidEmis; ?> of <?= $tenure; ?> EMIs paid</span><span><?= $progress; ?>%</span></div>
                <div class="progress-bar"><span style="width:<?= $progress; ?>%"></span></div>
                <div class="summary-grid">
                    <div class="summary-chip"><span>Total Paid</span><strong>BDT <?= number_format($totalPaid, 0); ?></strong></div>
                    <div class="summary-chip"><span>Remaining</span><strong>BDT <?= number_format($totalRemaining, 0); ?></strong></div>
                </div>
            </div>

            <div class="table-wrap">
                <h2><i class="fas fa-calendar" style="color:var(--emerald);margin-right:8px"></i> EMI Schedule</h2>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Due Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $index => $pay): ?>
                            <tr>
                                <td>EMI <?= $index + 1; ?></td>
                                <td><?= date('M j, Y', strtotime($pay['due_date'])); ?></td>
                                <td>BDT <?= number_format((float)$pay['amount'], 0); ?></td>
                                <td><span class="badge-status <?= $pay['status'] === 'paid' ? 'badge-success' : ($pay['status'] === 'late' ? 'badge-danger' : 'badge-warning'); ?>"><?= htmlspecialchars($pay['status']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="table-wrap" style="margin-top:2rem">
            <h2><i class="fas fa-history" style="color:var(--emerald);margin-right:8px"></i> Previous Loans</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Amount</th>
                        <th>Tenure</th>
                        <th>Status</th>
                        <th>Disbursed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$history): ?>
                        <tr><td colspan="5">No closed loans yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($history as $loan): ?>
                            <tr>
                                <td>LN<?= (int)$loan['id']; ?></td>
                                <td>BDT <?= number_format((float)$loan['principal'], 0); ?></td>
                                <td><?= (int)$loan['tenure_months']; ?> months</td>
                                <td><span class="badge-status badge-success">Closed</span></td>
                                <td><?= date('M j, Y', strtotime($loan['disbursed_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../app/includes/footer.php'; ?>
</body>
</html>

