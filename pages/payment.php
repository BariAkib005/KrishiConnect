<?php
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/includes/helpers.php';
require_once __DIR__ . '/../app/includes/db.php';
require_once __DIR__ . '/../app/includes/sslcommerz.php';

require_login();
$user = current_user();
$orderId = (int)($_GET['order_id'] ?? 0);

$pdo = db();
$stmt = $pdo->prepare('SELECT id, total_amount, payment_status, status FROM orders WHERE id = ? AND buyer_id = ?');
$stmt->execute([$orderId, (int)$user['id']]);
$order = $stmt->fetch();
if (!$order) {
    redirect('pages/cart.php');
}

$gateway = sslcommerz_enabled();
$error = $_GET['error'] ?? '';
$errorMessages = [
    'failed' => 'Your payment failed or was declined. Please try again.',
    'cancelled' => 'You cancelled the payment. You can try again whenever you are ready.',
    'validation' => 'We could not verify your payment. If you were charged, please contact support before retrying.',
    'gateway' => 'Could not reach the payment gateway. Please try again in a moment.',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment — KrishiConnect</title>
    <link rel="stylesheet" href="<?= asset_url('css/styles.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<?php require __DIR__ . '/../app/includes/header.php'; ?>

<section class="section">
    <div class="container" style="max-width:820px">
        <div class="section-header" style="margin-bottom:2rem">
            <h2>Payment</h2>
            <p>Complete your purchase securely</p>
        </div>

        <?php if ($error !== ''): ?>
            <div class="notice error"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($errorMessages[$error] ?? 'Something went wrong with your payment.'); ?></div>
        <?php endif; ?>
        <?php if ($gateway): ?>
            <div class="notice"><i class="fas fa-lock"></i> Secure payment via <strong>SSLCommerz (Sandbox)</strong>. You will be redirected to the gateway to pay with test cards or mobile banking.</div>
        <?php endif; ?>

        <div class="card" style="margin-bottom:1.5rem">
            <h3 style="margin-bottom:1rem">Order Summary</h3>
            <div class="summary-row"><span>Subtotal</span><span>৳<?= number_format($order['total_amount'] - 150, 0); ?></span></div>
            <div class="summary-row"><span>Delivery Fee</span><span>৳150</span></div>
            <div class="summary-row total"><span>Total Amount</span><span>৳<?= number_format($order['total_amount'], 0); ?></span></div>
        </div>

        <form method="post" action="<?= url('app/actions/pay.php'); ?>" class="card">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('pay'), ENT_QUOTES); ?>">
            <input type="hidden" name="order_id" value="<?= (int)$order['id']; ?>">
            <?php if ($gateway): ?>
                <h3 style="margin-bottom:1rem">Pay securely</h3>
                <p style="color:var(--gray);margin-bottom:1rem">Choose your exact method (bKash, Nagad, card, etc.) on the next secure SSLCommerz page.</p>
                <input type="hidden" name="payment_method" value="SSLCommerz">
            <?php else: ?>
                <h3 style="margin-bottom:1rem">Select Payment Method</h3>
                <div class="payment-options">
                    <label class="payment-card">
                        <input type="radio" name="payment_method" value="mobile" checked>
                        <div>
                            <strong>Mobile Banking</strong>
                            <p>bKash, Nagad, Rocket</p>
                        </div>
                    </label>
                    <label class="payment-card">
                        <input type="radio" name="payment_method" value="card">
                        <div>
                            <strong>Credit/Debit Card</strong>
                            <p>Visa, Mastercard, AmEx</p>
                        </div>
                    </label>
                    <label class="payment-card">
                        <input type="radio" name="payment_method" value="bank">
                        <div>
                            <strong>Bank Transfer</strong>
                            <p>Direct bank account transfer</p>
                        </div>
                    </label>
                </div>
            <?php endif; ?>
            <button class="btn btn-primary btn-block" type="submit" style="margin-top:1.5rem"><?= $gateway ? 'Proceed to Pay' : 'Pay'; ?> ৳<?= number_format($order['total_amount'], 0); ?></button>
        </form>
    </div>
</section>

<?php require __DIR__ . '/../app/includes/footer.php'; ?>
</body>
</html>
