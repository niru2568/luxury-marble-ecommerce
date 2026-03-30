<?php
ob_start();
session_start();
require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

requireLogin();

$user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->bind_param('i', $_SESSION['user_id']);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

$orders_stmt = $conn->prepare(
    "SELECT o.*, COUNT(oi.id) as item_count
     FROM orders o
     LEFT JOIN order_items oi ON o.id = oi.order_id
     WHERE o.user_id = ?
     GROUP BY o.id
     ORDER BY o.created_at DESC
     LIMIT 5"
);
$orders_stmt->bind_param('i', $_SESSION['user_id']);
$orders_stmt->execute();
$recent_orders = $orders_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$errors = [];
$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $action = sanitize($_POST['action'] ?? '');

        if ($action === 'update_profile') {
            $name    = sanitize($_POST['name'] ?? '');
            $phone   = sanitize($_POST['phone'] ?? '');
            $address = sanitize($_POST['address'] ?? '');
            $city    = sanitize($_POST['city'] ?? '');
            $state   = sanitize($_POST['state'] ?? '');
            $pincode = sanitize($_POST['pincode'] ?? '');

            if (!$name) $errors[] = 'Name is required';

            if (empty($errors)) {
                $upd_stmt = $conn->prepare(
                    "UPDATE users SET name=?, phone=?, address=?, city=?, state=?, pincode=? WHERE id=?"
                );
                $upd_stmt->bind_param('ssssssi', $name, $phone, $address, $city, $state, $pincode, $_SESSION['user_id']);
                $upd_stmt->execute();
                $_SESSION['user_name'] = $name;
                $user['name']    = $name;
                $user['phone']   = $phone;
                $user['address'] = $address;
                $user['city']    = $city;
                $user['state']   = $state;
                $user['pincode'] = $pincode;
                $success_msg = 'Profile updated successfully!';
            }

        } elseif ($action === 'change_password') {
            $current  = $_POST['current_password'] ?? '';
            $new_pass = $_POST['new_password'] ?? '';
            $confirm  = $_POST['confirm_password'] ?? '';

            if (!password_verify($current, $user['password'])) {
                $errors[] = 'Current password is incorrect';
            } elseif (strlen($new_pass) < 8) {
                $errors[] = 'New password must be at least 8 characters';
            } elseif ($new_pass !== $confirm) {
                $errors[] = 'Passwords do not match';
            } else {
                $new_hash = password_hash($new_pass, PASSWORD_BCRYPT);
                $pw_stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
                $pw_stmt->bind_param('si', $new_hash, $_SESSION['user_id']);
                $pw_stmt->execute();
                $success_msg = 'Password changed successfully!';
            }
        }
    }
}

// Determine active tab after POST
$active_tab = 'profile';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted_action = sanitize($_POST['action'] ?? '');
    if ($posted_action === 'change_password') $active_tab = 'password';
}

$page_title = 'My Account';
require_once 'includes/header.php';
?>

