<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/functions.php';

requireLogin();

$cart_items = getCartItems($conn);
if (empty($cart_items)) {
    redirect(SITE_URL . '/cart.php');
}

$cart_total  = getCartTotal($conn);
$shipping    = ($cart_total >= 5000) ? 0 : 500;
$grand_total = $cart_total + $shipping;

// Pre-fill user info
$user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->bind_param('i', $_SESSION['user_id']);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        $name    = sanitize($_POST['name']    ?? '');
        $email   = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $phone   = sanitize($_POST['phone']   ?? '');
        $address = sanitize($_POST['address'] ?? '');
        $city    = sanitize($_POST['city']    ?? '');
        $state   = sanitize($_POST['state']   ?? '');
        $pincode = sanitize($_POST['pincode'] ?? '');

        $allowed_methods  = ['razorpay', 'cod', 'bank_transfer'];
        $payment_method   = in_array($_POST['payment_method'] ?? '', $allowed_methods, true)
                            ? $_POST['payment_method'] : 'cod';

        if (!$name)                        $errors[] = 'Name is required.';
        if (!$email)                       $errors[] = 'A valid email address is required.';
        if (!$phone || strlen($phone) < 10) $errors[] = 'A valid phone number (min 10 digits) is required.';
        if (!$address)                     $errors[] = 'Address is required.';
        if (!$city)                        $errors[] = 'City is required.';
        if (!$state)                       $errors[] = 'State is required.';
        if (!$pincode)                     $errors[] = 'Pincode is required.';

        if (empty($errors)) {
            $order_number = generateOrderNumber();
            $session_id   = session_id();
            $user_id      = (int)$_SESSION['user_id'];

            $order_stmt = $conn->prepare(
                "INSERT INTO orders
                    (order_number, user_id, session_id, name, email, phone,
                     address, city, state, pincode,
                     subtotal, shipping, total, payment_method)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            // s i s  s s s  s s s s  d d d s
            $order_stmt->bind_param(
                'sissssssssddds',
                $order_number,
                $user_id,
                $session_id,
                $name,
                $email,
                $phone,
                $address,
                $city,
                $state,
                $pincode,
                $cart_total,
                $shipping,
                $grand_total,
                $payment_method
            );
            $order_stmt->execute();
            $order_id = (int)$conn->insert_id;
            $order_stmt->close();

            // Insert order items
            foreach ($cart_items as $item) {
                // getCartItems() returns: cart_id, product_id, product_name,
                // price (already COALESCED), product_image, quantity
                $item_price    = (float)$item['price'];
                $item_subtotal = $item_price * (int)$item['quantity'];
                $img           = $item['product_image'] ?? '';
                $product_name  = $item['product_name'];
                $quantity      = (int)$item['quantity'];
                $product_id    = (int)$item['product_id'];

                $item_stmt = $conn->prepare(
                    "INSERT INTO order_items
                        (order_id, product_id, product_name, product_image, price, quantity, subtotal)
                     VALUES (?, ?, ?, ?, ?, ?, ?)"
                );
                $item_stmt->bind_param('iissdid', $order_id, $product_id, $product_name, $img, $item_price, $quantity, $item_subtotal);
                $item_stmt->execute();
                $item_stmt->close();
            }

            // Clear cart for this user
            $clear_stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $clear_stmt->bind_param('i', $user_id);
            $clear_stmt->execute();
            $clear_stmt->close();

            redirect(SITE_URL . '/order-confirmation.php?order_number=' . urlencode($order_number));
        }
    }
}

// Keep form values for re-population on validation failure
$form = [
    'name'    => sanitize($_POST['name']    ?? ($user['name']    ?? '')),
    'email'   => htmlspecialchars($_POST['email']   ?? ($user['email']   ?? ''), ENT_QUOTES, 'UTF-8'),
    'phone'   => sanitize($_POST['phone']   ?? ($user['phone']   ?? '')),
    'address' => sanitize($_POST['address'] ?? ($user['address'] ?? '')),
    'city'    => sanitize($_POST['city']    ?? ($user['city']    ?? '')),
    'state'   => sanitize($_POST['state']   ?? ($user['state']   ?? '')),
    'pincode' => sanitize($_POST['pincode'] ?? ($user['pincode'] ?? '')),
    'payment' => htmlspecialchars($_POST['payment_method'] ?? 'cod', ENT_QUOTES, 'UTF-8'),
];

