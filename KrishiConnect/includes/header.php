<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container navbar">
            <div class="logo">
                <img src="<?php echo IMAGE_PATH; ?>krishiconnect-logo.png" alt="KrishiConnect Logo">
                <span>KrishiConnect</span>
            </div>
            <ul class="nav-links">
                <li><a href="<?php echo SITE_URL; ?>/index.php">Home</a></li>
                <li><a href="<?php echo SITE_URL; ?>/pages/marketplace.php">Marketplace</a></li>
                <li><a href="<?php echo SITE_URL; ?>/pages/microfinance.php">Microfinance</a></li>
                <li><a href="<?php echo SITE_URL; ?>/pages/about.php">About Us</a></li>
                <li><a href="<?php echo SITE_URL; ?>/pages/contact.php">Contact</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if (is_farmer()): ?>
                    <li><a href="<?php echo SITE_URL; ?>/pages/farmer-dashboard.php">My Dashboard</a></li>
                    <?php elseif (is_buyer()): ?>
                    <li><a href="<?php echo SITE_URL; ?>/pages/buyer-dashboard.php">My Dashboard</a></li>
                    <?php elseif (is_admin()): ?>
                    <li><a href="<?php echo SITE_URL; ?>/pages/admin-dashboard.php">Admin Dashboard</a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo SITE_URL; ?>/pages/logout.php" class="btn btn-secondary">Logout</a></li>
                <?php else: ?>
                    <li><a href="<?php echo SITE_URL; ?>/pages/login.php" class="btn btn-primary">Login/Register</a></li>
                <?php endif; ?>
            </ul>
            <button class="language-switch">বাংলা</button>
            <div class="hamburger">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </header>
    
    <!-- Display success/error messages if any -->
    <div class="container message-container">
        <?php echo display_messages(); ?>
    </div> 