<section class="py-4" style="background:#faf8f5;min-height:80vh;">
    <div class="container">

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo htmlspecialchars(SITE_URL); ?>" class="text-gold">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">My Account</li>
            </ol>
        </nav>

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 style="font-family:'Playfair Display',serif;color:#2c2c2c;">
                    Welcome, <?php echo htmlspecialchars($user['name']); ?>
                </h1>
                <p class="text-muted mb-0"><?php echo htmlspecialchars($user['email']); ?></p>
            </div>
            <a href="<?php echo htmlspecialchars(SITE_URL . '/logout.php'); ?>" class="btn btn-outline-danger">
                <i class="fas fa-sign-out-alt me-1"></i>Logout
            </a>
        </div>

        <?php if ($success_msg): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_msg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <ul class="mb-0 ps-3">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" id="accountTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $active_tab === 'profile' ? 'active' : ''; ?>"
                        id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile"
                        type="button" role="tab">
                    <i class="fas fa-user me-1"></i>Profile
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $active_tab === 'password' ? 'active' : ''; ?>"
                        id="password-tab" data-bs-toggle="tab" data-bs-target="#password-change"
                        type="button" role="tab">
                    <i class="fas fa-lock me-1"></i>Change Password
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link"
                        id="orders-tab" data-bs-toggle="tab" data-bs-target="#recent-orders"
                        type="button" role="tab">
                    <i class="fas fa-shopping-bag me-1"></i>Recent Orders
                </button>
            </li>
        </ul>

        <div class="tab-content" id="accountTabsContent">

            <!-- Profile Tab -->
            <div class="tab-pane fade <?php echo $active_tab === 'profile' ? 'show active' : ''; ?>"
                 id="profile" role="tabpanel">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-header" style="background:linear-gradient(135deg,#2c2c2c,#444);color:#e8c96e;">
                        <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Profile Information</h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" action="<?php echo htmlspecialchars(SITE_URL . '/my-account.php'); ?>" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="update_profile">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name"
                                           value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="phone" class="form-label fw-semibold">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone"
                                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                </div>
                                <div class="col-12">
                                    <label for="address" class="form-label fw-semibold">Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                                </div>
                                <div class="col-md-4">
                                    <label for="city" class="form-label fw-semibold">City</label>
                                    <input type="text" class="form-control" id="city" name="city"
                                           value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="state" class="form-label fw-semibold">State</label>
                                    <input type="text" class="form-control" id="state" name="state"
                                           value="<?php echo htmlspecialchars($user['state'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="pincode" class="form-label fw-semibold">PIN Code</label>
                                    <input type="text" class="form-control" id="pincode" name="pincode"
                                           value="<?php echo htmlspecialchars($user['pincode'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-gold px-4">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Change Password Tab -->
            <div class="tab-pane fade <?php echo $active_tab === 'password' ? 'show active' : ''; ?>"
                 id="password-change" role="tabpanel">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-header" style="background:linear-gradient(135deg,#2c2c2c,#444);color:#e8c96e;">
                        <h5 class="mb-0"><i class="fas fa-key me-2"></i>Change Password</h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" action="<?php echo htmlspecialchars(SITE_URL . '/my-account.php'); ?>" novalidate style="max-width:480px;">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="change_password">

                            <div class="mb-3">
                                <label for="current_password" class="form-label fw-semibold">Current Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="current_password"
                                           name="current_password" placeholder="Enter current password" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="new_password" class="form-label fw-semibold">New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="new_password"
                                           name="new_password" placeholder="Minimum 8 characters" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="confirm_password_acc" class="form-label fw-semibold">Confirm New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="confirm_password_acc"
                                           name="confirm_password" placeholder="Re-enter new password" required>
                                </div>
                                <small id="acc-pw-match" class="mt-1 d-block" style="display:none;"></small>
                            </div>

                            <button type="submit" class="btn btn-gold px-4">
                                <i class="fas fa-save me-2"></i>Change Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Recent Orders Tab -->
            <div class="tab-pane fade" id="recent-orders" role="tabpanel">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-header d-flex justify-content-between align-items-center"
                         style="background:linear-gradient(135deg,#2c2c2c,#444);color:#e8c96e;">
                        <h5 class="mb-0"><i class="fas fa-shopping-bag me-2"></i>Recent Orders</h5>
                        <a href="<?php echo htmlspecialchars(SITE_URL . '/orders.php'); ?>" class="btn btn-sm btn-outline-light">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recent_orders)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-shopping-bag" style="font-size:3rem;color:#ddd;"></i>
                                <p class="text-muted mt-3 mb-3">No orders yet</p>
                                <a href="<?php echo htmlspecialchars(SITE_URL . '/shop.php'); ?>" class="btn btn-gold">
                                    <i class="fas fa-store me-2"></i>Shop Now
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 align-middle">
                                    <thead style="background:#f8f4ee;">
                                        <tr>
                                            <th class="ps-3">Order #</th>
                                            <th>Date</th>
                                            <th>Items</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th class="pe-3">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_orders as $order): ?>
                                            <tr>
                                                <td class="ps-3 fw-semibold"><?php echo htmlspecialchars($order['order_number']); ?></td>
                                                <td><?php echo date('d M Y', strtotime($order['created_at'])); ?></td>
                                                <td><?php echo (int)$order['item_count']; ?></td>
                                                <td class="fw-semibold"><?php echo formatPrice($order['total_amount']); ?></td>
                                                <td>
                                                    <?php
                                                    $status_map = [
                                                        'pending'    => 'warning',
                                                        'processing' => 'primary',
                                                        'shipped'    => 'info',
                                                        'delivered'  => 'success',
                                                        'cancelled'  => 'danger',
                                                    ];
                                                    $s = strtolower($order['status'] ?? 'pending');
                                                    $badge = $status_map[$s] ?? 'secondary';
                                                    ?>
                                                    <span class="badge bg-<?php echo $badge; ?>">
                                                        <?php echo htmlspecialchars(ucfirst($s)); ?>
                                                    </span>
                                                </td>
                                                <td class="pe-3">
                                                    <a href="<?php echo htmlspecialchars(SITE_URL . '/order-detail.php?id=' . (int)$order['id']); ?>"
                                                       class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-eye me-1"></i>View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<style>
.btn-gold { background: linear-gradient(135deg, #c9a84c, #e8c96e); color: #fff; border: none; font-weight: 600; }
.btn-gold:hover { background: linear-gradient(135deg, #b8932f, #d4b44a); color: #fff; }
.text-gold { color: #c9a84c !important; }
.nav-tabs .nav-link.active { border-bottom-color: #c9a84c; color: #c9a84c; font-weight: 600; }
.nav-tabs .nav-link { color: #555; }
.input-group-text { background: #fdf9f0; border-color: #ddd; color: #c9a84c; }
.form-control:focus { border-color: #c9a84c; box-shadow: 0 0 0 0.2rem rgba(201,168,76,0.2); }
</style>

<script>
document.getElementById('new_password').addEventListener('input', checkAccPwMatch);
document.getElementById('confirm_password_acc').addEventListener('input', checkAccPwMatch);

function checkAccPwMatch() {
    const pw = document.getElementById('new_password').value;
    const cpw = document.getElementById('confirm_password_acc').value;
    const el = document.getElementById('acc-pw-match');
    if (!cpw) { el.style.display = 'none'; return; }
    el.style.display = 'block';
    if (pw === cpw) {
        el.textContent = '✓ Passwords match';
        el.style.color = '#28a745';
    } else {
        el.textContent = '✗ Passwords do not match';
        el.style.color = '#dc3545';
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
