<?php
$title = 'KrishiConnect - Connecting Farmers, Buyers & Finance';
$active = 'home';
require_once __DIR__ . '/app/includes/header.php';

// Live platform metrics for the stats band — real values, not placeholders.
$pdo = db();
$statFarmers   = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'farmer'")->fetchColumn();
$statBuyers    = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'buyer' AND status = 'active'")->fetchColumn();
$statLoans     = (float)$pdo->query("SELECT COALESCE(SUM(approved_amount), 0) FROM loans WHERE status IN ('active', 'closed')")->fetchColumn();
$statDistricts = (int)$pdo->query("SELECT COUNT(DISTINCT location) FROM farmer_profiles WHERE location IS NOT NULL AND location <> ''")->fetchColumn();
$statMarkets   = (int)$pdo->query("SELECT COUNT(DISTINCT region) FROM market_prices")->fetchColumn();

// On-time repayment rate: of settled installments (paid or late), the share
// paid on or before their due date. Defaults to 100% when nothing is settled.
$repay = $pdo->query(
    "SELECT
        COALESCE(SUM(status = 'paid'), 0) AS paid,
        COALESCE(SUM(status = 'late'), 0) AS late,
        COALESCE(SUM(status = 'paid' AND paid_at IS NOT NULL AND paid_at <= due_date), 0) AS on_time
     FROM loan_payments"
)->fetch();
$settled = (int)$repay['paid'] + (int)$repay['late'];
$statOnTime = $settled > 0 ? (int)round(100 * (int)$repay['on_time'] / $settled) : 100;

