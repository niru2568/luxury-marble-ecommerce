<?php
ob_start();
session_start();
require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

if (isLoggedIn()) { redirect(SITE_URL . '/my-account.php'); }

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$email) $errors[] = 'Email is required';
        if (!$password) $errors[] = 'Password is required';

        if (empty($errors)) {
            $result = loginUser($conn, $email, $password);
            if ($result['success']) {
                flashMessage('success', 'Welcome back, ' . $_SESSION['user_name'] . '!');
                $redirect = isset($_GET['redirect']) && $_GET['redirect'] === 'checkout'
                    ? SITE_URL . '/checkout.php'
                    : SITE_URL . '/my-account.php';
                redirect($redirect);
            } else {
                $errors[] = $result['message'];
            }
        }
    }
}

$page_title = 'Login';
require_once 'includes/header.php';
?>

<section class="auth-section py-5">
    <div class="container">
        <div class="auth-container d-flex justify-content-center align-items-center">
            <div class="auth-card card shadow-lg p-4 p-md-5">
                <div class="text-center mb-4">
                    <h2 class="auth-title" style="font-family:'Playfair Display',serif;">Welcome Back</h2>
                    <div class="gold-divider mx-auto my-3"></div>
                    <p class="text-muted">Sign in to your luxury marble account</p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php $flash = getFlashMessage(); if ($flash): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>">
                        <?php echo htmlspecialchars($flash['message']); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo htmlspecialchars(SITE_URL . '/login.php' . (isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : '')); ?>" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?php echo htmlspecialchars($email); ?>"
                                   placeholder="you@example.com" required autofocus>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password"
                                   placeholder="Enter your password" required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword" tabindex="-1">
                                <i class="fas fa-eye" id="togglePasswordIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3 d-flex justify-content-between align-items-center">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="remember_me" name="remember_me">
                            <label class="form-check-label" for="remember_me">Remember me</label>
                        </div>
                        <a href="<?php echo htmlspecialchars(SITE_URL . '/forgot-password.php'); ?>" class="text-gold small">Forgot password?</a>
                    </div>

                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-gold btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>Sign In
                        </button>
                    </div>

                    <p class="text-center mb-0">
                        Don't have an account?
                        <a href="<?php echo htmlspecialchars(SITE_URL . '/register.php'); ?>" class="text-gold fw-semibold">Register</a>
                    </p>
                </form>
            </div>
        </div>
    </div>
</section>

<style>
.auth-section { min-height: 80vh; background: #faf8f5; }
.auth-container { min-height: 70vh; }
.auth-card { max-width: 480px; width: 100%; border-radius: 12px; border: none; }
.auth-title { color: #2c2c2c; font-size: 2rem; }
.gold-divider { width: 60px; height: 3px; background: linear-gradient(90deg, #c9a84c, #e8c96e, #c9a84c); border-radius: 2px; }
.btn-gold { background: linear-gradient(135deg, #c9a84c, #e8c96e); color: #fff; border: none; font-weight: 600; letter-spacing: 0.5px; }
.btn-gold:hover { background: linear-gradient(135deg, #b8932f, #d4b44a); color: #fff; }
.text-gold { color: #c9a84c !important; }
.text-gold:hover { color: #a8872a !important; }
.input-group-text { background: #fdf9f0; border-color: #ddd; color: #c9a84c; }
.form-control:focus { border-color: #c9a84c; box-shadow: 0 0 0 0.2rem rgba(201,168,76,0.2); }
</style>

<script>
document.getElementById('togglePassword').addEventListener('click', function () {
    const pwInput = document.getElementById('password');
    const icon = document.getElementById('togglePasswordIcon');
    if (pwInput.type === 'password') {
        pwInput.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        pwInput.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
