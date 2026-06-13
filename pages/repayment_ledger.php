<?php
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/includes/helpers.php';
require_once __DIR__ . '/../app/includes/db.php';

$user = require_role('farmer');
$pdo = db();

$paid = ($_GET['paid'] ?? '') === '1';
$error = $_GET['error'] ?? '';

// Active loan + its installments.
$loanStmt = $pdo->prepare('SELECT * FROM loans WHERE farmer_id = ? AND status = "active" ORDER BY disbursed_at DESC LIMIT 1');
$loanStmt->execute([(int)$user['id']]);
$activeLoan = $loanStmt->fetch();

$payments = [];
if ($activeLoan) {
    $payStmt = $pdo->prepare('SELECT * FROM loan_payments WHERE loan_id = ? ORDER BY due_date ASC, id ASC');
    $payStmt->execute([(int)$activeLoan['id']]);
    $payments = $payStmt->fetchAll();
}

$paidEmis = 0;
$totalPaid = 0;
$totalRemaining = 0;
$nextDueId = null;
foreach ($payments as $p) {
    if ($p['status'] === 'paid') {
        $paidEmis++;
        $totalPaid += (float)$p['amount'];
    } else {
        $totalRemaining += (float)$p['amount'];
        $nextDueId ??= (int)$p['id'];
    }
}
$tenure = $activeLoan ? (int)$activeLoan['tenure_months'] : 0;
$progress = $tenure > 0 ? (int)round(($paidEmis / $tenure) * 100) : 0;

// Outstanding due across ALL active loans + eligibility.
$income = farmer_monthly_income((int)$user['id']);
$eligibility = loan_eligibility((int)$user['id'], $income);

// Previous (closed) loans.
$historyStmt = $pdo->prepare('SELECT * FROM loans WHERE farmer_id = ? AND status = "closed" ORDER BY disbursed_at DESC');
$historyStmt->execute([(int)$user['id']]);
$history = $historyStmt->fetchAll();
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

