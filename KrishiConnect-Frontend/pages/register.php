<?php
$title = 'Register - KrishiConnect';
require_once __DIR__ . '/../app/includes/helpers.php';
$error = $_GET['error'] ?? '';
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
        <h1>Join the platform built for farm commerce.</h1>
        <p>Create a farmer, buyer, or finance profile and start using the PHP/MySQL powered KrishiConnect workflows.</p>
    </section>
    <section class="auth-right">
        <div class="auth-form">
            <h2>Create your account</h2>
            <p class="subtitle">Choose your role and complete the secure registration.</p>
            
            <?php if ($error === 'missing'): ?>
                <p class="alert">Please fill out the required fields.</p>
            <?php elseif ($error === 'nomatch'): ?>
                <p class="alert">Passwords do not match.</p>
            <?php elseif ($error === 'exists'): ?>
                <p class="alert">An account with this email already exists.</p>
            <?php elseif ($error === 'weak_password'): ?>
                <p class="alert">Password is too weak! Must be at least 8 characters long and include at least one uppercase letter, one lowercase letter, one number, and one special character (e.g., @, #, $, %).</p>
            <?php endif; ?>
            
            <form method="post" action="<?= url('app/actions/register.php'); ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="text" id="phone" name="phone">
                    </div>
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <div class="role-selector">
                        <label class="role-btn active">
                            <input type="radio" name="role" value="farmer" checked hidden>
                            Farmer
                        </label>
                        <label class="role-btn">
                            <input type="radio" name="role" value="buyer" hidden>
                            Buyer
                        </label>
                        <label class="role-btn">
                            <input type="radio" name="role" value="finance" hidden>
                            Finance
                        </label>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>
                <label class="checkbox-label">
                    <input type="checkbox" required>
                    <span>I agree to the terms and conditions</span>
                </label>
                <button class="btn btn-primary btn-block" type="submit" style="margin-top:1rem">Create Account</button>
            </form>
            <div class="auth-footer">
                <p>Already have an account? <a href="<?= url('pages/login.php'); ?>">Sign in</a></p>
            </div>
        </div>
    </section>
</main>
<script>
document.querySelectorAll('.role-btn').forEach((button) => {
    button.addEventListener('click', () => {
        document.querySelectorAll('.role-btn').forEach((item) => item.classList.remove('active'));
        button.classList.add('active');
    });
});
</script>
</body>
</html>