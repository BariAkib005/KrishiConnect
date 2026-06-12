<?php
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/includes/helpers.php';

$user = require_role('farmer');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Rules - KrishiConnect</title>
    <link rel="stylesheet" href="<?= asset_url('css/styles.css'); ?>">
</head>
<body>
<?php require __DIR__ . '/../app/includes/header.php'; ?>

<section class="section">
    <div class="container" style="max-width:900px;margin:0 auto">
        <a href="loan-application.php" class="btn btn-outline-secondary btn-sm mb-3">&larr; Back to Loan Application</a>

        <div class="section-header">
            <h2>KrishiConnect Microfinance - Loan Eligibility Rules</h2>
            <p class="mb-4">A farmer may request between <strong>BDT 5,000</strong> and <strong>BDT 1,50,000</strong> per loan application.</p>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Per-Application Loan Amount</th>
                        <th>Minimum Loan (BDT)</th>
                        <th>Maximum Loan (BDT)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>All farmers</td>
                        <td>5,000</td>
                        <td>150,000 (Absolute Maximum Cap)</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h3 class="mt-4 mb-2">Outstanding-Due Eligibility</h3>
        <p class="mb-3">In addition to the amount limit, a new loan is only approved when the farmer's <strong>existing</strong> outstanding due is within these income-based ceilings. (After it is exceeded, the farmer must repay before borrowing again.)</p>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Monthly Income (BDT)</th>
                        <th>Maximum Outstanding Due Allowed (BDT)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>Up to 25,000</td><td>50,000</td></tr>
                    <tr><td>25,001 to 49,999</td><td>70,000</td></tr>
                    <tr><td>50,000 and above</td><td>100,000</td></tr>
                    <tr><td colspan="2"><strong>Absolute rule:</strong> any farmer whose total due exceeds 1,00,000 cannot apply for a new loan.</td></tr>
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            <a href="loan-application.php" class="btn btn-outline-secondary btn-sm">&larr; Back to Loan Application</a>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../app/includes/footer.php'; ?>
</body>
</html>
