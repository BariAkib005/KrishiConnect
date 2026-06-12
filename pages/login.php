<?php
$title = 'Login - KrishiConnect';
require_once __DIR__ . '/../app/includes/auth.php';
$error = $_GET['error'] ?? '';
$notice = $_GET['notice'] ?? '';
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
<main class="auth-page">
    <section class="auth-left">
        <a href="<?= url('index.php'); ?>" class="logo"><span class="brand-mark"><i class="fas fa-seedling"></i></span><span>KrishiConnect</span></a>
        <h1>Welcome back to your agriculture workspace.</h1>
        <p>Access marketplace orders, loan applications, payments, messages, and dashboards from one secure platform.</p>
    </section>
    <section class="auth-right">
        <div class="auth-form">
            <h2>Sign in</h2>
            <p class="subtitle">Use one of the seeded accounts or your registered profile.</p>
            <?php if ($notice === 'pending'): ?>
                <p class="alert alert-success">Your finance account was created and is awaiting admin approval. You'll be able to sign in once it's activated.</p>
            <?php endif; ?>
            <?php if ($error === 'invalid'): ?>
                <p class="alert">Invalid email or password.</p>
            <?php elseif ($error === 'inactive'): ?>
                <p class="alert">Your account is not active.</p>
            <?php elseif ($error === 'missing'): ?>
                <p class="alert">Please enter your email and password.</p>
            <?php elseif ($error === 'csrf'): ?>
                <p class="alert">Your session expired. Please try again.</p>
            <?php elseif ($error === 'unauthorized'): ?>
                <p class="alert">Please sign in with an authorized account.</p>
            <?php endif; ?>
            <form method="post" action="<?= url('app/actions/login_process.php'); ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('login_form'), ENT_QUOTES); ?>">
                <div class="form-group">
                    <label for="email_or_username">Email Address or Username</label>
                    <input type="text" id="email_or_username" name="email_or_username" placeholder="you@example.com" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
                <button class="btn btn-primary btn-block" type="submit">Sign In</button>
            </form>
            <div class="auth-footer">
                <p>New here? <a href="<?= url('pages/register.php'); ?>">Create an account</a></p>
                <p><a href="<?= url('pages/admin_login.php'); ?>">Admin PIN login</a></p>
            </div>

            <!-- Demo credentials for quick testing -->
            <div class="alert alert-info demo-card" role="note">
                <div class="demo-card-title">
                    <i class="fas fa-key"></i>
                    <span>Quick Demo Login (For Testing Only)</span>
                </div>
                <p class="demo-card-pw">Universal Password: <code>12345678</code></p>
                <ul class="demo-card-list">
                    <li><i class="fas fa-seedling"></i> <strong>Farmer:</strong> <code>farmer1@krishiconnect.com</code></li>
                    <li><i class="fas fa-store"></i> <strong>Buyer:</strong> <code>buyer1@krishiconnect.com</code></li>
                    <li><i class="fas fa-hand-holding-usd"></i> <strong>Finance Officer:</strong> <code>finance1@krishiconnect.com</code></li>
                </ul>
            </div>
        </div>
    </section>
</main>

<style>
    .demo-card{
        margin-top:1.5rem;padding:1rem 1.25rem;border-radius:12px;
        background:#eef6ff;border:1px solid #c7e0fb;color:#0b3d66;
        font-size:.9rem;line-height:1.5;
    }
    .demo-card-title{display:flex;align-items:center;gap:8px;font-weight:700;margin-bottom:.5rem}
    .demo-card-title i{color:#1a6b4a}
    .demo-card-pw{margin:0 0 .6rem}
    .demo-card code{background:rgba(0,0,0,.06);padding:.1rem .4rem;border-radius:6px;font-size:.85rem;font-weight:600}
    .demo-card-list{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:.4rem}
    .demo-card-list i{color:#1a6b4a;width:16px;text-align:center}
</style>
</body>
</html>
