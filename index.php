<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/functions.php';

$sliders           = getAllSliders($conn);
$featured_products = getFeaturedProducts($conn, 4);
$latest_products   = getLatestProducts($conn, 8);
$categories        = getCategories($conn);

$page_title       = 'Premium Luxury Marble Products';
$meta_description = 'Shop premium marble mandirs, fireplaces, wall panels, flooring and decorative pieces. Crafted with precision since 1995.';

require_once 'includes/header.php';
?>

<!-- ═══════════════════════════════ HERO / SLIDER ═══════════════════════════════ -->
<?php if (!empty($sliders)): ?>
<div id="heroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel">
    <div class="carousel-indicators">
        <?php foreach ($sliders as $i => $slide): ?>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="<?php echo $i; ?>"
                class="<?php echo $i === 0 ? 'active' : ''; ?>"></button>
        <?php endforeach; ?>
    </div>

    <div class="carousel-inner">
        <?php foreach ($sliders as $i => $slide): ?>
        <div class="carousel-item <?php echo $i === 0 ? 'active' : ''; ?>">
            <?php if (!empty($slide['image'])): ?>
            <div class="hero-slide" style="background-image: url('<?php echo UPLOADS_URL . 'slider/' . htmlspecialchars($slide['image']); ?>');">
            <?php else: ?>
            <div class="hero-slide hero-gradient">
            <?php endif; ?>
                <div class="carousel-overlay"></div>
                <div class="hero-content text-center text-white">
                    <h1 class="hero-title"><?php echo htmlspecialchars($slide['title']); ?></h1>
                    <?php if (!empty($slide['subtitle'])): ?>
                    <p class="hero-subtitle"><?php echo htmlspecialchars($slide['subtitle']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($slide['button_text']) && !empty($slide['button_link'])): ?>
                    <a href="<?php echo htmlspecialchars($slide['button_link']); ?>" class="btn btn-gold btn-lg mt-3">
                        <?php echo htmlspecialchars($slide['button_text']); ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
    </button>
</div>

<?php else: ?>
<!-- Fallback hero -->
<div class="hero-slide hero-gradient d-flex align-items-center justify-content-center text-center text-white">
    <div>
        <h1 class="hero-title">Welcome to Luxe Marble</h1>
        <p class="hero-subtitle">Premium marble craftsmanship since 1995</p>
        <a href="<?php echo SITE_URL; ?>/shop.php" class="btn btn-gold btn-lg mt-3">
            <i class="fas fa-gem me-2"></i>Explore Collection
        </a>
    </div>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════ CATEGORIES ═══════════════════════════════ -->
<section class="section-padding bg-cream">
    <div class="container">
        <div class="section-heading text-center mb-5">
            <h2 class="section-title">Our Collections</h2>
            <div class="gold-divider mx-auto"></div>
        </div>
        <?php if (!empty($categories)): ?>
        <div class="row g-3 justify-content-center">
            <?php foreach ($categories as $cat): ?>
            <div class="col-6 col-md-4 col-lg-2">
                <a href="<?php echo SITE_URL; ?>/shop.php?category=<?php echo htmlspecialchars($cat['slug']); ?>"
                   class="category-card text-decoration-none text-center d-block p-3">
                    <div class="category-icon mb-2">
                        <i class="fas fa-gem gold-text fa-2x"></i>
                    </div>
                    <h6 class="category-name mb-0"><?php echo htmlspecialchars($cat['name']); ?></h6>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ═══════════════════════════════ FEATURED PRODUCTS ═══════════════════════════════ -->
<section class="section-padding">
    <div class="container">
        <div class="section-heading text-center mb-5">
            <h2 class="section-title">Featured Products</h2>
            <div class="gold-divider mx-auto"></div>
        </div>
        <?php if (!empty($featured_products)): ?>
        <div class="row g-4">
            <?php foreach ($featured_products as $product): ?>
            <div class="col-md-6 col-lg-3">
                <?php include __DIR__ . '/includes/product-card.php'; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-center text-muted">No featured products at the moment.</p>
        <?php endif; ?>
    </div>
</section>

<!-- ═══════════════════════════════ LATEST ARRIVALS ═══════════════════════════════ -->
<section class="section-padding bg-cream">
    <div class="container">
        <div class="section-heading text-center mb-5">
            <h2 class="section-title">Latest Arrivals</h2>
            <div class="gold-divider mx-auto"></div>
        </div>
        <?php if (!empty($latest_products)): ?>
        <div class="row g-4">
            <?php foreach ($latest_products as $product): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <?php include __DIR__ . '/includes/product-card.php'; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div class="text-center mt-5">
            <a href="<?php echo SITE_URL; ?>/shop.php" class="btn btn-outline-gold btn-lg">
                <i class="fas fa-th-large me-2"></i>View All Products
            </a>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════ WHY CHOOSE US ═══════════════════════════════ -->
<section class="section-padding why-us-section">
    <div class="container">
        <div class="section-heading text-center mb-5">
            <h2 class="section-title">Why Choose Luxe Marble?</h2>
            <div class="gold-divider mx-auto"></div>
        </div>
        <div class="row g-4 text-center">
            <div class="col-md-6 col-lg-3">
                <div class="feature-card p-4">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-award fa-3x gold-text"></i>
                    </div>
                    <h5 class="feature-title">Premium Quality</h5>
                    <p class="feature-text text-muted">Sourced from the finest quarries, every piece meets our exacting quality standards.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="feature-card p-4">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-tools fa-3x gold-text"></i>
                    </div>
                    <h5 class="feature-title">Expert Craftsmanship</h5>
                    <p class="feature-text text-muted">Master artisans with decades of experience shape every marble creation with precision.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="feature-card p-4">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-shipping-fast fa-3x gold-text"></i>
                    </div>
                    <h5 class="feature-title">Free Shipping on ₹5000+</h5>
                    <p class="feature-text text-muted">Enjoy complimentary delivery across India on all orders above ₹5,000.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="feature-card p-4">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-pencil-ruler fa-3x gold-text"></i>
                    </div>
                    <h5 class="feature-title">Custom Orders</h5>
                    <p class="feature-text text-muted">Bespoke marble creations tailored to your exact specifications and design vision.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
require_once 'includes/footer.php';
ob_end_flush();
?>
