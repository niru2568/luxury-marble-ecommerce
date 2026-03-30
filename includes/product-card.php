<?php
/**
 * Reusable product card partial.
 * Expects $product array in scope. Handles two image column conventions:
 *   - 'image_path'    (shop.php LEFT JOIN query)
 *   - 'primary_image' (getFeaturedProducts / getLatestProducts / getRelatedProducts)
 */
$_pc_name       = htmlspecialchars($product['name'] ?? '');
$_pc_slug       = $product['slug'] ?? '';
$_pc_price      = (float)($product['price'] ?? 0);
$_pc_sale_price = isset($product['sale_price']) && $product['sale_price'] > 0
                    ? (float)$product['sale_price'] : 0;
$_pc_initials   = strtoupper(mb_substr($product['name'] ?? 'M', 0, 2));
// Support both column name conventions used across queries
$_pc_image      = $product['image_path'] ?? ($product['primary_image'] ?? '');
?>
<div class="product-card card h-100 border-0 shadow-sm">
    <a href="<?php echo SITE_URL; ?>/product.php?slug=<?php echo htmlspecialchars($_pc_slug); ?>" class="text-decoration-none">
        <?php if (!empty($_pc_image)): ?>
        <img src="<?php echo UPLOADS_URL . 'products/' . htmlspecialchars($_pc_image); ?>"
             class="card-img-top product-img"
             alt="<?php echo $_pc_name; ?>"
             loading="lazy">
        <?php else: ?>
        <div class="product-img-placeholder d-flex align-items-center justify-content-center">
            <span class="placeholder-initials"><?php echo $_pc_initials; ?></span>
        </div>
        <?php endif; ?>
    </a>
    <div class="card-body d-flex flex-column p-3">
        <h5 class="card-title product-name mb-2">
            <a href="<?php echo SITE_URL; ?>/product.php?slug=<?php echo htmlspecialchars($_pc_slug); ?>"
               class="text-decoration-none text-dark">
                <?php echo $_pc_name; ?>
            </a>
        </h5>
        <div class="product-price mb-3">
            <?php if ($_pc_sale_price > 0): ?>
            <span class="original-price text-muted text-decoration-line-through me-2">
                <?php echo formatPrice($_pc_price); ?>
            </span>
            <span class="sale-price gold-text fw-bold">
                <?php echo formatPrice($_pc_sale_price); ?>
            </span>
            <?php else: ?>
            <span class="regular-price gold-text fw-bold">
                <?php echo formatPrice($_pc_price); ?>
            </span>
            <?php endif; ?>
        </div>
        <div class="mt-auto d-grid gap-2">
            <button class="add-to-cart-btn btn btn-gold w-100"
                    data-product-id="<?php echo (int)$product['id']; ?>"
                    data-qty="1">
                <i class="fas fa-cart-plus me-2"></i>Add to Cart
            </button>
            <a href="<?php echo SITE_URL; ?>/product.php?slug=<?php echo htmlspecialchars($_pc_slug); ?>"
               class="btn btn-outline-gold btn-sm w-100">
                <i class="fas fa-eye me-1"></i>View Details
            </a>
        </div>
    </div>
</div>
