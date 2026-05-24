<?php
require_once __DIR__ . '/helpers.php';
?>
<footer>
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col">
                <div class="logo" style="margin-bottom:1rem">
                    <span class="brand-mark"><i class="fas fa-seedling"></i></span>
                    <span>KrishiConnect</span>
                </div>
                <p>Empowering farmers across Bangladesh.</p>
                <div class="social-links">
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            <div class="footer-col">
                <h3>Platform</h3>
                <a href="<?= url('pages/marketplace.php'); ?>">Marketplace</a>
                <a href="<?= url('pages/microfinance.php'); ?>">Loans</a>
                <a href="<?= url('pages/dashboard.php'); ?>">Market Prices</a>
            </div>
            <div class="footer-col">
                <h3>Company</h3>
                <a href="<?= url('pages/about.php'); ?>">About Us</a>
                <a href="<?= url('pages/contact.php'); ?>">Contact</a>
                <a href="<?= url('pages/blog.php'); ?>">Blog</a>
            </div>
            <div class="footer-col">
                <h3>Support</h3>
                <a href="<?= url('pages/faq.php'); ?>">FAQ</a>
                <a href="<?= url('pages/contact.php'); ?>">Help Center</a>
                <a href="#">Privacy Policy</a>
            </div>
        </div>
        <div class="footer-bottom">
            <span>&copy; 2026 KrishiConnect - Made for Bangladesh agriculture</span>
            <span>Privacy Policy - Terms</span>
        </div>
    </div>
</footer>
</body>
</html>
