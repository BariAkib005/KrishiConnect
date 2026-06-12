<?php
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/includes/cart.php';
require_once __DIR__ . '/../app/includes/helpers.php';

require_login();
require_once __DIR__ . '/../app/includes/db.php';
$user = current_user();
$items = get_cart_items((int)$user['id']);
$totals = cart_totals($items);
$error = $_GET['error'] ?? '';

// Pre-fill the delivery form from the buyer's saved address (if any), so they
// only have to enter it once. First-time buyers fall back to their account
// name/email/phone.
$savedStmt = db()->prepare('SELECT * FROM buyer_addresses WHERE user_id = ?');
$savedStmt->execute([(int)$user['id']]);
$saved = $savedStmt->fetch() ?: [];

// current_user() doesn't include phone, so fetch it for the first-time fallback.
$acctStmt = db()->prepare('SELECT phone FROM users WHERE id = ?');
$acctStmt->execute([(int)$user['id']]);
$acctPhone = (string)($acctStmt->fetch()['phone'] ?? '');

$field = static function (string $key, string $fallback = '') use ($saved): string {
    $value = $saved[$key] ?? '';
    return htmlspecialchars($value !== '' && $value !== null ? $value : $fallback, ENT_QUOTES);
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout — KrishiConnect</title>
    <link rel="stylesheet" href="<?= asset_url('css/styles.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<?php require __DIR__ . '/../app/includes/header.php'; ?>

<section class="section">
    <div class="container">
        <div class="section-header" style="margin-bottom:2rem">
            <h2>Checkout</h2>
            <p>Complete your order</p>
        </div>

        <div class="checkout-steps">
            <div class="step active">1<span>Delivery</span></div>
            <div class="step">2<span>Payment</span></div>
            <div class="step">3<span>Confirm</span></div>
        </div>

        <div class="cart-grid">
            <div class="card">
                <h3 style="margin-bottom:1.5rem"><i class="fas fa-map-marker-alt" style="color:var(--emerald);margin-right:8px"></i> Delivery Address</h3>
                <?php if ($error === 'missing'): ?>
                    <p style="color:var(--red);margin-bottom:1rem">Please fill in all required fields.</p>
                <?php elseif ($error === 'failed'): ?>
                    <p style="color:var(--red);margin-bottom:1rem">We couldn't place your order. Please try again.</p>
                <?php elseif ($error === 'csrf'): ?>
                    <p style="color:var(--red);margin-bottom:1rem">Your session expired. Please review your details and submit again.</p>
                <?php endif; ?>
                <form method="post" action="<?= url('app/actions/checkout.php'); ?>" class="form-stack"><?= csrf_field('app'); ?>
                    <?php if ($saved): ?>
                        <p class="notice" style="margin-bottom:1rem"><i class="fas fa-circle-info"></i> We filled in your saved delivery details — edit any field if it has changed.</p>
                    <?php endif; ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="full_name" value="<?= $field('full_name', $user['full_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Phone Number *</label>
                            <input type="text" name="phone" value="<?= $field('phone', $acctPhone); ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" value="<?= $field('email', $user['email'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Street Address *</label>
                        <input type="text" name="street" value="<?= $field('street'); ?>" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>City *</label>
                            <input type="text" name="city" value="<?= $field('city'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>State *</label>
                            <input type="text" name="state" value="<?= $field('state'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Postal Code *</label>
                            <input type="text" name="postal" value="<?= $field('postal'); ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Landmark (Optional)</label>
                        <input type="text" name="landmark" value="<?= $field('landmark'); ?>">
                    </div>
                    <button class="btn btn-primary btn-block" type="submit">Continue to Payment</button>
                </form>
            </div>

            <div class="cart-summary">
                <div class="summary-box">
                    <h3>Order Summary</h3>
                    <div class="summary-row"><span>Subtotal</span><span>৳<?= number_format($totals['subtotal'], 0); ?></span></div>
                    <div class="summary-row"><span>Delivery</span><span>৳<?= number_format($totals['delivery'], 0); ?></span></div>
                    <div class="summary-row total"><span>Total</span><span>৳<?= number_format($totals['total'], 0); ?></span></div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../app/includes/footer.php'; ?>
</body>
</html>
