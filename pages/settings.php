<?php
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/includes/helpers.php';
require_once __DIR__ . '/../app/includes/db.php';

require_login();
$user = current_user();
$pdo = db();
$pdo->exec(
    "CREATE TABLE IF NOT EXISTS user_settings (
        user_id INT PRIMARY KEY,
        email_notifications TINYINT(1) NOT NULL DEFAULT 1,
        sms_notifications TINYINT(1) NOT NULL DEFAULT 1,
        language VARCHAR(40) NOT NULL DEFAULT 'English',
        region VARCHAR(80) NOT NULL DEFAULT 'Bangladesh',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )"
);
$pdo->exec(
    "CREATE TABLE IF NOT EXISTS user_payment_methods (
        user_id INT PRIMARY KEY,
        method VARCHAR(40) NOT NULL DEFAULT 'bKash',
        account_number VARCHAR(80) DEFAULT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )"
);
$saved = $_GET['saved'] ?? '';
$error = $_GET['error'] ?? '';

$stmt = $pdo->prepare('SELECT id, full_name, email, phone, role FROM users WHERE id = ?');
$stmt->execute([(int)$user['id']]);
$account = $stmt->fetch() ?: $user;

$profile = [];
if ($account['role'] === 'buyer') {
    $profileStmt = $pdo->prepare('SELECT company_name AS extra, address FROM buyer_profiles WHERE user_id = ?');
} elseif ($account['role'] === 'farmer') {
    $profileStmt = $pdo->prepare('SELECT farm_name AS extra, location AS address FROM farmer_profiles WHERE user_id = ?');
} elseif ($account['role'] === 'finance') {
    $profileStmt = $pdo->prepare('SELECT institution AS extra, designation AS address FROM finance_profiles WHERE user_id = ?');
} else {
    $profileStmt = null;
}

if ($profileStmt) {
    $profileStmt->execute([(int)$account['id']]);
    $profile = $profileStmt->fetch() ?: [];
}

