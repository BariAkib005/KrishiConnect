<?php
$title = 'Admin PIN Login - KrishiConnect';
require_once __DIR__ . '/../app/includes/auth.php';

if (!empty($_SESSION['logged_in']) && ($_SESSION['user_type'] ?? '') === 'admin') {
    redirect('pages/admin-dashboard.php');
}

$error = $_GET['error'] ?? '';
$lockedUntil = (int)($_SESSION['admin_login_locked_until'] ?? 0);
$isLocked = $lockedUntil > time();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<main class="min-vh-100 d-flex align-items-center py-5">
    <section class="container" style="max-width: 440px;">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4 p-md-5">
                <h1 class="h4 mb-2 text-center">Admin PIN</h1>
                <p class="text-secondary text-center mb-4">KrishiConnect administration</p>

                <?php if ($error === 'invalid'): ?>
                    <div class="alert alert-danger">Invalid PIN.</div>
                <?php elseif ($error === 'locked' || $isLocked): ?>
                    <div class="alert alert-warning">Too many failed attempts. Please wait before trying again.</div>
                <?php elseif ($error === 'csrf'): ?>
                    <div class="alert alert-danger">Security token expired. Please try again.</div>
                <?php elseif ($error === 'inactive'): ?>
                    <div class="alert alert-danger">Admin account is not active.</div>
                <?php endif; ?>

                <form method="post" action="<?= url('app/actions/admin_login_process.php'); ?>" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('admin_login_form'), ENT_QUOTES); ?>">
                    <div class="mb-3">
                        <label for="admin_pin" class="form-label">6-digit PIN</label>
                        <input
                            type="password"
                            inputmode="numeric"
                            pattern="[0-9]{6}"
                            minlength="6"
                            maxlength="6"
                            autocomplete="one-time-code"
                            class="form-control form-control-lg text-center"
                            id="admin_pin"
                            name="admin_pin"
                            required
                            <?= $isLocked ? 'disabled' : ''; ?>
                        >
                    </div>
                    <button class="btn btn-success w-100 btn-lg" type="submit" <?= $isLocked ? 'disabled' : ''; ?>>Continue</button>
                </form>
            </div>
        </div>
    </section>
</main>
</body>
</html>
