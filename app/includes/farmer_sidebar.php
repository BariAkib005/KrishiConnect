<?php
// Shared farmer dashboard sidebar. Set $active to the current page key before
// including this file (e.g. 'dashboard', 'profile', 'inventory', ...).
$active = $active ?? '';
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <a href="<?= url('index.php'); ?>" class="logo">
            <span class="brand-mark"><i class="fas fa-seedling"></i></span>
            <span>KrishiConnect</span>
        </a>
    </div>
    <nav class="sidebar-nav">
        <a href="<?= url('pages/dashboard.php'); ?>" class="<?= $active === 'dashboard' ? 'active' : ''; ?>"><i class="fas fa-home"></i> Dashboard</a>
        <a href="<?= url('pages/farmer_profile.php'); ?>" class="<?= $active === 'profile' ? 'active' : ''; ?>"><i class="fas fa-user"></i> My Profile</a>
        <a href="<?= url('pages/manage_products.php#add'); ?>" class="<?= $active === 'list-produce' ? 'active' : ''; ?>"><i class="fas fa-plus-circle"></i> List Produce</a>
        <a href="<?= url('pages/manage_products.php'); ?>" class="<?= $active === 'inventory' ? 'active' : ''; ?>"><i class="fas fa-warehouse"></i> Inventory</a>
        <a href="<?= url('pages/order_history.php'); ?>" class="<?= $active === 'sales' ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Sales</a>
        <a href="<?= url('pages/loan-application.php'); ?>" class="<?= $active === 'apply-loan' ? 'active' : ''; ?>"><i class="fas fa-file-signature"></i> Apply for Loan</a>
        <a href="<?= url('pages/repayment_ledger.php'); ?>" class="<?= $active === 'loan-tracking' ? 'active' : ''; ?>"><i class="fas fa-hand-holding-usd"></i> Loan Tracking</a>
        <a href="<?= url('pages/smart_agri.php'); ?>" class="<?= $active === 'market-insights' ? 'active' : ''; ?>"><i class="fas fa-lightbulb"></i> Market Insights</a>
        <a href="<?= url('pages/farmer_profile.php#edit'); ?>" class="<?= $active === 'settings' ? 'active' : ''; ?>"><i class="fas fa-cog"></i> Settings</a>
        <a href="<?= url('app/actions/logout.php'); ?>" style="margin-top:2rem;opacity:.6"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
</aside>
