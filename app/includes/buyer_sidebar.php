<?php
// Shared buyer dashboard sidebar. Set $active to the current page key before
// including this file (e.g. 'dashboard', 'marketplace', 'settings', ...).
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
        <a href="<?= url('pages/buyer-dashboard.php'); ?>" class="<?= $active === 'dashboard' ? 'active' : ''; ?>"><i class="fas fa-home"></i> Dashboard</a>
        <a href="<?= url('pages/marketplace.php'); ?>" class="<?= $active === 'marketplace' ? 'active' : ''; ?>"><i class="fas fa-store"></i> Marketplace</a>
        <a href="<?= url('pages/cart.php'); ?>" class="<?= $active === 'cart' ? 'active' : ''; ?>"><i class="fas fa-shopping-cart"></i> Shopping Cart</a>
        <a href="<?= url('pages/order_history.php'); ?>" class="<?= $active === 'orders' ? 'active' : ''; ?>"><i class="fas fa-history"></i> Order History</a>
        <a href="<?= url('pages/wishlist.php'); ?>" class="<?= $active === 'wishlist' ? 'active' : ''; ?>"><i class="fas fa-heart"></i> Wishlist</a>
        <a href="<?= url('pages/messaging.php'); ?>" class="<?= $active === 'messages' ? 'active' : ''; ?>"><i class="fas fa-comments"></i> Messages</a>
        <a href="<?= url('pages/buyer_profile.php#profile'); ?>" class="<?= $active === 'settings' ? 'active' : ''; ?>"><i class="fas fa-cog"></i> Settings</a>
        <a href="<?= url('app/actions/logout.php'); ?>" style="margin-top:2rem;opacity:.6"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
</aside>