// Real featured listings for the homepage produce grid.
$featuredProducts = $pdo->query(
    "SELECT p.id, p.name, p.local_name, p.price, p.unit, p.rating, p.is_organic, p.image_url, c.name AS category_name,
            (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS image_path
     FROM products p
     JOIN categories c ON c.id = p.category_id
     WHERE p.status = 'active' AND p.product_status = 'approved'
     ORDER BY p.is_featured DESC, p.rating DESC, p.created_at DESC
     LIMIT 4"
)->fetchAll();
?>

<section class="design-hero">
    <div class="container design-hero-grid">
        <div>
            <div class="design-eyebrow"><i class="fas fa-leaf"></i> Empowering Farmers Nationwide</div>
            <h1>Connecting Farmers Directly to <span>Buyers &amp; Financial Support</span></h1>
            <p>KrishiConnect brings market prices, direct buyers, and trusted microfinance into one platform built for Bangladeshi farmers.</p>
            <div class="design-actions">
                <a href="<?= url('pages/register.php'); ?>" class="btn btn-accent">Get Started <i class="fas fa-arrow-right"></i></a>
                <a href="<?= url('pages/login.php'); ?>" class="btn btn-ghost-light">Sign in</a>
            </div>
        </div>
        <div class="hero-stats-card">
            <div class="hero-stats-grid">
                <div><strong><?= number_format($statFarmers); ?></strong><span>Farmers onboarded</span></div>
                <div><strong>BDT <?= number_format($statLoans); ?></strong><span>Disbursed in loans</span></div>
                <div><strong><?= number_format($statMarkets); ?></strong><span>Markets tracked</span></div>
                <div><strong><?= $statOnTime; ?>%</strong><span>On-time repayment</span></div>
            </div>
            <div class="logistics-chip"><i class="fas fa-truck"></i> Logistics partners in all 8 divisions</div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-header compact-left">
            <h2>Everything you need in one place</h2>
        </div>
        <div class="featured-grid">
            <div class="card feature-card">
                <div class="icon-wrap"><i class="fas fa-chart-line"></i></div>
                <h3>Live Market Prices</h3>
                <p>Daily updated rates from local and regional markets across Bangladesh.</p>
            </div>
            <div class="card feature-card">
                <div class="icon-wrap"><i class="fas fa-hand-holding-usd"></i></div>
                <h3>Microfinance Loans</h3>
                <p>Apply for small loans with transparent rates and easy approval.</p>
            </div>
            <div class="card feature-card">
                <div class="icon-wrap"><i class="fas fa-users"></i></div>
                <h3>Direct Buyer Access</h3>
                <p>Sell directly to verified buyers with no middlemen and better prices.</p>
            </div>
            <div class="card feature-card">
                <div class="icon-wrap"><i class="fas fa-shield-halved"></i></div>
                <h3>Secure &amp; Verified</h3>
                <p>KYC-verified farmers, buyers, and finance officers.</p>
            </div>
        </div>
    </div>
</section>

<section class="section section-alt">
    <div class="container">
        <div class="section-header">
            <span class="section-label">Fresh from Farms</span>
            <h2>Seasonal Vegetables</h2>
            <p>Fresh produce directly from farms across Bangladesh</p>
        </div>
        <div class="product-grid">
            <?php if (!$featuredProducts): ?>
                <div class="card" style="grid-column:1/-1;text-align:center">Fresh produce will appear here as farmers list it.</div>
            <?php endif; ?>
            <?php foreach ($featuredProducts as $product): ?>
                <a class="product-card" href="<?= url('pages/product.php?id=' . (int)$product['id']); ?>" style="color:inherit">
                    <div class="img-wrap">
                        <img src="<?= product_image_src($product); ?>" alt="<?= htmlspecialchars($product['name']); ?>">
                        <span class="badge"><?= htmlspecialchars($product['category_name']); ?></span>
                    </div>
                    <div class="details">
                        <h3><?= htmlspecialchars($product['name']); ?></h3>
                        <p class="origin"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($product['local_name'] ?? '') ?: 'Verified Farm'; ?></p>
                        <div class="price-row">
                            <span class="price">BDT <?= number_format((float)$product['price'], 0); ?>/<?= htmlspecialchars($product['unit']); ?></span>
                            <span class="rating"><i class="fas fa-star"></i> <?= number_format((float)$product['rating'], 1); ?></span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        <div style="text-align:center;margin-top:2.5rem">
            <a href="<?= url('pages/marketplace.php'); ?>" class="btn btn-outline"><i class="fas fa-th"></i> Browse All Products</a>
        </div>
    </div>
</section>

<section class="stats-section">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-item"><h3><?= number_format($statFarmers); ?></h3><p>Registered Farmers</p></div>
            <div class="stat-item"><h3><?= number_format($statBuyers); ?></h3><p>Active Buyers</p></div>
            <div class="stat-item"><h3>BDT <?= number_format($statLoans); ?></h3><p>Loans Disbursed</p></div>
            <div class="stat-item"><h3><?= number_format($statDistricts); ?></h3><p>Districts Covered</p></div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-header">
            <span class="section-label">Impact Stories</span>
            <h2>Transforming Lives</h2>
            <p>How KrishiConnect is making a difference for Bangladeshi farmers</p>
        </div>
        <div class="stories-grid">
            <div class="story-card">
                <div class="img-wrap"><img src="<?= asset_url('images/farmers/farmer1.jpg'); ?>" alt="Farmer Rahman"></div>
                <div class="content">
                    <h3>Rahman's Journey to Self-Reliance</h3>
                    <p>After receiving a BDT 50,000 loan through KrishiConnect, Rahman from Sylhet expanded his farm and increased income by 40% within 6 months.</p>
                    <a href="<?= url('pages/blog.php'); ?>" class="btn btn-outline btn-sm">Read More <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
            <div class="story-card">
                <div class="img-wrap"><img src="<?= asset_url('images/farmers/farmer2.jpg'); ?>" alt="Farmer Fatima"></div>
                <div class="content">
                    <h3>Fatima's Market Access Success</h3>
                    <p>Fatima from Khulna found direct buyers for her organic vegetables and built a stable base of repeat customers.</p>
                    <a href="<?= url('pages/blog.php'); ?>" class="btn btn-outline btn-sm">Read More <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
            <div class="story-card">
                <div class="img-wrap"><img src="<?= asset_url('images/farmers/farmer3.jpg'); ?>" alt="Farmer Karim"></div>
                <div class="content">
                    <h3>Karim's Community Impact</h3>
                    <p>With a KrishiConnect microfinance loan, Karim started a cooperative that now supplies vegetables to Dhaka restaurants.</p>
                    <a href="<?= url('pages/blog.php'); ?>" class="btn btn-outline btn-sm">Read More <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="stats-section cta-band">
    <div class="container">
        <h2>Ready to Grow Your Agricultural Business?</h2>
        <p>Join thousands of farmers and buyers who are already benefiting from KrishiConnect.</p>
        <div class="design-actions center">
            <a href="<?= url('pages/register.php'); ?>" class="btn btn-accent"><i class="fas fa-user-plus"></i> Get Started Free</a>
            <a href="<?= url('pages/about.php'); ?>" class="btn btn-ghost-light">Learn More</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/app/includes/footer.php'; ?>