$page_title = 'Checkout';
require_once 'includes/header.php';
?>

<!-- ─── Breadcrumb ─────────────────────────────────────────────────────────── -->
<div class="breadcrumb-section bg-cream py-3">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/">Home</a></li>
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/cart.php">Cart</a></li>
                <li class="breadcrumb-item active">Checkout</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container my-5">
    <h2 class="page-title mb-4">
        <i class="fas fa-lock gold-text me-2"></i>Secure Checkout
    </h2>

    <!-- Validation errors -->
    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger mb-4">
        <i class="fas fa-exclamation-circle me-2"></i>
        <strong>Please fix the following errors:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($errors as $err): ?>
            <li><?php echo htmlspecialchars($err); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <form method="post" action="" id="checkoutForm" novalidate>
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

        <div class="row g-4 align-items-start">

            <!-- ══ CHECKOUT FORM ═════════════════════════════════════════════ -->
            <div class="col-lg-8">

                <!-- Shipping Information -->
                <div class="checkout-section card border-0 shadow-sm p-4 mb-4">
                    <h5 class="checkout-section-title mb-4">
                        <i class="fas fa-map-marker-alt gold-text me-2"></i>Shipping Information
                    </h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name"
                                   class="form-control <?php echo isset($errors) && !sanitize($_POST['name'] ?? '') && !empty($errors) ? 'is-invalid' : ''; ?>"
                                   value="<?php echo $form['name']; ?>"
                                   placeholder="Your full name"
                                   required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" id="email" name="email"
                                   class="form-control"
                                   value="<?php echo $form['email']; ?>"
                                   placeholder="your@email.com"
                                   required>
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label fw-semibold">Phone Number <span class="text-danger">*</span></label>
                            <input type="tel" id="phone" name="phone"
                                   class="form-control"
                                   value="<?php echo $form['phone']; ?>"
                                   placeholder="+91 98765 43210"
                                   required>
                        </div>
                        <div class="col-12">
                            <label for="address" class="form-label fw-semibold">Street Address <span class="text-danger">*</span></label>
                            <textarea id="address" name="address"
                                      class="form-control"
                                      rows="2"
                                      placeholder="House / Flat No., Street, Locality"
                                      required><?php echo $form['address']; ?></textarea>
                        </div>
                        <div class="col-md-4">
                            <label for="city" class="form-label fw-semibold">City <span class="text-danger">*</span></label>
                            <input type="text" id="city" name="city"
                                   class="form-control"
                                   value="<?php echo $form['city']; ?>"
                                   placeholder="City"
                                   required>
                        </div>
                        <div class="col-md-4">
                            <label for="state" class="form-label fw-semibold">State <span class="text-danger">*</span></label>
                            <select id="state" name="state" class="form-select" required>
                                <option value="">Select State</option>
                                <?php
                                $states = ['Andhra Pradesh','Arunachal Pradesh','Assam','Bihar','Chhattisgarh','Goa','Gujarat','Haryana','Himachal Pradesh','Jharkhand','Karnataka','Kerala','Madhya Pradesh','Maharashtra','Manipur','Meghalaya','Mizoram','Nagaland','Odisha','Punjab','Rajasthan','Sikkim','Tamil Nadu','Telangana','Tripura','Uttar Pradesh','Uttarakhand','West Bengal','Delhi','Jammu & Kashmir','Ladakh'];
                                foreach ($states as $s):
                                ?>
                                <option value="<?php echo htmlspecialchars($s); ?>" <?php echo $form['state'] === htmlspecialchars($s) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($s); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="pincode" class="form-label fw-semibold">Pincode <span class="text-danger">*</span></label>
                            <input type="text" id="pincode" name="pincode"
                                   class="form-control"
                                   value="<?php echo $form['pincode']; ?>"
                                   placeholder="6-digit PIN"
                                   maxlength="6"
                                   required>
                        </div>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="checkout-section card border-0 shadow-sm p-4">
                    <h5 class="checkout-section-title mb-4">
                        <i class="fas fa-credit-card gold-text me-2"></i>Payment Method
                    </h5>

                    <div class="payment-options d-flex flex-column gap-3">

                        <!-- Razorpay -->
                        <label class="payment-option card border p-3 mb-0 cursor-pointer <?php echo $form['payment'] === 'razorpay' ? 'selected' : ''; ?>"
                               for="pay_razorpay">
                            <div class="d-flex align-items-center gap-3">
                                <input type="radio" name="payment_method" id="pay_razorpay"
                                       value="razorpay"
                                       class="payment-radio"
                                       <?php echo $form['payment'] === 'razorpay' ? 'checked' : ''; ?>>
                                <i class="fas fa-bolt gold-text fa-lg"></i>
                                <div>
                                    <strong>Razorpay</strong>
                                    <div class="small text-muted">Pay securely via UPI, Card, Net Banking</div>
                                </div>
                            </div>
                            <div id="razorpay-section" class="payment-detail mt-3 ms-4 ps-2 <?php echo $form['payment'] === 'razorpay' ? '' : 'd-none'; ?>">
                                <div class="alert alert-info py-2 px-3 small mb-0">
                                    <i class="fas fa-shield-alt me-1"></i>
                                    Your payment is secured by Razorpay's 256-bit SSL encryption.
                                </div>
                            </div>
                        </label>

                        <!-- COD -->
                        <label class="payment-option card border p-3 mb-0 cursor-pointer <?php echo $form['payment'] === 'cod' ? 'selected' : ''; ?>"
                               for="pay_cod">
                            <div class="d-flex align-items-center gap-3">
                                <input type="radio" name="payment_method" id="pay_cod"
                                       value="cod"
                                       class="payment-radio"
                                       <?php echo $form['payment'] === 'cod' ? 'checked' : ''; ?>>
                                <i class="fas fa-hand-holding-usd gold-text fa-lg"></i>
                                <div>
                                    <strong>Cash on Delivery</strong>
                                    <div class="small text-muted">Pay with cash when your order arrives</div>
                                </div>
                            </div>
                            <div id="cod-section" class="payment-detail mt-3 ms-4 ps-2 <?php echo $form['payment'] === 'cod' ? '' : 'd-none'; ?>">
                                <div class="alert alert-success py-2 px-3 small mb-0">
                                    <i class="fas fa-check-circle me-1"></i>
                                    Pay at delivery. No prepayment required.
                                </div>
                            </div>
                        </label>

                        <!-- Bank Transfer -->
                        <label class="payment-option card border p-3 mb-0 cursor-pointer <?php echo $form['payment'] === 'bank_transfer' ? 'selected' : ''; ?>"
                               for="pay_bank">
                            <div class="d-flex align-items-center gap-3">
                                <input type="radio" name="payment_method" id="pay_bank"
                                       value="bank_transfer"
                                       class="payment-radio"
                                       <?php echo $form['payment'] === 'bank_transfer' ? 'checked' : ''; ?>>
                                <i class="fas fa-university gold-text fa-lg"></i>
                                <div>
                                    <strong>Bank Transfer</strong>
                                    <div class="small text-muted">Direct bank transfer (NEFT / RTGS / IMPS)</div>
                                </div>
                            </div>
                            <div id="bank-section" class="payment-detail mt-3 ms-4 ps-2 <?php echo $form['payment'] === 'bank_transfer' ? '' : 'd-none'; ?>">
                                <div class="alert alert-warning py-2 px-3 small mb-0">
                                    <i class="fas fa-info-circle me-1"></i>
                                    <strong>Bank Details:</strong><br>
                                    Account Name: Luxe Marble Pvt. Ltd.<br>
                                    Account No: 1234 5678 9012<br>
                                    IFSC: HDFC0001234<br>
                                    Bank: HDFC Bank, Jaipur
                                </div>
                            </div>
                        </label>

                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-gold btn-lg w-100" id="placeOrderBtn">
                            <i class="fas fa-check-circle me-2"></i>Place Order
                        </button>
                        <p class="text-center text-muted small mt-2 mb-0">
                            <i class="fas fa-lock me-1"></i>Your information is secure and encrypted.
                        </p>
                    </div>
                </div>

            </div><!-- /form column -->

            <!-- ══ ORDER SUMMARY ═════════════════════════════════════════════ -->
            <div class="col-lg-4">
                <div class="checkout-section card border-0 shadow-sm p-4 sticky-top" style="top:90px;">
                    <h5 class="checkout-section-title mb-4">
                        <i class="fas fa-receipt gold-text me-2"></i>Order Summary
                    </h5>

                    <div class="order-items mb-3">
                        <?php foreach ($cart_items as $item):
                            $item_price    = (float)$item['price'];
                            $item_subtotal = $item_price * (int)$item['quantity'];
                        ?>
                        <div class="order-item d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
                            <?php if (!empty($item['product_image'])): ?>
                            <img src="<?php echo UPLOADS_URL . 'products/' . htmlspecialchars($item['product_image']); ?>"
                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                 class="rounded"
                                 width="48" height="48"
                                 style="object-fit:cover;flex-shrink:0;">
                            <?php else: ?>
                            <div class="cart-product-placeholder d-flex align-items-center justify-content-center rounded"
                                 style="width:48px;height:48px;flex-shrink:0;">
                                <span class="small fw-bold gold-text">
                                    <?php echo strtoupper(mb_substr($item['product_name'], 0, 2)); ?>
                                </span>
                            </div>
                            <?php endif; ?>
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="small fw-semibold text-truncate"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                <div class="small text-muted">
                                    <?php echo (int)$item['quantity']; ?> &times; <?php echo formatPrice($item_price); ?>
                                </div>
                            </div>
                            <div class="small fw-bold gold-text"><?php echo formatPrice($item_subtotal); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="summary-row d-flex justify-content-between mb-2">
                        <span class="text-muted">Subtotal</span>
                        <span class="fw-semibold"><?php echo formatPrice($cart_total); ?></span>
                    </div>
                    <div class="summary-row d-flex justify-content-between mb-3">
                        <span class="text-muted">Shipping</span>
                        <span class="fw-semibold <?php echo $shipping === 0 ? 'text-success' : ''; ?>">
                            <?php echo $shipping === 0 ? '<i class="fas fa-check-circle me-1 small"></i>Free' : formatPrice($shipping); ?>
                        </span>
                    </div>
                    <hr>
                    <div class="summary-row d-flex justify-content-between">
                        <span class="fw-bold fs-5">Grand Total</span>
                        <span class="fw-bold fs-5 gold-text"><?php echo formatPrice($grand_total); ?></span>
                    </div>
                </div>
            </div><!-- /summary column -->

        </div><!-- /row -->
    </form>
</div><!-- /container -->

<script>
(function () {
    var radios   = document.querySelectorAll('.payment-radio');
    var sections = { razorpay: 'razorpay-section', cod: 'cod-section', bank_transfer: 'bank-section' };
    var labels   = document.querySelectorAll('.payment-option');

    function updatePayment(val) {
        labels.forEach(function (lbl) { lbl.classList.remove('selected'); });
        Object.keys(sections).forEach(function (key) {
            var sec = document.getElementById(sections[key]);
            if (sec) sec.classList.add('d-none');
        });
        if (sections[val]) {
            var active = document.getElementById(sections[val]);
            if (active) active.classList.remove('d-none');
        }
        // highlight selected label
        radios.forEach(function (r) {
            if (r.value === val) {
                r.closest('.payment-option').classList.add('selected');
            }
        });
    }

    radios.forEach(function (radio) {
        radio.addEventListener('change', function () { updatePayment(this.value); });
    });

    // Init
    var checked = document.querySelector('.payment-radio:checked');
    if (checked) updatePayment(checked.value);
})();
</script>

<?php
require_once 'includes/footer.php';
ob_end_flush();
?>
