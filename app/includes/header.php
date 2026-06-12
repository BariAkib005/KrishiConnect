<?php
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

$title = $title ?? 'KrishiConnect';
$active = $active ?? '';
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title); ?></title>
    <link rel="stylesheet" href="<?= asset_url('css/styles.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<header class="site-header">
    <div class="container navbar">
        <a href="<?= url('index.php'); ?>" class="logo">
            <span class="brand-mark"><i class="fas fa-seedling"></i></span>
            <span>KrishiConnect</span>
        </a>
        <nav class="nav-links" id="primary-nav">
            <a href="<?= url('pages/about.php'); ?>" class="<?= $active === 'about' ? 'active' : ''; ?>">About</a>
            <a href="<?= url('pages/contact.php'); ?>" class="<?= $active === 'contact' ? 'active' : ''; ?>">Contact</a>
            <a href="<?= url('pages/faq.php'); ?>" class="<?= $active === 'faq' ? 'active' : ''; ?>">FAQ</a>
            <a href="<?= url('pages/blog.php'); ?>" class="<?= $active === 'blog' ? 'active' : ''; ?>">Blog</a>
            <?php if ($user): ?>
                <a href="<?= url(current_dashboard_path()); ?>" class="nav-cta">Dashboard</a>
            <?php else: ?>
                <a href="<?= url('pages/login.php'); ?>" class="nav-cta">Sign in</a>
            <?php endif; ?>
        </nav>
        <button class="hamburger" type="button" aria-label="Toggle navigation" aria-controls="primary-nav" aria-expanded="false"><i class="fas fa-bars"></i></button>
    </div>
</header>
<script>
(function () {
    var toggle = document.querySelector('.hamburger');
    var nav = document.getElementById('primary-nav');
    if (!toggle || !nav) return;
    toggle.addEventListener('click', function () {
        var open = nav.classList.toggle('open');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
})();
</script>
