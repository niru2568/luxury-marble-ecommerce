<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/functions.php';

$cart_items  = getCartItems($conn);
$cart_total  = getCartTotal($conn);
$shipping    = ($cart_total >= 5000) ? 0 : 500;
$grand_total = $cart_total + $shipping;

$page_title = 'Shopping Cart';
require_once 'includes/header.php';
?>

<!-- ─── Breadcrumb ─────────────────────────────────────────────────────────── -->
<div class="breadcrumb-section bg-cream py-3">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/">Home</a></li>
                <li class="breadcrumb-item active">Cart</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container my-5">
    <h2 class="page-title mb-4">
        <i class="fas fa-shopping-cart gold-text me-2"></i>Shopping Cart
    </h2>

<?php if (empty($cart_items)): ?>
    <!-- Empty cart -->
    <div class="empty-cart text-center py-5">
        <i class="fas fa-shopping-cart fa-5x text-muted mb-4"></i>
        <h4 class="text-muted mb-3">Your cart is empty</h4>
        <p class="text-muted mb-4">Looks like you haven't added anything to your cart yet.</p>
        <a href="<?php echo SITE_URL; ?>/shop.php" class="btn btn-gold btn-lg">
            <i class="fas fa-store me-2"></i>Continue Shopping
        </a>
    </div>

<?php else: ?>
    <div class="row g-4 align-items-start">

        <!-- ══ CART ITEMS TABLE ══════════════════════════════════════════════ -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table cart-table mb-0">
                            <thead class="bg-cream">
                                <tr>
                                    <th class="ps-4">Product</th>
                                    <th class="text-center">Price</th>
                                    <th class="text-center">Quantity</th>
                                    <th class="text-center">Total</th>
                                    <th class="text-center pe-4">Remove</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart_items as $item):
                    // getCartItems() returns: cart_id, quantity, product_id,
                    // product_name, price (already COALESCED), product_image
                    $item_price    = (float)$item['price'];
                    $item_subtotal = $item_price * (int)$item['quantity'];
                ?>
                                <tr class="cart-row" data-cart-id="<?php echo (int)$item['cart_id']; ?>">
                                    <!-- Product -->
                                    <td class="ps-4 py-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <?php if (!empty($item['product_image'])): ?>
                                            <img src="<?php echo UPLOADS_URL . 'products/' . htmlspecialchars($item['product_image']); ?>"
                                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                                 class="cart-product-img rounded"
                                                 width="60" height="60"
                                                 style="object-fit:cover;">
                                            <?php else: ?>
                                            <div class="cart-product-placeholder d-flex align-items-center justify-content-center rounded"
                                                 style="width:60px;height:60px;">
                                                <span class="small fw-bold gold-text">
                                                    <?php echo strtoupper(mb_substr($item['product_name'], 0, 2)); ?>
                                                </span>
                                            </div>
                                            <?php endif; ?>
                                            <div>
                                                <span class="text-dark fw-semibold">
                                                    <?php echo htmlspecialchars($item['product_name']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Unit price -->
                                    <td class="text-center align-middle">
                                        <span class="gold-text fw-semibold"><?php echo formatPrice($item_price); ?></span>
                                    </td>

                                    <!-- Quantity controls -->
                                    <td class="text-center align-middle">
                                        <div class="d-inline-flex align-items-center qty-control">
                                            <button class="btn btn-sm btn-outline-secondary qty-decrease"
                                                    data-cart-id="<?php echo (int)$item['cart_id']; ?>"
                                                    data-product-id="<?php echo (int)$item['product_id']; ?>">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <span class="qty-display mx-2 fw-semibold" style="min-width:24px;">
                                                <?php echo (int)$item['quantity']; ?>
                                            </span>
                                            <button class="btn btn-sm btn-outline-secondary qty-increase"
                                                    data-cart-id="<?php echo (int)$item['cart_id']; ?>"
                                                    data-product-id="<?php echo (int)$item['product_id']; ?>">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </td>

                                    <!-- Row total -->
                                    <td class="text-center align-middle fw-bold cart-row-total">
                                        <?php echo formatPrice($item_subtotal); ?>
                                    </td>

                                    <!-- Remove -->
                                    <td class="text-center align-middle pe-4">
                                        <button class="btn btn-sm btn-outline-danger remove-cart-btn"
                                                data-cart-id="<?php echo (int)$item['cart_id']; ?>"
                                                title="Remove item">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <a href="<?php echo SITE_URL; ?>/shop.php" class="btn btn-outline-gold">
                    <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                </a>
            </div>
        </div>

        <!-- ══ ORDER SUMMARY ═════════════════════════════════════════════════ -->
        <div class="col-lg-4">
            <div class="cart-summary card border-0 shadow-sm p-4">
                <h5 class="summary-title mb-4">
                    <i class="fas fa-receipt gold-text me-2"></i>Order Summary
                </h5>

                <div class="summary-row d-flex justify-content-between mb-2">
                    <span class="text-muted">Subtotal</span>
                    <span class="fw-semibold" id="cartSubtotal"><?php echo formatPrice($cart_total); ?></span>
                </div>

                <div class="summary-row d-flex justify-content-between mb-3">
                    <span class="text-muted">Shipping</span>
                    <span class="fw-semibold <?php echo $shipping === 0 ? 'text-success' : ''; ?>">
                        <?php if ($shipping === 0): ?>
                        <i class="fas fa-check-circle me-1 small"></i>Free
                        <?php else: ?>
                        <?php echo formatPrice($shipping); ?>
                        <?php endif; ?>
                    </span>
                </div>

                <?php if ($shipping > 0): ?>
                <div class="shipping-note alert alert-info py-2 px-3 small mb-3">
                    <i class="fas fa-info-circle me-1"></i>
                    Add <?php echo formatPrice(5000 - $cart_total); ?> more for free shipping!
                </div>
                <?php endif; ?>

                <hr>

                <div class="summary-row d-flex justify-content-between mb-4">
                    <span class="fw-bold fs-5">Total</span>
                    <span class="fw-bold fs-5 gold-text" id="cartGrandTotal"><?php echo formatPrice($grand_total); ?></span>
                </div>

                <a href="<?php echo SITE_URL; ?>/checkout.php" class="btn btn-gold w-100 mb-2">
                    <i class="fas fa-lock me-2"></i>Proceed to Checkout
                </a>
                <a href="<?php echo SITE_URL; ?>/shop.php" class="btn btn-outline-gold w-100">
                    <i class="fas fa-store me-2"></i>Continue Shopping
                </a>
            </div>
        </div>

    </div><!-- /row -->
<?php endif; ?>
</div><!-- /container -->

<?php
require_once 'includes/footer.php';
ob_end_flush();
?>
