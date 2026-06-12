<?php
$title = 'Blog - KrishiConnect';
$active = '';
require_once __DIR__ . '/../app/includes/header.php';
require_once __DIR__ . '/../app/includes/db.php';

$posts = db()->query('SELECT title, excerpt, published_at FROM blog_posts WHERE status = "published" ORDER BY published_at DESC, created_at DESC LIMIT 12')->fetchAll();
?>

<section class="section">
    <div class="container">
        <div class="section-header">
            <span class="section-label">Blog</span>
            <h2>Stories From the Field</h2>
            <p>Updates, farmer stories, and practical market insights.</p>
        </div>
        <div class="featured-grid">
            <?php if (!$posts): ?>
                <div class="card" style="grid-column:1/-1;text-align:center">No blog posts have been published yet.</div>
            <?php endif; ?>
            <?php foreach ($posts as $post): ?>
                <article class="card">
                    <h3><?= htmlspecialchars($post['title']); ?></h3>
                    <p><?= htmlspecialchars($post['excerpt'] ?: 'Read the latest KrishiConnect update.'); ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../app/includes/footer.php'; ?>
