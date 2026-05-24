<?php
$title = 'Contact - KrishiConnect';
$active = '';
require_once __DIR__ . '/../app/includes/header.php';
?>

<section class="section">
    <div class="container">
        <div class="form-container" style="max-width:720px;margin:0 auto">
            <h2>Contact Support</h2>
            <p class="subtitle" style="color:var(--gray);margin-bottom:1.5rem">Send a message to the KrishiConnect support team.</p>
            <form method="post" action="#">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" rows="5" required></textarea>
                </div>
                <button class="btn btn-primary" type="submit">Send Message</button>
            </form>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