<div class="dashboard-layout">
    <?php $active = 'loan-tracking'; require __DIR__ . '/../app/includes/farmer_sidebar.php'; ?>

    <main class="main-content">
        <div class="dash-header">
            <div>
                <h1>Loan Tracking &amp; Repayment</h1>
                <p>Monitor your repayment schedule and outstanding due.</p>
            </div>
            <a href="<?= url('pages/loan-application.php'); ?>" class="btn btn-accent"><i class="fas fa-file-signature"></i> Apply for Loan</a>
        </div>

        <?php if ($paid): ?>
            <div class="notice success"><i class="fas fa-check-circle"></i> Installment paid successfully. Your outstanding due has been updated.</div>
        <?php elseif ($error === 'cancelled'): ?>
            <div class="notice error"><i class="fas fa-circle-exclamation"></i> Payment cancelled — your installment was not charged.</div>
        <?php elseif ($error === 'gateway'): ?>
            <div class="notice error"><i class="fas fa-circle-exclamation"></i> Could not reach the payment gateway. Please try again.</div>
        <?php elseif ($error !== ''): ?>
            <div class="notice error"><i class="fas fa-circle-exclamation"></i> Could not process that payment. Please try again.</div>
        <?php endif; ?>

        <!-- Due + eligibility summary -->
        <div class="dash-cards">
            <div class="dash-card">
                <div class="info"><p>Outstanding Due</p><h3>BDT <?= number_format($eligibility['total_due'], 0); ?></h3><div class="sub">Across active loans</div></div>
                <div class="icon red"><i class="fas fa-hand-holding-usd"></i></div>
            </div>
            <div class="dash-card">
                <div class="info"><p>Due Ceiling (your tier)</p><h3>BDT <?= number_format($eligibility['max_due'], 0); ?></h3><div class="sub">Income: BDT <?= number_format($income, 0); ?>/mo</div></div>
                <div class="icon gold"><i class="fas fa-sliders"></i></div>
            </div>
            <div class="dash-card">
                <div class="info"><p>Due Headroom</p><h3>BDT <?= number_format($eligibility['headroom'], 0); ?></h3><div class="sub">Before hitting your due ceiling</div></div>
                <div class="icon navy"><i class="fas fa-arrow-up-right-dots"></i></div>
            </div>
            <div class="dash-card">
                <div class="info"><p>New Loan Eligibility</p><h3 style="font-size:1.2rem;color:<?= $eligibility['can_apply'] ? 'var(--emerald)' : 'var(--red)'; ?>"><?= $eligibility['can_apply'] ? 'Eligible' : 'Blocked'; ?></h3><div class="sub">Cap: BDT 1,00,000</div></div>
                <div class="icon green"><i class="fas <?= $eligibility['can_apply'] ? 'fa-circle-check' : 'fa-circle-xmark'; ?>"></i></div>
            </div>
        </div>

        <?php if (!$eligibility['can_apply']): ?>
            <div class="notice error"><i class="fas fa-triangle-exclamation"></i> <?= htmlspecialchars($eligibility['reason']); ?> Repay installments below to free up your limit.</div>
        <?php endif; ?>

        <?php if (!$activeLoan): ?>
            <div class="card" style="text-align:center;margin-top:1rem">
                <h3>No active loans</h3>
                <p style="color:var(--gray);margin-top:.5rem">Apply for a loan to start tracking repayments here.</p>
                <a class="btn btn-primary" href="<?= url('pages/loan-application.php'); ?>" style="margin-top:1rem">Apply for Loan</a>
            </div>
        <?php else: ?>
            <div class="card" style="margin:1.5rem 0">
                <div class="panel-heading" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
                    <h3>Active Loan · LN<?= (int)$activeLoan['id']; ?> · BDT <?= number_format((float)$activeLoan['principal'], 0); ?></h3>
                    <span class="muted"><?= number_format((float)$activeLoan['interest_rate'], 1); ?>% p.a. · <?= (int)$activeLoan['tenure_months']; ?> months</span>
                </div>
                <div class="summary-row"><span><?= $paidEmis; ?> of <?= $tenure; ?> EMIs paid</span><span><?= $progress; ?>%</span></div>
                <div class="progress-bar"><span style="width:<?= $progress; ?>%"></span></div>
                <div class="summary-grid">
                    <div class="summary-chip"><span>Total Paid</span><strong>BDT <?= number_format($totalPaid, 0); ?></strong></div>
                    <div class="summary-chip"><span>Remaining</span><strong>BDT <?= number_format($totalRemaining, 0); ?></strong></div>
                </div>
            </div>

            <div class="table-wrap">
                <h2><i class="fas fa-calendar" style="color:var(--emerald);margin-right:8px"></i> Installment Schedule</h2>
                <table>
                    <thead>
                        <tr><th>#</th><th>Due Date</th><th>Amount</th><th>Status</th><th>Paid On</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $index => $pay): ?>
                            <tr>
                                <td>EMI <?= $index + 1; ?></td>
                                <td><?= date('M j, Y', strtotime($pay['due_date'])); ?></td>
                                <td>BDT <?= number_format((float)$pay['amount'], 0); ?></td>
                                <td><span class="badge-status <?= $pay['status'] === 'paid' ? 'badge-success' : ($pay['status'] === 'late' ? 'badge-danger' : 'badge-warning'); ?>"><?= htmlspecialchars(ucfirst($pay['status'])); ?></span></td>
                                <td><?= $pay['paid_at'] ? date('M j, Y', strtotime($pay['paid_at'])) : '-'; ?></td>
                                <td>
                                    <?php if ($pay['status'] !== 'paid' && (int)$pay['id'] === $nextDueId): ?>
                                        <form method="post" action="<?= url('app/actions/loan_pay.php'); ?>"><?= csrf_field('app'); ?>
                                            <input type="hidden" name="payment_id" value="<?= (int)$pay['id']; ?>">
                                            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-credit-card"></i> Pay Now</button>
                                        </form>
                                    <?php elseif ($pay['status'] === 'paid'): ?>
                                        <span class="muted"><i class="fas fa-check"></i> Settled</span>
                                    <?php else: ?>
                                        <span class="muted">Pay earlier EMIs first</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="table-wrap" style="margin-top:2rem">
            <h2><i class="fas fa-history" style="color:var(--emerald);margin-right:8px"></i> Previous Loans</h2>
            <table>
                <thead><tr><th>ID</th><th>Amount</th><th>Tenure</th><th>Status</th><th>Disbursed</th></tr></thead>
                <tbody>
                    <?php if (!$history): ?>
                        <tr><td colspan="5" class="empty-cell">No closed loans yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($history as $loan): ?>
                            <tr>
                                <td>LN<?= (int)$loan['id']; ?></td>
                                <td>BDT <?= number_format((float)$loan['principal'], 0); ?></td>
                                <td><?= (int)$loan['tenure_months']; ?> months</td>
                                <td><span class="badge-status badge-success">Closed</span></td>
                                <td><?= $loan['disbursed_at'] ? date('M j, Y', strtotime($loan['disbursed_at'])) : '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

</body>
</html>
