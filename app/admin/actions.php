<?php
require_once __DIR__ . '/../includes/auth.php';

function admin_toggle_user_status(PDO $pdo, int $adminId, int $targetUserId): bool
{
    if ($targetUserId <= 0 || $targetUserId === $adminId) {
        return false;
    }

    $stmt = $pdo->prepare('SELECT id, status FROM users WHERE id = :id AND role <> :admin_role LIMIT 1');
    $stmt->execute([
        ':id' => $targetUserId,
        ':admin_role' => 'admin',
    ]);
    $target = $stmt->fetch();

    if (!$target) {
        return false;
    }

    $newStatus = $target['status'] === 'suspended' ? 'active' : 'suspended';

    $update = $pdo->prepare('UPDATE users SET status = :status WHERE id = :id');
    $update->execute([
        ':status' => $newStatus,
        ':id' => $targetUserId,
    ]);

    write_security_log(
        $adminId,
        'user_suspension',
        sprintf('User ID %d status changed to %s.', $targetUserId, $newStatus)
    );

    return true;
}

function admin_fetch_pending_products(PDO $pdo): array
{
    $stmt = $pdo->prepare(
        'SELECT p.id, p.name, p.price, p.unit, p.quantity_available, p.product_status,
                p.created_at, u.full_name AS farmer_name, c.name AS category_name
         FROM products p
         JOIN users u ON u.id = p.farmer_id
         JOIN categories c ON c.id = p.category_id
         WHERE p.product_status = :status
         ORDER BY p.created_at DESC'
    );
    $stmt->execute([':status' => 'pending']);

    return $stmt->fetchAll();
}

function admin_update_product_status(PDO $pdo, int $adminId, int $productId, string $decision): bool
{
    if (!in_array($decision, ['approved', 'rejected'], true)) {
        return false;
    }

    $listingStatus = $decision === 'approved' ? 'active' : 'inactive';
    $stmt = $pdo->prepare(
        'UPDATE products
         SET product_status = :product_status,
             status = :listing_status
         WHERE id = :id'
    );
    $stmt->execute([
        ':product_status' => $decision,
        ':listing_status' => $listingStatus,
        ':id' => $productId,
    ]);

    if ($stmt->rowCount() < 1) {
        return false;
    }

    write_security_log(
        $adminId,
        $decision === 'approved' ? 'product_approval' : 'product_removal',
        sprintf('Product ID %d was %s.', $productId, $decision)
    );

    return true;
}

function admin_financial_stats(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT
            loans_summary.total_disbursed,
            repayments_summary.total_repaid,
            payments_summary.total_platform_revenue
         FROM
            (SELECT COALESCE(SUM(approved_amount), 0) AS total_disbursed
             FROM loans
             WHERE status IN ("active", "closed")) loans_summary
         CROSS JOIN
            (SELECT COALESCE(SUM(lp.amount), 0) AS total_repaid
             FROM loan_payments lp
             JOIN loans l ON l.id = lp.loan_id
             WHERE lp.status = "paid") repayments_summary
         CROSS JOIN
            (SELECT COALESCE(SUM(p.amount), 0) AS total_platform_revenue
             FROM payments p
             JOIN orders o ON o.id = p.order_id
             WHERE p.status = "success") payments_summary'
    );

    return $stmt->fetch() ?: [
        'total_disbursed' => 0,
        'total_repaid' => 0,
        'total_platform_revenue' => 0,
    ];
}

function render_security_logs_table(PDO $pdo): void
{
    $stmt = $pdo->prepare(
        'SELECT sl.id, sl.user_id, sl.action, sl.details, sl.ip_address, sl.created_at,
                u.full_name AS user_name
         FROM security_logs sl
         LEFT JOIN users u ON u.id = sl.user_id
         ORDER BY sl.created_at DESC
         LIMIT 50'
    );
    $stmt->execute();
    $logs = $stmt->fetchAll();
    ?>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Details</th>
                    <th>IP Address</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$logs): ?>
                    <tr><td colspan="6" class="text-center text-muted">No security logs found.</td></tr>
                <?php endif; ?>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= (int)$log['id']; ?></td>
                        <td><?= htmlspecialchars($log['user_name'] ?: ('User #' . ($log['user_id'] ?? 'Guest'))); ?></td>
                        <td><?= htmlspecialchars($log['action']); ?></td>
                        <td><?= htmlspecialchars($log['details'] ?? ''); ?></td>
                        <td><?= htmlspecialchars($log['ip_address'] ?? ''); ?></td>
                        <td><?= htmlspecialchars($log['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function admin_handle_post_actions(PDO $pdo, int $adminId, array $post): bool
{
    if (!verify_csrf_token($post['csrf_token'] ?? null, 'admin_actions')) {
        return false;
    }

    if (isset($post['toggle_status'], $post['target_user_id'])) {
        return admin_toggle_user_status($pdo, $adminId, (int)$post['target_user_id']);
    }

    if (isset($post['product_id'], $post['product_decision'])) {
        return admin_update_product_status($pdo, $adminId, (int)$post['product_id'], (string)$post['product_decision']);
    }

    return false;
}
