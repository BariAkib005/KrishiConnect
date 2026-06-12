<?php
$title = 'Microfinance — KrishiConnect';
$active = 'microfinance';
require_once __DIR__ . '/../app/includes/header.php';
?>

<section class="section">
    <div class="container">
        <div class="section-header">
            <span class="section-label">Microfinance</span>
            <h2>Flexible Loans for Farmers</h2>
            <p>Transparent rates and fast approvals built for agriculture.</p>
        </div>
        <div class="loan-grid">
            <div class="loan-card">
                <div class="top">
                    <h3>Crop Loan</h3>
                    <div class="amount">৳50,000</div>
                    <p>Short-term support for seasonal crops</p>
                </div>
                <div class="bottom">
                    <ul>
                        <li><i class="fas fa-check-circle"></i> 6-12 months tenure</li>
                        <li><i class="fas fa-check-circle"></i> Low interest rates</li>
                        <li><i class="fas fa-check-circle"></i> Quick approvals</li>
                    </ul>
                    <a href="<?= url('pages/loan-application.php'); ?>" class="btn btn-primary btn-block">Apply Now</a>
                </div>
            </div>
            <div class="loan-card">
                <div class="top">
                    <h3>Equipment Loan</h3>
                    <div class="amount">৳1,50,000</div>
                    <p>Buy equipment and modern tools</p>
                </div>
                <div class="bottom">
                    <ul>
                        <li><i class="fas fa-check-circle"></i> 12-24 months tenure</li>
                        <li><i class="fas fa-check-circle"></i> Flexible repayment</li>
                        <li><i class="fas fa-check-circle"></i> KYC verified</li>
                    </ul>
                    <a href="<?= url('pages/loan-application.php'); ?>" class="btn btn-primary btn-block">Apply Now</a>
                </div>
            </div>
            <div class="loan-card">
                <div class="top">
                    <h3>Farm Expansion</h3>
                    <div class="amount">৳3,00,000</div>
                    <p>Scale your farming business</p>
                </div>
                <div class="bottom">
                    <ul>
                        <li><i class="fas fa-check-circle"></i> 24-36 months tenure</li>
                        <li><i class="fas fa-check-circle"></i> Growth advisory support</li>
                        <li><i class="fas fa-check-circle"></i> Dedicated officer</li>
                    </ul>
                    <a href="<?= url('pages/loan-application.php'); ?>" class="btn btn-primary btn-block">Apply Now</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section section-alt">
    <div class="container">
        <div class="section-header">
            <span class="section-label">How it works</span>
            <h2>3 Easy Steps</h2>
        </div>
        <div class="steps-grid">
            <div class="card step-card">
                <h3>Apply Online</h3>
                <p>Submit basic information about your farm and needs.</p>
            </div>
            <div class="card step-card">
                <h3>Get Approved</h3>
                <p>We review applications within 48 hours.</p>
            </div>
            <div class="card step-card">
                <h3>Receive Funds</h3>
                <p>Money is disbursed directly to your account.</p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
