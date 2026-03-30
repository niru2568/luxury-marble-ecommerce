<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/functions.php';

$slug = isset($_GET['slug']) ? sanitize($_GET['slug']) : '';
if (!$slug) {
    redirect(SITE_URL . '/shop.php');
}

$product = getProductBySlug($conn, $slug);
if (!$product || $product['status'] !== 'active') {
    redirect(SITE_URL . '/shop.php');
}

// Product images
$img_stmt = $conn->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC");
$img_stmt->bind_param('i', $product['id']);
$img_stmt->execute();
$images = $img_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$img_stmt->close();

$related = getRelatedProducts($conn, (int)$product['category_id'], (int)$product['id'], 4);

$page_title       = $product['meta_title']       ?: $product['name'];
$meta_description = $product['meta_description'] ?: truncateText(strip_tags($product['description'] ?? ''), 160);

require_once 'includes/header.php';

// Resolve primary image (basename for path safety)
$primary_image = '';
foreach ($images as $img) {
    if ($img['is_primary']) { $primary_image = basename($img['image_path']); break; }
}
if (!$primary_image && !empty($images)) {
    $primary_image = basename($images[0]['image_path']);
}
$display_price = ($product['sale_price'] > 0) ? (float)$product['sale_price'] : (float)$product['price'];
?>

<!-- ─── Breadcrumb ─────────────────────────────────────────────────────────── -->
<div class="breadcrumb-section bg-cream py-3">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/">Home</a></li>
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/shop.php">Shop</a></li>
                <?php if (!empty($product['category_name'])): ?>
                <li class="breadcrumb-item">
                    <a href="<?php echo SITE_URL; ?>/shop.php">
                        <?php echo htmlspecialchars($product['category_name']); ?>
                    </a>
                </li>
                <?php endif; ?>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['name']); ?></li>
            </ol>
        </nav>
    </div>
</div>

