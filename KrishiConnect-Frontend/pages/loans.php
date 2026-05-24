<?php
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/includes/helpers.php';
require_once __DIR__ . '/../app/includes/db.php';

$user = require_role('farmer');
$pdo = db();

$appStmt = $pdo->prepare('SELECT * FROM loan_applications WHERE farmer_id = ? ORDER BY submitted_at DESC');
$appStmt->execute([(int)$user['id']]);
$applications = $appStmt->fetchAll();

$submitted = $_GET['submitted'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agricultural Loans - KrishiConnect</title>
    <link rel="stylesheet" href="<?= asset_url('css/styles.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<?php require __DIR__ . '/../app/includes/header.php'; ?>

<section class="section">
    <div class="container">
        <div class="section-header" style="margin-bottom:2rem">
            <h2>Agricultural Loans</h2>
            <p>Get financial support for your farming needs</p>
        </div>
        <?php if ($submitted): ?>
            <div class="alert" style="margin-bottom:1.5rem">Your application has been submitted successfully.</div>
        <?php endif; ?>

        <div class="loan-calc">
            <div class="loan-calc-left">
                <h3><i class="fas fa-calculator"></i> Loan Calculator</h3>
                <label>Loan Amount</label>
                <input type="number" id="loanAmount" value="100000" min="10000" max="500000" step="10000">
                <input type="range" id="loanAmountRange" min="10000" max="500000" step="10000" value="100000">

                <label>Tenure (months)</label>
                <input type="number" id="loanTenure" value="12" min="6" max="60" step="6">
                <input type="range" id="loanTenureRange" min="6" max="60" step="6" value="12">

                <label>Interest Rate</label>
                <input type="text" value="7% p.a." disabled>
            </div>
            <div class="loan-calc-right">
                <h3>Loan Summary</h3>
                <div class="summary-row"><span>Monthly EMI</span><span id="loanEmi">BDT 0</span></div>
                <div class="summary-row"><span>Principal</span><span id="loanPrincipal">BDT 100,000</span></div>
                <div class="summary-row"><span>Total Interest</span><span id="loanInterest">BDT 0</span></div>
                <div class="summary-row total"><span>Total Payment</span><span id="loanTotal">BDT 0</span></div>
                <a class="btn btn-primary btn-block" href="<?= url('pages/loan-application.php'); ?>">Apply for Loan</a>
            </div>
        </div>

        <div style="margin-top:3rem">
            <h2 class="text-xl">Available Loan Schemes</h2>
            <div class="loan-grid" style="margin-top:1.25rem">
                <div class="loan-card">
                    <div class="top">
                        <h3>Crop Loan</h3>
                        <div class="amount">Up to BDT 3,00,000</div>
                        <p>7% p.a. - 6-12 months</p>
                    </div>
                    <div class="bottom">
                        <ul>
                            <li><i class="fas fa-check"></i> Quick approval</li>
                            <li><i class="fas fa-check"></i> Minimal documentation</li>
                            <li><i class="fas fa-check"></i> Flexible repayment</li>
                        </ul>
                        <a class="btn btn-outline btn-block" href="<?= url('pages/loan-application.php'); ?>">Learn More</a>
                    </div>
                </div>
                <div class="loan-card">
                    <div class="top">
                        <h3>Equipment Loan</h3>
                        <div class="amount">Up to BDT 10,00,000</div>
                        <p>9% p.a. - 1-5 years</p>
                    </div>
                    <div class="bottom">
                        <ul>
                            <li><i class="fas fa-check"></i> Low interest rate</li>
                            <li><i class="fas fa-check"></i> Long tenure</li>
                            <li><i class="fas fa-check"></i> Asset-backed</li>
                        </ul>
                        <a class="btn btn-outline btn-block" href="<?= url('pages/loan-application.php'); ?>">Learn More</a>
                    </div>
                </div>
                <div class="loan-card">
                    <div class="top">
                        <h3>Farm Development</h3>
                        <div class="amount">Up to BDT 5,00,000</div>
                        <p>8% p.a. - 2-7 years</p>
                    </div>
                    <div class="bottom">
                        <ul>
                            <li><i class="fas fa-check"></i> Subsidized rates</li>
                            <li><i class="fas fa-check"></i> Government backed</li>
                            <li><i class="fas fa-check"></i> Grace period</li>
                        </ul>
                        <a class="btn btn-outline btn-block" href="<?= url('pages/loan-application.php'); ?>">Learn More</a>
                    </div>
                </div>
            </div>
        </div>

        <div style="margin-top:3rem">
            <div class="table-wrap">
                <h2><i class="fas fa-file-alt" style="color:var(--emerald);margin-right:8px"></i> My Loan Applications</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Purpose</th>
                            <th>Amount</th>
                            <th>Tenure</th>
                            <th>Risk</th>
                            <th>Status</th>
                            <th>Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$applications): ?>
                            <tr><td colspan="7">No applications yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($applications as $app): ?>
                                <tr>
                                    <td>LN<?= (int)$app['id']; ?></td>
                                    <td><?= htmlspecialchars($app['purpose'] ?? '-'); ?></td>
                                    <td>BDT <?= number_format((float)$app['requested_amount'], 0); ?></td>
                                    <td><?= (int)($app['tenure_months'] ?? 0); ?> months</td>
                                    <td><span class="badge-status badge-info"><?= htmlspecialchars($app['risk_level']); ?></span></td>
                                    <td><span class="badge-status <?= $app['status'] === 'approved' ? 'badge-success' : ($app['status'] === 'rejected' ? 'badge-danger' : 'badge-warning'); ?>"><?= htmlspecialchars($app['status']); ?></span></td>
                                    <td><?= date('M j, Y', strtotime($app['submitted_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<script>
const amountInput = document.getElementById('loanAmount');
const amountRange = document.getElementById('loanAmountRange');
const tenureInput = document.getElementById('loanTenure');
const tenureRange = document.getElementById('loanTenureRange');
const emiEl = document.getElementById('loanEmi');
const principalEl = document.getElementById('loanPrincipal');
const interestEl = document.getElementById('loanInterest');
const totalEl = document.getElementById('loanTotal');

function calc() {
  const rate = 7 / 12 / 100;
  const principal = parseFloat(amountInput.value || 0);
  const months = parseInt(tenureInput.value || 0, 10);
  if (!principal || !months) {
    return;
  }
  const emi = (principal * rate * Math.pow(1 + rate, months)) / (Math.pow(1 + rate, months) - 1);
  const total = emi * months;
  const interest = total - principal;
  emiEl.textContent = 'BDT ' + Math.round(emi).toLocaleString();
  principalEl.textContent = 'BDT ' + principal.toLocaleString();
  interestEl.textContent = 'BDT ' + Math.round(interest).toLocaleString();
  totalEl.textContent = 'BDT ' + Math.round(total).toLocaleString();
}

function sync() {
  amountRange.value = amountInput.value;
  tenureRange.value = tenureInput.value;
  calc();
}

amountInput.addEventListener('input', sync);
amountRange.addEventListener('input', () => { amountInput.value = amountRange.value; sync(); });
tenureInput.addEventListener('input', sync);
tenureRange.addEventListener('input', () => { tenureInput.value = tenureRange.value; sync(); });

calc();
</script>

<?php require __DIR__ . '/../app/includes/footer.php'; ?>
</body>
</html>

