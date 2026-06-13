<?php
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/includes/helpers.php';
require_once __DIR__ . '/../app/includes/db.php';

$user = require_role('farmer');
$pdo = db();

$saved = ($_GET['saved'] ?? '') === '1';
$error = $_GET['error'] ?? '';

$accStmt = $pdo->prepare('SELECT id, full_name, email, phone FROM users WHERE id = ?');
$accStmt->execute([(int)$user['id']]);
$account = $accStmt->fetch() ?: $user;

$profStmt = $pdo->prepare('SELECT * FROM farmer_profiles WHERE user_id = ?');
$profStmt->execute([(int)$user['id']]);
$profile = $profStmt->fetch() ?: [];

$val = static fn(string $key): string => htmlspecialchars((string)($profile[$key] ?? ''), ENT_QUOTES);
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
    <?php $active = 'settings'; require __DIR__ . '/../app/includes/farmer_sidebar.php'; ?>

    <main class="main-content">
        <div class="dash-header">
            <div>
                <h1>My Profile</h1>
                <p>View and update your personal, farm, and bank details.</p>
            </div>
            <a href="#edit" class="btn btn-accent"><i class="fas fa-pen"></i> Edit Details</a>
        </div>

        <?php if ($saved): ?>
            <div class="notice success"><i class="fas fa-check-circle"></i> Your profile has been updated.</div>
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
            <h2 style="font-size:1.15rem;margin-bottom:1.25rem"><i class="fas fa-tractor" style="color:var(--emerald);margin-right:8px"></i> Farm Details</h2>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.25rem">
                <div><p class="muted">Farm Name</p><p style="font-weight:600"><?= htmlspecialchars($profile['farm_name'] ?? 'Not set'); ?></p></div>
                <div><p class="muted">Location</p><p style="font-weight:600"><?= htmlspecialchars($profile['location'] ?? 'Not set'); ?></p></div>
                <div><p class="muted">Farm Size</p><p style="font-weight:600"><?= number_format((float)($profile['land_area'] ?? 0), 1); ?> Acres</p></div>
                <div><p class="muted">Soil Type</p><p style="font-weight:600"><?= htmlspecialchars($profile['soil_type'] ?? 'Not set'); ?></p></div>
                <div><p class="muted">Irrigation</p><p style="font-weight:600"><?= htmlspecialchars($profile['irrigation'] ?? 'Not set'); ?></p></div>
                <div><p class="muted">Monthly Income</p><p style="font-weight:600">BDT <?= number_format((float)($profile['monthly_income'] ?? 0), 0); ?></p></div>
                <div><p class="muted">KYC Status</p><p><span class="badge-status <?= ($profile['kyc_status'] ?? '') === 'verified' ? 'badge-success' : 'badge-warning'; ?>"><?= htmlspecialchars(ucfirst($profile['kyc_status'] ?? 'pending')); ?></span></p></div>
            </div>
        </div>

        <div class="card dashboard-section" style="margin-bottom:1.5rem">
            <h2 style="font-size:1.15rem;margin-bottom:1.25rem"><i class="fas fa-university" style="color:var(--emerald);margin-right:8px"></i> Bank Details</h2>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.25rem">
                <div><p class="muted">Bank Name</p><p style="font-weight:600"><?= htmlspecialchars($profile['bank_name'] ?? 'Not set'); ?></p></div>
                <div><p class="muted">Account Number</p><p style="font-weight:600"><?= htmlspecialchars($profile['bank_account'] ?? 'Not set'); ?></p></div>
                <div><p class="muted">Branch</p><p style="font-weight:600"><?= htmlspecialchars($profile['bank_branch'] ?? 'Not set'); ?></p></div>
            </div>
        </div>

        <!-- Edit form (also serves the "Settings" sidebar entry) -->
        <section class="card dashboard-section" id="edit">
            <h2 style="font-size:1.15rem;margin-bottom:1.25rem"><i class="fas fa-cog" style="color:var(--emerald);margin-right:8px"></i> Update Details (Settings)</h2>
            <form method="post" action="<?= url('app/actions/farmer_profile_update.php'); ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('farmer_profile'), ENT_QUOTES); ?>">

                <h3 class="form-section-title">Personal</h3>
                <div class="form-row">
                    <div class="form-group"><label>Full Name</label><input type="text" name="full_name" value="<?= htmlspecialchars($account['full_name'] ?? ''); ?>" required></div>
                    <div class="form-group"><label>Phone</label><input type="text" name="phone" value="<?= htmlspecialchars($account['phone'] ?? ''); ?>"></div>
                </div>

                <h3 class="form-section-title">Farm</h3>
                <div class="form-row">
                    <div class="form-group"><label>Farm Name</label><input type="text" name="farm_name" value="<?= $val('farm_name'); ?>"></div>
                    <div class="form-group"><label>Location / District</label><input type="text" name="location" value="<?= $val('location'); ?>"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Farm Size (acres)</label><input type="number" step="0.01" min="0" name="land_area" value="<?= htmlspecialchars((string)($profile['land_area'] ?? '')); ?>"></div>
                    <div class="form-group"><label>Monthly Income (BDT)</label><input type="number" step="1" min="0" name="monthly_income" value="<?= htmlspecialchars((string)($profile['monthly_income'] ?? '')); ?>"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Soil Type</label><input type="text" name="soil_type" value="<?= $val('soil_type'); ?>"></div>
                    <div class="form-group"><label>Irrigation</label><input type="text" name="irrigation" value="<?= $val('irrigation'); ?>"></div>
                </div>

                <h3 class="form-section-title">Bank</h3>
                <div class="form-row">
                    <div class="form-group"><label>Bank Name</label><input type="text" name="bank_name" value="<?= $val('bank_name'); ?>"></div>
                    <div class="form-group"><label>Account Number</label><input type="text" name="bank_account" value="<?= $val('bank_account'); ?>"></div>
                </div>
                <div class="form-group"><label>Branch</label><input type="text" name="bank_branch" value="<?= $val('bank_branch'); ?>"></div>

                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
            </form>
        </section>
    </main>
</div>

</body>
</html>
