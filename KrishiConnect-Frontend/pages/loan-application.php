<?php
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/includes/helpers.php';

$user = require_role('farmer');
$error = $_GET['error'] ?? '';
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
            <?php if ($error === 'missing'): ?>
                <p style="color:var(--red);margin-bottom:1rem">Please fill out the required fields.</p>
            <?php endif; ?>
            <form method="post" action="<?= url('app/actions/loan_apply.php'); ?>">
                <h3 class="form-section-title"><i class="fas fa-user" style="color:var(--emerald);margin-right:8px"></i> Personal Information</h3>
                <div class="form-row">
                    <div class="form-group"><label>Full Name</label><input type="text" value="<?= htmlspecialchars($user['full_name'] ?? ''); ?>" readonly></div>
                    <div class="form-group"><label>Email</label><input type="text" value="<?= htmlspecialchars($user['email'] ?? ''); ?>" readonly></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Phone Number</label><input type="text" name="phone" placeholder="Enter phone number" required></div>
                    <div class="form-group"><label>District</label><input type="text" name="location" placeholder="Enter district" required></div>
                </div>

                <h3 class="form-section-title"><i class="fas fa-file-invoice-dollar" style="color:var(--emerald);margin-right:8px"></i> Loan Details</h3>
                <div class="form-group">
                    <label>Loan Type</label>
                    <select name="loan_type" required>
                        <option value="">Select Loan Type</option>
                        <option>Crop Loan</option>
                        <option>Equipment Loan</option>
                        <option>Farm Development</option>
                    </select>
                </div>
                <div class="form-group"><label>Loan Amount (BDT)</label><input type="number" name="amount" placeholder="Enter amount in Taka" required></div>
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

                <h3 class="form-section-title"><i class="fas fa-university" style="color:var(--emerald);margin-right:8px"></i> Banking Information</h3>
                <div class="form-row">
                    <div class="form-group"><label>Bank Name</label><input type="text" name="bank_name" placeholder="Enter bank name" required></div>
                    <div class="form-group"><label>Account Number</label><input type="text" name="bank_account" placeholder="Enter account number" required></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Farm Size</label><input type="text" name="farm_size" placeholder="e.g., 5 acres" required></div>
                    <div class="form-group"><label>Monthly Income</label><input type="text" name="monthly_income" placeholder="e.g., BDT 25,000" required></div>
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

<?php require __DIR__ . '/../app/includes/footer.php'; ?>
</body>
</html>