$prefStmt = $pdo->prepare('SELECT * FROM user_settings WHERE user_id = ?');
$prefStmt->execute([(int)$account['id']]);
$prefs = $prefStmt->fetch() ?: [
    'email_notifications' => true,
    'sms_notifications' => true,
    'language' => 'English',
    'region' => 'Bangladesh',
];
$payStmt = $pdo->prepare('SELECT method, account_number AS account FROM user_payment_methods WHERE user_id = ?');
$payStmt->execute([(int)$account['id']]);
$payment = $payStmt->fetch() ?: [
    'method' => 'bKash',
    'account' => '',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - KrishiConnect</title>
    <link rel="stylesheet" href="<?= asset_url('css/styles.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<?php require __DIR__ . '/../app/includes/header.php'; ?>

<section class="section">
    <div class="container" style="max-width:960px">
        <div class="section-header" style="margin-bottom:2rem">
            <h2>Settings</h2>
            <p>Manage your account preferences and security.</p>
        </div>

        <?php if ($saved): ?>
            <div class="notice success"><i class="fas fa-check-circle"></i> Settings saved successfully.</div>
        <?php elseif ($error): ?>
            <div class="notice error"><i class="fas fa-circle-exclamation"></i> Please check the highlighted form and try again.</div>
        <?php endif; ?>

        <div class="settings-jump-grid">
            <a class="settings-card" href="#profile"><i class="fas fa-user"></i><div><h3>Profile Information</h3><p>Update your name, phone, and address</p></div></a>
            <a class="settings-card" href="#security"><i class="fas fa-lock"></i><div><h3>Security &amp; Password</h3><p>Change password</p></div></a>
            <a class="settings-card" href="#preferences"><i class="fas fa-bell"></i><div><h3>Notifications</h3><p>Manage email and SMS preferences</p></div></a>
            <a class="settings-card" href="#preferences"><i class="fas fa-globe"></i><div><h3>Language &amp; Region</h3><p><?= htmlspecialchars($prefs['language']); ?> - <?= htmlspecialchars($prefs['region']); ?> (BDT)</p></div></a>
            <a class="settings-card" href="#payment"><i class="fas fa-credit-card"></i><div><h3>Payment Methods</h3><p>Manage bKash, Nagad, Rocket, cards</p></div></a>
        </div>

        <div class="settings-panel" id="profile">
            <h2><i class="fas fa-user"></i> Profile Information</h2>
            <form method="post" action="<?= url('app/actions/settings_update.php'); ?>"><?= csrf_field('app'); ?>
                <input type="hidden" name="action" value="profile">
                <div class="form-row">
                    <div class="form-group"><label>Full Name</label><input type="text" name="full_name" value="<?= htmlspecialchars($account['full_name'] ?? ''); ?>" required></div>
                    <div class="form-group"><label>Email</label><input type="email" value="<?= htmlspecialchars($account['email'] ?? ''); ?>" disabled></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Phone</label><input type="text" name="phone" value="<?= htmlspecialchars($account['phone'] ?? ''); ?>"></div>
                    <div class="form-group"><label><?= $account['role'] === 'finance' ? 'Designation' : 'Address / Location'; ?></label><input type="text" name="address" value="<?= htmlspecialchars($profile['address'] ?? ''); ?>"></div>
                </div>
                <div class="form-group"><label><?= $account['role'] === 'farmer' ? 'Farm Name' : ($account['role'] === 'finance' ? 'Institution' : 'Company Name'); ?></label><input type="text" name="extra" value="<?= htmlspecialchars($profile['extra'] ?? ''); ?>"></div>
                <button class="btn btn-primary" type="submit">Save Profile</button>
            </form>
        </div>

        <div class="settings-panel" id="security">
            <h2><i class="fas fa-lock"></i> Security &amp; Password</h2>
            <form method="post" action="<?= url('app/actions/settings_update.php'); ?>"><?= csrf_field('app'); ?>
                <input type="hidden" name="action" value="password">
                <div class="form-row">
                    <div class="form-group"><label>Current Password</label><input type="password" name="current_password" required></div>
                    <div class="form-group"><label>New Password</label><input type="password" name="new_password" minlength="8" required></div>
                </div>
                <div class="form-group"><label>Confirm New Password</label><input type="password" name="confirm_password" minlength="8" required></div>
                <button class="btn btn-primary" type="submit">Update Password</button>
            </form>
        </div>

        <div class="settings-panel" id="preferences">
            <h2><i class="fas fa-bell"></i> Notifications, Language &amp; Region</h2>
            <form method="post" action="<?= url('app/actions/settings_update.php'); ?>"><?= csrf_field('app'); ?>
                <input type="hidden" name="action" value="preferences">
                <div class="settings-list">
                    <label><span>Email notifications</span><input type="checkbox" name="email_notifications" <?= !empty($prefs['email_notifications']) ? 'checked' : ''; ?>></label>
                    <label><span>SMS notifications</span><input type="checkbox" name="sms_notifications" <?= !empty($prefs['sms_notifications']) ? 'checked' : ''; ?>></label>
                </div>
                <div class="form-row" style="margin-top:1rem">
                    <div class="form-group"><label>Language</label><select name="language"><option <?= $prefs['language'] === 'English' ? 'selected' : ''; ?>>English</option><option <?= $prefs['language'] === 'Bangla' ? 'selected' : ''; ?>>Bangla</option></select></div>
                    <div class="form-group"><label>Region</label><select name="region"><option <?= $prefs['region'] === 'Bangladesh' ? 'selected' : ''; ?>>Bangladesh</option><option <?= $prefs['region'] === 'India' ? 'selected' : ''; ?>>India</option></select></div>
                </div>
                <button class="btn btn-primary" type="submit">Save Preferences</button>
            </form>
        </div>

        <div class="settings-panel" id="payment">
            <h2><i class="fas fa-credit-card"></i> Payment Methods</h2>
            <form method="post" action="<?= url('app/actions/settings_update.php'); ?>"><?= csrf_field('app'); ?>
                <input type="hidden" name="action" value="payment">
                <div class="form-row">
                    <div class="form-group"><label>Preferred Method</label><select name="payment_method"><option <?= $payment['method'] === 'bKash' ? 'selected' : ''; ?>>bKash</option><option <?= $payment['method'] === 'Nagad' ? 'selected' : ''; ?>>Nagad</option><option <?= $payment['method'] === 'Rocket' ? 'selected' : ''; ?>>Rocket</option><option <?= $payment['method'] === 'Card' ? 'selected' : ''; ?>>Card</option></select></div>
                    <div class="form-group"><label>Account / Wallet Number</label><input type="text" name="payment_account" value="<?= htmlspecialchars($payment['account']); ?>" placeholder="01XXXXXXXXX"></div>
                </div>
                <button class="btn btn-primary" type="submit">Save Payment Method</button>
            </form>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../app/includes/footer.php'; ?>
</body>
</html>
