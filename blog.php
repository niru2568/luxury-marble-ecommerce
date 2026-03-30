<?php
ob_start();
session_start();
require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/functions.php';

$page     = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 9;
$offset   = ($page - 1) * $per_page;

// Count total posts
$count_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM blog WHERE status = 'active'");
$count_stmt->execute();
$total       = (int)$count_stmt->get_result()->fetch_assoc()['total'];
$count_stmt->close();
$total_pages = (int)ceil($total / $per_page);

// Get blog posts
$stmt = $conn->prepare("SELECT * FROM blog WHERE status = 'active' ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->bind_param('ii', $per_page, $offset);
$stmt->execute();
$posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Sidebar: recent posts
$recent_stmt = $conn->prepare("SELECT id, title, slug, image, created_at FROM blog WHERE status = 'active' ORDER BY created_at DESC LIMIT 5");
$recent_stmt->execute();
$recent_posts = $recent_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$recent_stmt->close();

// Sidebar: categories with counts
$cat_stmt = $conn->prepare("SELECT category, COUNT(*) AS cnt FROM blog WHERE status = 'active' GROUP BY category ORDER BY cnt DESC");
$cat_stmt->execute();
$blog_categories = $cat_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$cat_stmt->close();

$page_title       = 'Blog - Marble Insights';
$meta_description = 'Read our latest articles about marble care, design tips, and luxury interiors.';
require_once 'includes/header.php';
?>

<!-- Hero Section -->
<section class="page-hero" style="background: #f8f5f0; padding: 80px 0;">
    <div class="container text-center">
        <h1 class="display-4 fw-bold" style="font-family: 'Playfair Display', serif;">Marble Insights</h1>
        <p class="lead text-muted mt-3">Expert tips on marble care, design inspiration, and luxury interiors</p>
    </div>
</section>

<!-- Breadcrumb -->
<div class="container mt-3 mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Blog</li>
        </ol>
    </nav>
</div>

<!-- Main Content -->
<section class="py-5">
    <div class="container">
        <div class="row">

            <!-- Blog Posts Grid -->
            <div class="col-lg-9">
                <?php if (empty($posts)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-newspaper fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No blog posts found.</p>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($posts as $post): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="blog-card card h-100 border-0 shadow-sm">
                                <!-- Image -->
                                <div class="blog-card-img-wrap" style="height: 200px; overflow: hidden;">
                                    <?php if (!empty($post['image'])): ?>
                                        <img src="<?php echo UPLOADS_URL . 'blog/' . htmlspecialchars($post['image']); ?>"
                                             alt="<?php echo htmlspecialchars($post['title']); ?>"
                                             class="w-100 h-100" style="object-fit: cover;">
                                    <?php else: ?>
                                        <div class="w-100 h-100 d-flex align-items-center justify-content-center"
                                             style="background: linear-gradient(135deg, #c9a96e 0%, #8b6914 100%);">
                                            <i class="fas fa-gem fa-3x text-white opacity-50"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="card-body d-flex flex-column p-3">
                                    <!-- Category Badge -->
                                    <?php if (!empty($post['category'])): ?>
                                        <span class="blog-category-badge badge mb-2"
                                              style="background: #c9a96e; color: #fff; font-size: 0.75rem; width: fit-content;">
                                            <?php echo htmlspecialchars($post['category']); ?>
                                        </span>
                                    <?php endif; ?>

                                    <!-- Title -->
                                    <h5 class="card-title" style="font-family: 'Playfair Display', serif; font-size: 1rem;">
                                        <a href="<?php echo SITE_URL; ?>/blog-detail.php?slug=<?php echo urlencode($post['slug']); ?>"
                                           class="text-dark text-decoration-none">
                                            <?php echo htmlspecialchars($post['title']); ?>
                                        </a>
                                    </h5>

                                    <!-- Excerpt -->
                                    <p class="card-text text-muted small flex-grow-1">
                                        <?php echo htmlspecialchars(truncateText($post['excerpt'] ?? '', 120)); ?>
                                    </p>

                                    <!-- Date + Read More -->
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar-alt me-1"></i>
                                            <?php echo date('d M Y', strtotime($post['created_at'])); ?>
                                        </small>
                                        <a href="<?php echo SITE_URL; ?>/blog-detail.php?slug=<?php echo urlencode($post['slug']); ?>"
                                           class="btn btn-outline-gold btn-sm"
                                           style="border-color: #c9a96e; color: #c9a96e; font-size: 0.78rem;">
                                            Read More
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <nav class="mt-5" aria-label="Blog pagination">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>">&laquo; Prev</a>
                            </li>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next &raquo;</a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-3 mt-4 mt-lg-0">

                <!-- Recent Posts -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header" style="background: #c9a96e; color: #fff;">
                        <h6 class="mb-0 fw-bold">Recent Posts</h6>
                    </div>
                    <div class="card-body p-3">
                        <?php if (empty($recent_posts)): ?>
                            <p class="text-muted small mb-0">No posts yet.</p>
                        <?php else: ?>
                            <?php foreach ($recent_posts as $rp): ?>
                            <div class="d-flex mb-3">
                                <div class="flex-shrink-0 me-2" style="width: 55px; height: 55px; overflow: hidden; border-radius: 4px;">
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
                                       class="text-dark text-decoration-none small fw-semibold d-block lh-sm">
                                        <?php echo htmlspecialchars(truncateText($rp['title'], 55)); ?>
                                    </a>
                                    <small class="text-muted"><?php echo date('d M Y', strtotime($rp['created_at'])); ?></small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Categories -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header" style="background: #c9a96e; color: #fff;">
                        <h6 class="mb-0 fw-bold">Categories</h6>
                    </div>
                    <div class="card-body p-3">
                        <?php if (empty($blog_categories)): ?>
                            <p class="text-muted small mb-0">No categories yet.</p>
                        <?php else: ?>
                            <ul class="list-unstyled mb-0">
                                <?php foreach ($blog_categories as $bc): ?>
                                <li class="d-flex justify-content-between align-items-center py-1 border-bottom">
                                    <a href="<?php echo SITE_URL; ?>/blog.php?category=<?php echo urlencode($bc['category']); ?>"
                                       class="text-dark text-decoration-none small">
                                        <?php echo htmlspecialchars($bc['category']); ?>
                                    </a>
                                    <span class="badge rounded-pill" style="background: #f8f5f0; color: #c9a96e; border: 1px solid #c9a96e;">
                                        <?php echo (int)$bc['cnt']; ?>
                                    </span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
