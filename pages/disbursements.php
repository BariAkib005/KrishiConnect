<?php
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/includes/helpers.php';
require_once __DIR__ . '/../app/includes/db.php';

$user = require_role('finance');
$pdo = db();
$updated = ($_GET['updated'] ?? '') === '1';
$error = $_GET['error'] ?? '';

$summaryStmt = $pdo->query(
    'SELECT
        l.id AS loan_id,
        l.principal,
        l.approved_amount,
        l.disbursed_at,
        l.status AS loan_status,
        u.full_name AS farmer_name,
        u.email AS farmer_email,
        COALESCE(SUM(CASE WHEN lp.status = "paid" THEN lp.amount ELSE 0 END), 0) AS paid_amount,
        COALESCE(SUM(CASE WHEN lp.status <> "paid" THEN lp.amount ELSE 0 END), 0) AS due_amount,
        COALESCE(SUM(CASE WHEN lp.status = "late" THEN lp.amount ELSE 0 END), 0) AS overdue_amount,
        MIN(CASE WHEN lp.status <> "paid" THEN lp.due_date ELSE NULL END) AS next_due_date
     FROM loans l
     JOIN users u ON u.id = l.farmer_id
     LEFT JOIN loan_payments lp ON lp.loan_id = l.id
     WHERE l.status IN ("active", "closed")
     GROUP BY l.id, l.principal, l.approved_amount, l.disbursed_at, l.status, u.full_name, u.email
     ORDER BY l.disbursed_at DESC, l.id DESC'
);
$loanRows = $summaryStmt->fetchAll();

$installmentStmt = $pdo->query(
    'SELECT lp.*, l.id AS loan_id, u.full_name AS farmer_name
     FROM loan_payments lp
     JOIN loans l ON l.id = lp.loan_id
     JOIN users u ON u.id = l.farmer_id
     WHERE l.status = "active"
     ORDER BY lp.due_date ASC, lp.id ASC
     LIMIT 50'
);
$installments = $installmentStmt->fetchAll();

