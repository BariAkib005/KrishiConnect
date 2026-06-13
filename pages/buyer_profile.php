<?php
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/includes/helpers.php';
require_once __DIR__ . '/../app/includes/db.php';

$user = require_role('buyer');
$pdo = db();

$saved = $_GET['saved'] ?? '';
$error = $_GET['error'] ?? '';

$accStmt = $pdo->prepare('SELECT id, full_name, email, phone FROM users WHERE id = ?');
$accStmt->execute([(int)$user['id']]);
$account = $accStmt->fetch() ?: $user;

$profStmt = $pdo->prepare('SELECT company_name, address FROM buyer_profiles WHERE user_id = ?');
$profStmt->execute([(int)$user['id']]);
$profile = $profStmt->fetch() ?: [];

$prefStmt = $pdo->prepare('SELECT email_notifications, sms_notifications, language, region FROM user_settings WHERE user_id = ?');
$prefStmt->execute([(int)$user['id']]);
$prefs = $prefStmt->fetch() ?: ['email_notifications' => 1, 'sms_notifications' => 1, 'language' => 'English', 'region' => 'Bangladesh'];

$payStmt = $pdo->prepare('SELECT method, account_number AS account FROM user_payment_methods WHERE user_id = ?');
$payStmt->execute([(int)$user['id']]);
$payment = $payStmt->fetch() ?: ['method' => 'bKash', 'account' => ''];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - KrishiConnect</title>
    <link rel="stylesheet" href="<?= asset_url('css/styles.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<div class="dashboard-layout">
    <?php $active = 'settings'; require __DIR__ . '/../app/includes/buyer_sidebar.php'; ?>

    <main class="main-content">
        <div class="dash-header">
            <div>
                <h1>My Profile</h1>
                <p>View and update your account, contact, and payment details.</p>
            </div>
            <a href="#profile" class="btn btn-accent"><i class="fas fa-pen"></i> Edit Details</a>
        </div>

        <?php if ($saved !== ''): ?>
            <div class="notice success"><i class="fas fa-check-circle"></i> Your changes have been saved.</div>
        <?php elseif ($error !== ''): ?>
            <div class="notice error"><i class="fas fa-circle-exclamation"></i> Please check the form and try again.</div>
        <?php endif; ?>

        <!-- Read-only summary -->
        <div class="card dashboard-section" style="margin-bottom:1.5rem">
            <h2 style="font-size:1.15rem;margin-bottom:1.25rem"><i class="fas fa-user" style="color:var(--emerald);margin-right:8px"></i> Personal Details</h2>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.25rem">
                <div><p class="muted">Full Name</p><p style="font-weight:600"><?= htmlspecialchars($account['full_name'] ?? ''); ?></p></div>
                <div><p class="muted">Email</p><p style="font-weight:600"><?= htmlspecialchars($account['email'] ?? ''); ?></p></div>
                <div><p class="muted">Phone</p><p style="font-weight:600"><?= htmlspecialchars($account['phone'] ?: 'Not set'); ?></p></div>
            </div>
        </div>

        <div class="card dashboard-section" style="margin-bottom:1.5rem">
            <h2 style="font-size:1.15rem;margin-bottom:1.25rem"><i class="fas fa-store" style="color:var(--emerald);margin-right:8px"></i> Business Details</h2>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.25rem">
                <div><p class="muted">Company Name</p><p style="font-weight:600"><?= htmlspecialchars($profile['company_name'] ?? 'Not set'); ?></p></div>
                <div><p class="muted">Address</p><p style="font-weight:600"><?= htmlspecialchars($profile['address'] ?? 'Not set'); ?></p></div>
            </div>
        </div>

        <!-- Edit form (the "Settings" entry point) -->
        <section class="card dashboard-section" id="profile" style="margin-bottom:1.5rem">
            <h2 style="font-size:1.15rem;margin-bottom:1.25rem"><i class="fas fa-cog" style="color:var(--emerald);margin-right:8px"></i> Update Details (Settings)</h2>
            <form method="post" action="<?= url('app/actions/settings_update.php'); ?>"><?= csrf_field('app'); ?>
                <input type="hidden" name="action" value="profile">
                <input type="hidden" name="return_to" value="pages/buyer_profile.php">

                <h3 class="form-section-title">Personal</h3>
                <div class="form-row">
                    <div class="form-group"><label>Full Name</label><input type="text" name="full_name" value="<?= htmlspecialchars($account['full_name'] ?? ''); ?>" required></div>
                    <div class="form-group"><label>Email</label><input type="email" value="<?= htmlspecialchars($account['email'] ?? ''); ?>" disabled></div>
                </div>
                <div class="form-group"><label>Phone</label><input type="text" name="phone" value="<?= htmlspecialchars($account['phone'] ?? ''); ?>"></div>

                <h3 class="form-section-title">Business</h3>
                <div class="form-row">
                    <div class="form-group"><label>Company Name</label><input type="text" name="extra" value="<?= htmlspecialchars($profile['company_name'] ?? ''); ?>"></div>
                    <div class="form-group"><label>Address</label><input type="text" name="address" value="<?= htmlspecialchars($profile['address'] ?? ''); ?>"></div>
                </div>

                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
            </form>
        </section>

        <!-- Password -->
        <section class="card dashboard-section" id="security" style="margin-bottom:1.5rem">
            <h2 style="font-size:1.15rem;margin-bottom:1.25rem"><i class="fas fa-lock" style="color:var(--emerald);margin-right:8px"></i> Security &amp; Password</h2>
            <form method="post" action="<?= url('app/actions/settings_update.php'); ?>"><?= csrf_field('app'); ?>
                <input type="hidden" name="action" value="password">
                <input type="hidden" name="return_to" value="pages/buyer_profile.php">
                <div class="form-row">
                    <div class="form-group"><label>Current Password</label><input type="password" name="current_password" required></div>
                    <div class="form-group"><label>New Password</label><input type="password" name="new_password" minlength="8" required></div>
                </div>
                <div class="form-group"><label>Confirm New Password</label><input type="password" name="confirm_password" minlength="8" required></div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-key"></i> Update Password</button>
            </form>
        </section>

        <!-- Notifications & region -->
        <section class="card dashboard-section" id="preferences" style="margin-bottom:1.5rem">
            <h2 style="font-size:1.15rem;margin-bottom:1.25rem"><i class="fas fa-bell" style="color:var(--emerald);margin-right:8px"></i> Notifications, Language &amp; Region</h2>
            <form method="post" action="<?= url('app/actions/settings_update.php'); ?>"><?= csrf_field('app'); ?>
                <input type="hidden" name="action" value="preferences">
                <input type="hidden" name="return_to" value="pages/buyer_profile.php">
                <div class="settings-list">
                    <label><span>Email notifications</span><input type="checkbox" name="email_notifications" <?= !empty($prefs['email_notifications']) ? 'checked' : ''; ?>></label>
                    <label><span>SMS notifications</span><input type="checkbox" name="sms_notifications" <?= !empty($prefs['sms_notifications']) ? 'checked' : ''; ?>></label>
                </div>
                <div class="form-row" style="margin-top:1rem">
                    <div class="form-group"><label>Language</label><select name="language"><option <?= $prefs['language'] === 'English' ? 'selected' : ''; ?>>English</option><option <?= $prefs['language'] === 'Bangla' ? 'selected' : ''; ?>>Bangla</option></select></div>
                    <div class="form-group"><label>Region</label><select name="region"><option <?= $prefs['region'] === 'Bangladesh' ? 'selected' : ''; ?>>Bangladesh</option><option <?= $prefs['region'] === 'India' ? 'selected' : ''; ?>>India</option></select></div>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Preferences</button>
            </form>
        </section>

        <!-- Payment methods -->
        <section class="card dashboard-section" id="payment">
            <h2 style="font-size:1.15rem;margin-bottom:1.25rem"><i class="fas fa-credit-card" style="color:var(--emerald);margin-right:8px"></i> Payment Methods</h2>
            <form method="post" action="<?= url('app/actions/settings_update.php'); ?>"><?= csrf_field('app'); ?>
                <input type="hidden" name="action" value="payment">
                <input type="hidden" name="return_to" value="pages/buyer_profile.php">
                <div class="form-row">
                    <div class="form-group"><label>Preferred Method</label><select name="payment_method"><option <?= $payment['method'] === 'bKash' ? 'selected' : ''; ?>>bKash</option><option <?= $payment['method'] === 'Nagad' ? 'selected' : ''; ?>>Nagad</option><option <?= $payment['method'] === 'Rocket' ? 'selected' : ''; ?>>Rocket</option><option <?= $payment['method'] === 'Card' ? 'selected' : ''; ?>>Card</option></select></div>
                    <div class="form-group"><label>Account / Wallet Number</label><input type="text" name="payment_account" value="<?= htmlspecialchars($payment['account'] ?? ''); ?>" placeholder="01XXXXXXXXX"></div>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Payment Method</button>
            </form>
        </section>
    </main>
</div>

</body>
</html>
