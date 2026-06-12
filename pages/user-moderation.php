<?php
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/includes/helpers.php';
require_once __DIR__ . '/../app/includes/db.php';

$user = require_role('admin');

$pdo = db();
$role = trim($_GET['role'] ?? 'all');
$search = trim($_GET['search'] ?? '');

$where = '1=1';
$params = [];
if ($role !== 'all') {
    $where .= ' AND role = ?';
    $params[] = $role;
}
if ($search !== '') {
    $where .= ' AND (full_name LIKE ? OR email LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$stmt = $pdo->prepare("SELECT id, full_name, email, role, status, created_at FROM users WHERE {$where} ORDER BY created_at DESC");
$stmt->execute($params);
$users = $stmt->fetchAll();

$statsTotal = (int)$pdo->query('SELECT COUNT(*) AS total FROM users')->fetch()['total'];
$statsFarmers = (int)$pdo->query("SELECT COUNT(*) AS total FROM users WHERE role = 'farmer' AND status = 'active'")->fetch()['total'];
$statsBuyers = (int)$pdo->query("SELECT COUNT(*) AS total FROM users WHERE role = 'buyer' AND status = 'active'")->fetch()['total'];
$statsSuspended = (int)$pdo->query("SELECT COUNT(*) AS total FROM users WHERE status = 'suspended'")->fetch()['total'];
$statsPending = (int)$pdo->query("SELECT COUNT(*) AS total FROM users WHERE status = 'pending'")->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Moderation — KrishiConnect</title>
    <link rel="stylesheet" href="<?= asset_url('css/styles.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<?php require __DIR__ . '/../app/includes/header.php'; ?>

<section class="section">
    <div class="container">
        <div class="dash-header">
            <div><h1>User Moderation</h1><p>Manage and moderate all platform users</p></div>
        </div>

        <!-- Summary metrics: responsive grid keeps these four cards side-by-side on
             desktop (4-up), 2-up on tablet, and stacked on mobile. -->
        <div class="moderation-stats">
            <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-users"></i></div><div class="stat-value"><?= number_format($statsTotal); ?></div><div class="stat-label">Total Users</div></div>
            <div class="stat-card"><div class="stat-icon green"><i class="fas fa-user-check"></i></div><div class="stat-value"><?= number_format($statsFarmers); ?></div><div class="stat-label">Active Farmers</div></div>
            <div class="stat-card"><div class="stat-icon green"><i class="fas fa-user-check"></i></div><div class="stat-value"><?= number_format($statsBuyers); ?></div><div class="stat-label">Active Buyers</div></div>
            <div class="stat-card"><div class="stat-icon gold"><i class="fas fa-user-clock"></i></div><div class="stat-value"><?= number_format($statsPending); ?></div><div class="stat-label">Pending Approval</div></div>
            <div class="stat-card"><div class="stat-icon red"><i class="fas fa-user-slash"></i></div><div class="stat-value"><?= number_format($statsSuspended); ?></div><div class="stat-label">Suspended</div></div>
        </div>

        <form class="moderation-filters" method="get" action="<?= url('pages/user-moderation.php'); ?>">
            <div class="input-icon">
                <i class="fas fa-search"></i>
                <input type="text" name="search" value="<?= htmlspecialchars($search); ?>" placeholder="Search by name or email">
            </div>
            <select name="role">
                <option value="all" <?= $role === 'all' ? 'selected' : ''; ?>>All Roles</option>
                <option value="farmer" <?= $role === 'farmer' ? 'selected' : ''; ?>>Farmers</option>
                <option value="buyer" <?= $role === 'buyer' ? 'selected' : ''; ?>>Buyers</option>
                <option value="finance" <?= $role === 'finance' ? 'selected' : ''; ?>>Finance Officers</option>
                <option value="admin" <?= $role === 'admin' ? 'selected' : ''; ?>>Admins</option>
            </select>
            <button class="btn btn-outline" type="submit">Filter</button>
        </form>

        <div class="table-wrap">
            <h2><i class="fas fa-shield-alt" style="color:var(--emerald);margin-right:8px"></i> Users</h2>
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$users): ?>
                        <tr><td colspan="5">No users found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $row): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($row['full_name']); ?></strong><br>
                                    <span class="muted"><?= htmlspecialchars($row['email']); ?></span>
                                </td>
                                <td><span class="badge-status badge-info"><?= htmlspecialchars(ucfirst($row['role'])); ?></span></td>
                                <td>
                                    <span class="badge-status <?= $row['status'] === 'active' ? 'badge-success' : ($row['status'] === 'suspended' ? 'badge-danger' : 'badge-warning'); ?>">
                                        <?= htmlspecialchars(ucfirst($row['status'])); ?>
                                    </span>
                                </td>
                                <td><?= date('M j, Y', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <?php if ((int)$row['id'] === (int)$user['id']): ?>
                                        <span class="muted">You</span>
                                    <?php elseif ($row['status'] === 'suspended'): ?>
                                        <form method="post" action="<?= url('app/actions/user_revoke.php'); ?>">
                                            <?= csrf_field('app'); ?>
                                            <input type="hidden" name="user_id" value="<?= (int)$row['id']; ?>">
                                            <button class="btn btn-outline btn-sm" type="submit">Restore</button>
                                        </form>
                                    <?php elseif ($row['status'] === 'pending'): ?>
                                        <form method="post" action="<?= url('app/actions/user_revoke.php'); ?>">
                                            <?= csrf_field('app'); ?>
                                            <input type="hidden" name="user_id" value="<?= (int)$row['id']; ?>">
                                            <button class="btn btn-primary btn-sm" type="submit">Approve</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" action="<?= url('app/actions/user_suspend.php'); ?>">
                                            <?= csrf_field('app'); ?>
                                            <input type="hidden" name="user_id" value="<?= (int)$row['id']; ?>">
                                            <button class="btn btn-outline btn-sm" type="submit" style="color:var(--red);border-color:var(--red)">Suspend</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../app/includes/footer.php'; ?>
</body>
</html>
