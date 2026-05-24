<?php
$title = 'FAQ - KrishiConnect';
$active = '';
require_once __DIR__ . '/../app/includes/header.php';
?>

<section class="section">
    <div class="container">
        <div class="section-header">
            <span class="section-label">FAQ</span>
            <h2>Common Questions</h2>
            <p>Quick answers for farmers, buyers, and finance users.</p>
        </div>
        <div class="featured-grid">
            <div class="card">
                <h3>How do farmers sell products?</h3>
                <p>Farmers register, access the dashboard, and keep product listings updated for buyers.</p>
            </div>
            <div class="card">
                <h3>How do buyers place orders?</h3>
                <p>Buyers browse the marketplace, add items to the cart, and complete checkout.</p>
            </div>
            <div class="card">
                <h3>How are loans reviewed?</h3>
                <p>Finance users review submitted loan applications from the finance dashboard.</p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