$totalDisbursed = 0;
$totalPaid = 0;
$totalDue = 0;
$totalOverdue = 0;
foreach ($loanRows as $loan) {
    $totalDisbursed += (float)$loan['approved_amount'];
    $totalPaid += (float)$loan['paid_amount'];
    $totalDue += (float)$loan['due_amount'];
    $totalOverdue += (float)$loan['overdue_amount'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disbursements - KrishiConnect</title>
    <link rel="stylesheet" href="<?= asset_url('css/styles.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<div class="dashboard-layout">
    <?php $active = 'disbursements'; require __DIR__ . '/../app/includes/finance_sidebar.php'; ?>

    <main class="main-content">
        <div class="dash-header">
            <div>
                <h1>Disbursements & Repayments</h1>
                <p>Track which farmers paid, how much is collected, and how much is still due.</p>
            </div>
            <div class="meta"><i class="fas fa-user"></i> <?= htmlspecialchars($user['full_name']); ?></div>
        </div>

        <?php if ($updated): ?>
            <div class="notice success"><i class="fas fa-check-circle"></i> Installment status updated.</div>
        <?php elseif ($error !== ''): ?>
            <div class="notice error"><i class="fas fa-circle-exclamation"></i> Could not update installment.</div>
        <?php endif; ?>

        <div class="dash-cards">
            <div class="dash-card">
                <div class="info"><p>Total Disbursed</p><h3>BDT <?= number_format($totalDisbursed, 0); ?></h3><div class="sub"><?= count($loanRows); ?> active or closed loans</div></div>
                <div class="icon green"><i class="fas fa-money-bill-transfer"></i></div>
            </div>
            <div class="dash-card">
                <div class="info"><p>Total Paid</p><h3>BDT <?= number_format($totalPaid, 0); ?></h3><div class="sub">Collected repayments</div></div>
                <div class="icon gold"><i class="fas fa-circle-check"></i></div>
            </div>
            <div class="dash-card">
                <div class="info"><p>Total Due</p><h3>BDT <?= number_format($totalDue, 0); ?></h3><div class="sub">Remaining scheduled payments</div></div>
                <div class="icon navy"><i class="fas fa-calendar-days"></i></div>
            </div>
            <div class="dash-card">
                <div class="info"><p>Overdue</p><h3>BDT <?= number_format($totalOverdue, 0); ?></h3><div class="sub">Marked late by finance</div></div>
                <div class="icon red"><i class="fas fa-triangle-exclamation"></i></div>
            </div>
        </div>

        <div class="table-wrap">
            <h2><i class="fas fa-hand-holding-dollar" style="color:var(--emerald);margin-right:8px"></i> Farmer Loan Balances</h2>
            <table>
                <thead>
                    <tr>
                        <th>Loan</th>
                        <th>Farmer</th>
                        <th>Disbursed</th>
                        <th>Paid</th>
                        <th>Due</th>
                        <th>Overdue</th>
                        <th>Next Due</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$loanRows): ?>
                        <tr><td colspan="8" class="empty-cell">No disbursed loans yet. Approved loan applications will appear here.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($loanRows as $loan): ?>
                        <tr>
                            <td>LN<?= (int)$loan['loan_id']; ?></td>
                            <td>
                                <strong><?= htmlspecialchars($loan['farmer_name']); ?></strong>
                                <div class="muted"><?= htmlspecialchars($loan['farmer_email']); ?></div>
                            </td>
                            <td>BDT <?= number_format((float)$loan['approved_amount'], 0); ?></td>
                            <td>BDT <?= number_format((float)$loan['paid_amount'], 0); ?></td>
                            <td>BDT <?= number_format((float)$loan['due_amount'], 0); ?></td>
                            <td>BDT <?= number_format((float)$loan['overdue_amount'], 0); ?></td>
                            <td><?= $loan['next_due_date'] ? date('M j, Y', strtotime($loan['next_due_date'])) : 'Paid off'; ?></td>
                            <td><span class="badge-status <?= $loan['loan_status'] === 'closed' ? 'badge-success' : 'badge-warning'; ?>"><?= htmlspecialchars($loan['loan_status']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="table-wrap" id="installments">
            <h2><i class="fas fa-list-check" style="color:var(--emerald);margin-right:8px"></i> Installment Register</h2>
            <table>
                <thead>
                    <tr>
                        <th>Farmer</th>
                        <th>Loan</th>
                        <th>Due Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Paid At</th>
                        <th>Update</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$installments): ?>
                        <tr><td colspan="7" class="empty-cell">No active installment schedule found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($installments as $payment): ?>
                        <tr>
                            <td><?= htmlspecialchars($payment['farmer_name']); ?></td>
                            <td>LN<?= (int)$payment['loan_id']; ?></td>
                            <td><?= date('M j, Y', strtotime($payment['due_date'])); ?></td>
                            <td>BDT <?= number_format((float)$payment['amount'], 0); ?></td>
                            <td><span class="badge-status <?= $payment['status'] === 'paid' ? 'badge-success' : ($payment['status'] === 'late' ? 'badge-danger' : 'badge-warning'); ?>"><?= htmlspecialchars($payment['status']); ?></span></td>
                            <td><?= $payment['paid_at'] ? date('M j, Y', strtotime($payment['paid_at'])) : '-'; ?></td>
                            <td>
                                <form method="post" action="<?= url('app/actions/loan_payment_update.php'); ?>" class="inline-update-form"><?= csrf_field('app'); ?>
                                    <input type="hidden" name="payment_id" value="<?= (int)$payment['id']; ?>">
                                    <select name="status" aria-label="Payment status">
                                        <option value="due" <?= $payment['status'] === 'due' ? 'selected' : ''; ?>>Due</option>
                                        <option value="paid" <?= $payment['status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                        <option value="late" <?= $payment['status'] === 'late' ? 'selected' : ''; ?>>Late</option>
                                    </select>
                                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
</body>
</html>