<!-- ─── Product Detail ────────────────────────────────────────────────────── -->
<div class="container my-5">
    <div class="row g-5">

        <!-- Image gallery -->
        <div class="col-md-6">
            <div class="product-gallery">
                <div class="main-image-wrap mb-3 text-center">
                    <?php if ($primary_image): ?>
                    <img src="<?php echo UPLOADS_URL . 'products/' . htmlspecialchars($primary_image); ?>"
                         id="mainProductImage"
                         class="img-fluid product-main-img rounded shadow-sm"
                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <?php else: ?>
                    <div class="product-img-placeholder-lg d-flex align-items-center justify-content-center rounded shadow-sm">
                        <span class="placeholder-initials-lg"><?php echo strtoupper(mb_substr($product['name'], 0, 2)); ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if (count($images) > 1): ?>
                <div class="thumbnail-strip d-flex gap-2 flex-wrap justify-content-center">
                    <?php foreach ($images as $img):
                        // Validate image_path contains no directory traversal
                        $safe_img_path = basename($img['image_path']);
                    ?>
                    <img src="<?php echo UPLOADS_URL . 'products/' . htmlspecialchars($safe_img_path); ?>"
                         class="thumbnail-img rounded <?php echo $img['is_primary'] ? 'active' : ''; ?>"
                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                         data-src="<?php echo UPLOADS_URL . 'products/' . htmlspecialchars($safe_img_path); ?>"
                         loading="lazy">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Product info -->
        <div class="col-md-6">
            <div class="product-info">
                <h1 class="product-detail-title mb-3"><?php echo htmlspecialchars($product['name']); ?></h1>

                <!-- Price -->
                <div class="product-detail-price mb-3">
                    <?php if ($product['sale_price'] > 0): ?>
                    <span class="original-price text-muted text-decoration-line-through fs-5 me-2">
                        <?php echo formatPrice((float)$product['price']); ?>
                    </span>
                    <span class="sale-price gold-text fw-bold fs-3">
                        <?php echo formatPrice((float)$product['sale_price']); ?>
                    </span>
                    <span class="badge bg-danger ms-2">
                        <?php echo round((1 - $product['sale_price'] / $product['price']) * 100); ?>% OFF
                    </span>
                    <?php else: ?>
                    <span class="regular-price gold-text fw-bold fs-3">
                        <?php echo formatPrice((float)$product['price']); ?>
                    </span>
                    <?php endif; ?>
                </div>

                <!-- Stock badge -->
                <?php if ((int)($product['stock'] ?? 0) > 0): ?>
                <span class="badge bg-success mb-3 fs-6">
                    <i class="fas fa-check-circle me-1"></i>In Stock
                </span>
                <?php else: ?>
                <span class="badge bg-secondary mb-3 fs-6">
                    <i class="fas fa-times-circle me-1"></i>Out of Stock
                </span>
                <?php endif; ?>

                <!-- Short description -->
                <?php if (!empty($product['description'])): ?>
                <div class="product-short-desc text-muted mb-4">
                    <?php echo nl2br(htmlspecialchars(truncateText(strip_tags($product['description']), 200))); ?>
                </div>
                <?php endif; ?>

                <!-- Quantity + Add to cart -->
                <?php if ((int)($product['stock'] ?? 0) > 0): ?>
                <div class="qty-cart-row d-flex align-items-center gap-3 mb-4 flex-wrap">
                    <div class="input-group qty-group" style="width:130px;">
                        <button type="button" class="btn btn-outline-secondary qty-btn" id="qtyMinus">
                            <i class="fas fa-minus"></i>
                        </button>
                        <input type="number" id="product-qty" class="form-control text-center"
                               value="1" min="1" max="<?php echo (int)$product['stock']; ?>">
                        <button type="button" class="btn btn-outline-secondary qty-btn" id="qtyPlus">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <button class="add-to-cart-btn btn btn-gold flex-grow-1"
                            data-product-id="<?php echo (int)$product['id']; ?>"
                            data-qty="1"
                            id="addToCartDetailBtn">
                        <i class="fas fa-cart-plus me-2"></i>Add to Cart
                    </button>
                </div>
                <?php endif; ?>

                <a href="<?php echo SITE_URL; ?>/shop.php" class="btn btn-outline-gold btn-sm mb-4">
                    <i class="fas fa-arrow-left me-1"></i>Continue Shopping
                </a>

                <!-- Meta info -->
                <div class="product-meta border-top pt-3">
                    <?php if (!empty($product['category_name'])): ?>
                    <p class="mb-1 small text-muted">
                        <strong>Category:</strong>
                        <span class="badge bg-light text-dark border ms-1">
                            <?php echo htmlspecialchars($product['category_name']); ?>
                        </span>
                    </p>
                    <?php endif; ?>
                    <?php if (!empty($product['sku'])): ?>
                    <p class="mb-0 small text-muted">
                        <strong>SKU:</strong> <?php echo htmlspecialchars($product['sku']); ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Full Description -->
    <?php if (!empty($product['description'])): ?>
    <div class="row mt-5">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-cream border-0 py-3">
                    <h5 class="mb-0 gold-text"><i class="fas fa-info-circle me-2"></i>Product Description</h5>
                </div>
                <div class="card-body product-description">
                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Related Products -->
    <?php if (!empty($related)): ?>
    <div class="related-products mt-5">
        <div class="section-heading mb-4">
            <h3 class="section-title">You May Also Like</h3>
            <div class="gold-divider"></div>
        </div>
        <div class="row g-4">
            <?php foreach ($related as $product): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <?php include __DIR__ . '/includes/product-card.php'; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div><!-- /container -->

<script>
(function () {
    // Thumbnail swap
    var thumbs   = document.querySelectorAll('.thumbnail-img');
    var mainImg  = document.getElementById('mainProductImage');
    thumbs.forEach(function (thumb) {
        thumb.style.cursor = 'pointer';
        thumb.addEventListener('click', function () {
            if (mainImg) mainImg.src = this.dataset.src;
            thumbs.forEach(function (t) { t.classList.remove('active'); });
            this.classList.add('active');
        });
    });

    // Qty buttons sync with add-to-cart data-qty
    var qtyInput = document.getElementById('product-qty');
    var addBtn   = document.getElementById('addToCartDetailBtn');
    var minus    = document.getElementById('qtyMinus');
    var plus     = document.getElementById('qtyPlus');

    function syncQty() {
        if (addBtn && qtyInput) {
            addBtn.dataset.qty = qtyInput.value;
        }
    }
    if (minus) minus.addEventListener('click', function () {
        var v = parseInt(qtyInput.value, 10) || 1;
        if (v > 1) { qtyInput.value = v - 1; syncQty(); }
    });
    if (plus) plus.addEventListener('click', function () {
        var v   = parseInt(qtyInput.value, 10) || 1;
        var max = parseInt(qtyInput.max, 10) || 9999;
        if (v < max) { qtyInput.value = v + 1; syncQty(); }
    });
    if (qtyInput) qtyInput.addEventListener('change', syncQty);
})();
</script>

<?php
require_once 'includes/footer.php';
ob_end_flush();
?>
