<?php
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/includes/helpers.php';

require_role('admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics — KrishiConnect</title>
    <link rel="stylesheet" href="<?= asset_url('css/styles.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<?php require __DIR__ . '/../app/includes/header.php'; ?>

<section class="section">
    <div class="container">
        <div class="dash-header">
            <div><h1>Reports &amp; Analytics</h1><p>Comprehensive platform performance insights</p></div>
            <div><button class="btn btn-primary btn-sm"><i class="fas fa-download"></i> Export Report</button></div>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="stat-card"><div class="stat-icon green"><i class="fas fa-wallet"></i></div><div class="stat-value">৳48.5L</div><div class="stat-label">Total Revenue</div></div>
            <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-box"></i></div><div class="stat-value">1,245</div><div class="stat-label">Total Orders</div></div>
            <div class="stat-card"><div class="stat-icon green"><i class="fas fa-users"></i></div><div class="stat-value">12,543</div><div class="stat-label">Active Users</div></div>
            <div class="stat-card"><div class="stat-icon orange"><i class="fas fa-hand-holding-usd"></i></div><div class="stat-value">৳48L</div><div class="stat-label">Loans Disbursed</div></div>
        </div>

        <div class="card" style="margin-top:2rem">
            <h2>Revenue Breakdown</h2>
            <div class="revenue-bars">
                <div class="bar-row"><span>Jan</span><div class="bar"><div style="width:65%"></div></div><span>৳1.55M</span></div>
                <div class="bar-row"><span>Feb</span><div class="bar"><div style="width:70%"></div></div><span>৳1.62M</span></div>
                <div class="bar-row"><span>Mar</span><div class="bar"><div style="width:78%"></div></div><span>৳1.98M</span></div>
                <div class="bar-row"><span>Apr</span><div class="bar"><div style="width:85%"></div></div><span>৳2.32M</span></div>
                <div class="bar-row"><span>May</span><div class="bar"><div style="width:92%"></div></div><span>৳2.45M</span></div>
            </div>
        </div>

        <div class="grid lg:grid-cols-2 gap-6" style="margin-top:2rem">
            <div class="card">
                <h2>Top Products</h2>
                <ul class="rank-list">
                    <li><span>Organic Tomatoes</span><strong>৳558k</strong></li>
                    <li><span>Basmati Rice</span><strong>৳722k</strong></li>
                    <li><span>Fresh Mangoes</span><strong>৳744k</strong></li>
                    <li><span>Green Chilies</span><strong>৳288k</strong></li>
                </ul>
            </div>
            <div class="card">
                <h2>Top Farmers</h2>
                <ul class="rank-list">
                    <li><span>Rafiq Ahmed</span><strong>৳245k</strong></li>
                    <li><span>Jamal Islam</span><strong>৳198k</strong></li>
                    <li><span>Shahida Devi</span><strong>৳187k</strong></li>
                    <li><span>Vijay Hossain</span><strong>৳165k</strong></li>
                </ul>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../app/includes/footer.php'; ?>
</body>
</html>
