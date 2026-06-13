<?php
// Shared finance dashboard sidebar. Set $active to the current page key before
// including this file (e.g. 'dashboard', 'disbursements', 'settings', ...).
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
        <a href="<?= url('pages/finance-dashboard.php'); ?>" class="<?= $active === 'dashboard' ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="<?= url('pages/loan-management.php'); ?>" class="<?= $active === 'applications' ? 'active' : ''; ?>"><i class="fas fa-file-invoice"></i> Loan Applications</a>
        <a href="<?= url('pages/disbursements.php'); ?>" class="<?= $active === 'disbursements' ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Disbursements</a>
        <a href="<?= url('pages/messaging.php'); ?>" class="<?= $active === 'messages' ? 'active' : ''; ?>"><i class="fas fa-comments"></i> Messages</a>
        <a href="<?= url('pages/finance_profile.php#profile'); ?>" class="<?= $active === 'settings' ? 'active' : ''; ?>"><i class="fas fa-cog"></i> Settings</a>
        <a href="<?= url('app/actions/logout.php'); ?>" style="margin-top:2rem;opacity:.6"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
</aside>
