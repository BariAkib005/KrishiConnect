<?php
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/includes/helpers.php';

// Read flash session values (set by the processor) and then clear them
$loan_error = $_SESSION['loan_error'] ?? null;
$loan_error_field = $_SESSION['loan_error_field'] ?? null;
$allowed_min = $_SESSION['allowed_min'] ?? null;
$allowed_max = $_SESSION['allowed_max'] ?? null;
$old_income = $_SESSION['old_income'] ?? null;
$old_amount = $_SESSION['old_amount'] ?? null;
unset(
    $_SESSION['loan_error'],
    $_SESSION['loan_error_field'],
    $_SESSION['allowed_min'],
    $_SESSION['allowed_max'],
    $_SESSION['old_income'],
    $_SESSION['old_amount']
);

$user = require_role('farmer');
[$loanMin, $loanMax] = loan_amount_bounds();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Application - KrishiConnect</title>
    <link rel="stylesheet" href="<?= asset_url('css/styles.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<?php require __DIR__ . '/../app/includes/header.php'; ?>

<section class="section">
    <div class="container">
        <div class="section-header">
            <span class="section-label">Apply for a Loan</span>
            <h2>Loan Application Form</h2>
            <p>Fill in the details below to apply for financial support for your agricultural activities</p>
        </div>
        <div class="form-container" style="max-width:800px;margin:0 auto">
            <a href="loan-rules.php" class="btn btn-outline-info btn-sm mb-3"><i class="fas fa-info-circle"></i> View Loan Eligibility Rules</a>

            <form method="post" action="<?= url('app/actions/loan_apply.php'); ?>"><?= csrf_field('app'); ?>
                <?php if (!empty($loan_error) && ($loan_error_field ?? '') !== 'amount_error'): ?>
                    <div class="alert alert-danger" role="alert" style="margin-bottom:1rem">
                        <?= htmlspecialchars($loan_error); ?>
                    </div>
                <?php endif; ?>

                <h3 class="form-section-title"><i class="fas fa-user" style="color:var(--emerald);margin-right:8px"></i> Personal Information</h3>
                <div class="form-row">
                    <div class="form-group"><label>Full Name</label><input type="text" value="<?= htmlspecialchars($user['full_name'] ?? ''); ?>" readonly></div>
                    <div class="form-group"><label>Email</label><input type="text" value="<?= htmlspecialchars($user['email'] ?? ''); ?>" readonly></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Phone Number</label><input type="text" name="phone" placeholder="Enter phone number" required></div>
                    <div class="form-group"><label>District</label><input type="text" name="location" placeholder="Enter district" required></div>
                </div>

                <h3 class="form-section-title"><i class="fas fa-university" style="color:var(--emerald);margin-right:8px"></i> Banking Information</h3>
                <div class="form-row">
                    <div class="form-group"><label>Account Number</label><input type="text" name="bank_account" placeholder="Enter account number" required></div>
                    <div class="form-group"><label>Bank Name</label><input type="text" name="bank_name" placeholder="Enter bank name" required></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Branch</label><input type="text" name="bank_branch" placeholder="Enter branch name"></div>
                    <div class="form-group"><label>Farm Size</label><input type="text" name="farm_size" placeholder="e.g., 5 acres" required></div>
                </div>

                <div class="form-row">
                    <div class="form-group"><label>Monthly Income</label><input type="text" id="monthly_income" name="monthly_income" inputmode="numeric" placeholder="e.g., 25000 or BDT 25,000" value="<?= htmlspecialchars($old_income ?? ''); ?>" required></div>
                </div>

                <h3 class="form-section-title"><i class="fas fa-file-invoice-dollar" style="color:var(--emerald);margin-right:8px"></i> Loan Information</h3>
                <div class="form-group">
                    <label>Loan Type</label>
                    <select name="loan_type" required>
                        <option value="">Select Loan Type</option>
                        <option>Crop Loan</option>
                        <option>Equipment Loan</option>
                        <option>Farm Development</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Loan Amount (BDT)</label>
                    <input type="number" id="loan_amount" name="loan_amount" min="<?= (int)$loanMin; ?>" max="<?= (int)$loanMax; ?>" placeholder="Enter amount in Taka (max 150,000)" value="<?= htmlspecialchars($old_amount ?? ''); ?>" required>
                    <div id="loanRangeMsg" class="text-muted small mt-1"></div>
                    <div id="loanErrorMsg" class="text-danger small mt-1"></div>
                    <?php if (($loan_error_field ?? '') === 'amount_error' && $allowed_min !== null && $allowed_max !== null): ?>
                        <div class="text-danger small mt-1">You can take loan amount <?= htmlspecialchars(number_format((int)$allowed_min)); ?> to <?= htmlspecialchars(number_format((int)$allowed_max)); ?> according to your monthly income.</div>
                    <?php endif; ?>
                </div>
                <div class="form-group"><label>Purpose of Loan</label><textarea name="purpose" placeholder="Describe how you plan to use this loan..." required></textarea></div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Repayment Term (months)</label>
                        <select name="tenure_months" required>
                            <option value="">Select Term</option>
                            <option value="6">6 months</option>
                            <option value="12">12 months</option>
                            <option value="24">24 months</option>
                            <option value="36">36 months</option>
                            <option value="60">60 months</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Collateral</label>
                        <select name="collateral">
                            <option value="">No Collateral</option>
                            <option>Land Title</option>
                            <option>Farm Equipment</option>
                            <option>Other Asset</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-top:1.5rem">
                    <label class="checkbox-label"><input type="checkbox" required> I agree to the <a href="#" style="color:var(--emerald);font-weight:600">terms and conditions</a>.</label>
                </div>

                <div style="display:flex;gap:1rem;justify-content:flex-end;margin-top:2rem">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Submit Application</button>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
(function () {
    const loanInput = document.getElementById('loan_amount');
    const rangeEl = document.getElementById('loanRangeMsg');
    const errEl = document.getElementById('loanErrorMsg');
    if (!loanInput) return;

    // Single source of truth, injected from PHP (loan_amount_bounds()).
    const LOAN_MIN = <?= (int)$loanMin; ?>, LOAN_MAX = <?= (int)$loanMax; ?>;

    // Neutral helper text — shown without any error styling.
    if (rangeEl) {
        rangeEl.textContent = `Allowed: BDT ${LOAN_MIN.toLocaleString()} to ${LOAN_MAX.toLocaleString()}.`;
    }

    function validateLoan() {
        const raw = loanInput.value.trim();

        // Empty field stays neutral — no red before the user types anything.
        if (raw === '') {
            loanInput.style.borderColor = '';
            if (errEl) errEl.textContent = '';
            return;
        }

        const val = Number(raw);
        let error = '';
        if (val < 0) {
            error = 'Loan amount cannot be negative.';
        } else if (val > LOAN_MAX) {
            error = `Maximum loan amount is BDT ${LOAN_MAX.toLocaleString()}.`;
        }

        loanInput.style.borderColor = error ? 'red' : '';
        if (errEl) errEl.textContent = error;
    }

    loanInput.addEventListener('input', validateLoan);
})();
</script>

<?php require __DIR__ . '/../app/includes/footer.php'; ?>
</body>
</html>

