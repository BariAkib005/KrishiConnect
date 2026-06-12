<?php
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/includes/helpers.php';
require_once __DIR__ . '/../app/includes/db.php';

$user = require_role('farmer');
$pdo = db();

$profStmt = $pdo->prepare('SELECT location, soil_type FROM farmer_profiles WHERE user_id = ?');
$profStmt->execute([(int)$user['id']]);
$profile = $profStmt->fetch() ?: [];
$location = $profile['location'] ?? 'Bangladesh';

// Lightweight market price reference (seeded if available).
$prices = $pdo->query('SELECT crop_name, region, unit, price, previous_price FROM market_prices ORDER BY reported_on DESC LIMIT 6')->fetchAll();

// Static, demo-friendly weather + recommendation data keyed loosely to season.
$month = (int)date('n');
$season = $month >= 6 && $month <= 10 ? 'Monsoon' : ($month >= 11 || $month <= 2 ? 'Winter (Rabi)' : 'Summer (Kharif-1)');

$weatherAlerts = [
    ['icon' => 'fa-cloud-showers-heavy', 'level' => 'badge-warning', 'title' => 'Heavy Rain Likely', 'body' => "Scattered showers expected around {$location} over the next 48 hours. Ensure field drainage and delay pesticide spraying."],
    ['icon' => 'fa-temperature-high', 'level' => 'badge-danger', 'title' => 'Heat Advisory', 'body' => 'Daytime temperatures may exceed 35°C midweek. Irrigate early morning or late evening to reduce crop stress.'],
    ['icon' => 'fa-wind', 'level' => 'badge-info', 'title' => 'Breezy Conditions', 'body' => 'Moderate winds forecast — a good window for natural drying of harvested grain and spices.'],
];

$recommendations = [
    ['crop' => 'Aman Rice', 'why' => 'Best suited for the current monsoon water availability and your loamy soil.', 'window' => 'Transplant by mid-July'],
    ['crop' => 'Jute', 'why' => 'High market demand and tolerant of waterlogged conditions this season.', 'window' => 'Sow now'],
    ['crop' => 'Leafy Vegetables (Lal Shak, Pui)', 'why' => 'Fast 30–40 day cycle for quick cash flow between main crops.', 'window' => 'Any time'],
    ['crop' => 'Turmeric & Ginger', 'why' => 'Strong spice prices and good fit for shaded, well-drained plots.', 'window' => 'Plant by end of season'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Market Insights &amp; Smart Agri - KrishiConnect</title>
    <link rel="stylesheet" href="<?= asset_url('css/styles.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<div class="dashboard-layout">
    <?php $active = 'market-insights'; require __DIR__ . '/../app/includes/farmer_sidebar.php'; ?>

    <main class="main-content">
        <div class="dash-header">
            <div>
                <h1>Market Insights &amp; Smart Agri</h1>
                <p>Weather alerts and crop recommendations for <?= htmlspecialchars($location); ?> · <?= htmlspecialchars($season); ?> season.</p>
            </div>
        </div>

        <!-- Weather alerts -->
        <h2 style="font-size:1.2rem;margin:0 0 1rem"><i class="fas fa-cloud-sun-rain" style="color:var(--gold);margin-right:8px"></i> Weather Alerts</h2>
        <div class="dash-cards">
            <?php foreach ($weatherAlerts as $alert): ?>
                <div class="card">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.5rem">
                        <h3 style="font-size:1rem"><i class="fas <?= $alert['icon']; ?>" style="color:var(--emerald);margin-right:6px"></i> <?= htmlspecialchars($alert['title']); ?></h3>
                        <span class="badge-status <?= $alert['level']; ?>">Alert</span>
                    </div>
                    <p style="color:var(--gray);font-size:.9rem"><?= htmlspecialchars($alert['body']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Crop recommendations -->
        <h2 style="font-size:1.2rem;margin:1.5rem 0 1rem"><i class="fas fa-seedling" style="color:var(--emerald);margin-right:8px"></i> Recommended Crops</h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Crop</th><th>Why It Fits</th><th>Planting Window</th></tr></thead>
                <tbody>
                    <?php foreach ($recommendations as $rec): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($rec['crop']); ?></strong></td>
                            <td><?= htmlspecialchars($rec['why']); ?></td>
                            <td><span class="badge-status badge-info"><?= htmlspecialchars($rec['window']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Market prices -->
        <h2 style="font-size:1.2rem;margin:1.5rem 0 1rem"><i class="fas fa-chart-line" style="color:var(--navy);margin-right:8px"></i> Market Price Watch</h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Crop</th><th>Region</th><th>Price</th><th>Trend</th></tr></thead>
                <tbody>
                    <?php if (!$prices): ?>
                        <tr>
                            <td>Potato</td><td>Munshiganj</td><td>BDT 28/kg</td><td><span class="badge-status badge-success"><i class="fas fa-arrow-up"></i> Rising</span></td>
                        </tr>
                        <tr>
                            <td>Onion</td><td>Pabna</td><td>BDT 90/kg</td><td><span class="badge-status badge-success"><i class="fas fa-arrow-up"></i> Rising</span></td>
                        </tr>
                        <tr>
                            <td>Rice (Miniket)</td><td>Naogaon</td><td>BDT 72/kg</td><td><span class="badge-status badge-warning"><i class="fas fa-minus"></i> Stable</span></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($prices as $price): ?>
                            <?php $up = (float)$price['price'] >= (float)$price['previous_price']; ?>
                            <tr>
                                <td><?= htmlspecialchars($price['crop_name']); ?></td>
                                <td><?= htmlspecialchars($price['region']); ?></td>
                                <td>BDT <?= number_format((float)$price['price'], 0); ?>/<?= htmlspecialchars($price['unit']); ?></td>
                                <td><span class="badge-status <?= $up ? 'badge-success' : 'badge-danger'; ?>"><i class="fas fa-arrow-<?= $up ? 'up' : 'down'; ?>"></i> <?= $up ? 'Rising' : 'Falling'; ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

</body>
</html>
