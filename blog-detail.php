<?php
ob_start();
session_start();
require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/functions.php';

$slug = sanitize($_GET['slug'] ?? '');
if (!$slug) {
    redirect(SITE_URL . '/blog.php');
}

$stmt = $conn->prepare("SELECT * FROM blog WHERE slug = ? AND status = 'active'");
$stmt->bind_param('s', $slug);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$post) {
    redirect(SITE_URL . '/blog.php');
}

// Related posts: same category, not this post
$related_stmt = $conn->prepare(
    "SELECT * FROM blog WHERE category = ? AND slug != ? AND status = 'active' ORDER BY created_at DESC LIMIT 3"
);
$related_stmt->bind_param('ss', $post['category'], $slug);
$related_stmt->execute();
$related_posts = $related_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$related_stmt->close();

$page_title       = !empty($post['meta_title'])       ? $post['meta_title']       : $post['title'];
$meta_description = !empty($post['meta_description']) ? $post['meta_description'] : truncateText(strip_tags($post['excerpt'] ?? ''), 160);
require_once 'includes/header.php';
?>

<!-- Breadcrumb -->
<div class="container mt-3 mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/">Home</a></li>
            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/blog.php">Blog</a></li>
            <li class="breadcrumb-item active" aria-current="page">
                <?php echo htmlspecialchars(truncateText($post['title'], 60)); ?>
            </li>
        </ol>
    </nav>
</div>

<!-- Main Content -->
<section class="py-4 pb-5">
    <div class="container">
        <div class="row g-5">

            <!-- Article -->
            <div class="col-lg-8">

                <!-- Featured Image -->
                <?php if (!empty($post['image'])): ?>
                <div class="mb-4" style="max-height: 450px; overflow: hidden; border-radius: 8px;">
                    <img src="<?php echo UPLOADS_URL . 'blog/' . htmlspecialchars($post['image']); ?>"
                         alt="<?php echo htmlspecialchars($post['title']); ?>"
                         class="w-100" style="max-height: 450px; object-fit: cover;">
                </div>
                <?php endif; ?>

                <!-- Category Badge + Date -->
                <div class="d-flex align-items-center gap-3 mb-3">
                    <?php if (!empty($post['category'])): ?>
                    <span class="badge" style="background: #c9a96e; color: #fff; font-size: 0.8rem;">
                        <?php echo htmlspecialchars($post['category']); ?>
                    </span>
                    <?php endif; ?>
                    <small class="text-muted">
                        <i class="fas fa-calendar-alt me-1"></i>
                        <?php echo date('d M Y', strtotime($post['created_at'])); ?>
                    </small>
                </div>

                <!-- Title -->
                <h1 class="mb-4 fw-bold" style="font-family: 'Playfair Display', serif;">
                    <?php echo htmlspecialchars($post['title']); ?>
                </h1>

                <!-- Content -->
                <div class="blog-content" style="line-height: 1.9; color: #444;">
                    <?php echo $post['content']; ?>
                </div>

                <!-- Author Note -->
                <hr class="my-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width: 48px; height: 48px; background: linear-gradient(135deg, #c9a96e, #8b6914);">
                        <i class="fas fa-user text-white"></i>
                    </div>
                    <div>
                        <p class="mb-0 fw-semibold">Luxe Marble Team</p>
                        <small class="text-muted">Posted by our marble experts</small>
                    </div>
                </div>

                <!-- Back to Blog -->
                <div class="mt-4">
                    <a href="<?php echo SITE_URL; ?>/blog.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Blog
                    </a>
                </div>

            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">

                <!-- Related Posts -->
                <?php if (!empty($related_posts)): ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header" style="background: #c9a96e; color: #fff;">
                        <h6 class="mb-0 fw-bold">Related Posts</h6>
                    </div>
                    <div class="card-body p-3">
                        <?php foreach ($related_posts as $rp): ?>
                        <div class="d-flex mb-3">
                            <div class="flex-shrink-0 me-2" style="width: 70px; height: 60px; overflow: hidden; border-radius: 4px;">
                                <?php if (!empty($rp['image'])): ?>
                                    <img src="<?php echo UPLOADS_URL . 'blog/' . htmlspecialchars($rp['image']); ?>"
                                         alt="" class="w-100 h-100" style="object-fit: cover;">
                                <?php else: ?>
                                    <div class="w-100 h-100 d-flex align-items-center justify-content-center"
                                         style="background: linear-gradient(135deg, #c9a96e, #8b6914);">
                                        <i class="fas fa-gem text-white small"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="flex-grow-1">
                                <a href="<?php echo SITE_URL; ?>/blog-detail.php?slug=<?php echo urlencode($rp['slug']); ?>"
                                   class="text-dark text-decoration-none small fw-semibold d-block lh-sm mb-1">
                                    <?php echo htmlspecialchars(truncateText($rp['title'], 60)); ?>
                                </a>
                                <small class="text-muted">
                                    <?php echo date('d M Y', strtotime($rp['created_at'])); ?>
                                </small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Subscribe Card -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 text-center">
                        <i class="fas fa-envelope fa-2x mb-3" style="color: #c9a96e;"></i>
                        <h6 class="fw-bold mb-2" style="font-family: 'Playfair Display', serif;">Stay Inspired</h6>
                        <p class="text-muted small mb-3">Subscribe to receive the latest marble design tips and insights.</p>
                        <div class="input-group">
                            <input type="email" class="form-control form-control-sm" placeholder="Your email address">
                            <button class="btn btn-sm text-white" style="background: #c9a96e; border-color: #c9a96e;">
                                Subscribe
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
