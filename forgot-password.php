<?php
ob_start();
session_start();
require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/functions.php';

$errors = [];
$success = false;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $email = sanitize($_POST['email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        } else {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                $update_stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires_at = ? WHERE email = ?");
                $update_stmt->bind_param('sss', $token, $expires, $email);
                $update_stmt->execute();

                $reset_link = SITE_URL . '/reset-password.php?token=' . $token;
                $subject = 'Password Reset - ' . SITE_NAME;
                $body  = "Hello,\r\n\r\n";
                $body .= "You requested a password reset for your " . SITE_NAME . " account.\r\n\r\n";
                $body .= "Click this link to reset your password:\r\n" . $reset_link . "\r\n\r\n";
                $body .= "This link expires in 1 hour.\r\n\r\n";
                $body .= "If you did not request this, please ignore this email.\r\n\r\n";
                $body .= "Regards,\r\n" . SITE_NAME;

                $headers = 'From: ' . SITE_EMAIL . "\r\n" .
                           'Reply-To: ' . SITE_EMAIL . "\r\n" .
                           'X-Mailer: PHP/' . phpversion();

                mail($email, $subject, $body, $headers);
            }
            // Always show success (don't reveal if email exists)
            $success = true;
        }
    }
}

$page_title = 'Forgot Password';
require_once 'includes/header.php';
?>

<section class="auth-section py-5">
    <div class="container">
        <div class="auth-container d-flex justify-content-center align-items-center">
            <div class="auth-card card shadow-lg p-4 p-md-5">

                <?php if ($success): ?>
                    <div class="text-center">
                        <div class="success-icon-wrap mx-auto mb-4">
                            <i class="fas fa-check-circle" style="font-size:4rem;color:#28a745;"></i>
                        </div>
                        <h2 class="auth-title mb-3" style="font-family:'Playfair Display',serif;">Check Your Email</h2>
                        <div class="gold-divider mx-auto my-3"></div>
                        <p class="text-muted mb-4">
                            If an account exists for <strong><?php echo htmlspecialchars($email); ?></strong>,
                            a password reset link has been sent. Please check your inbox (and spam folder).
                        </p>
                        <div class="alert alert-success">
                            <i class="fas fa-envelope me-2"></i>Password reset link sent! Check your email.
                        </div>
                        <a href="<?php echo htmlspecialchars(SITE_URL . '/login.php'); ?>" class="btn btn-gold mt-3">
                            <i class="fas fa-sign-in-alt me-2"></i>Back to Login
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center mb-4">
                        <div class="key-icon-wrap mx-auto mb-3">
                            <i class="fas fa-key" style="font-size:2.5rem;color:#c9a84c;"></i>
                        </div>
                        <h2 class="auth-title" style="font-family:'Playfair Display',serif;">Forgot Password</h2>
                        <div class="gold-divider mx-auto my-3"></div>
                        <p class="text-muted">Enter your email address and we'll send you a link to reset your password.</p>
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

                    <form method="POST" action="<?php echo htmlspecialchars(SITE_URL . '/forgot-password.php'); ?>" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                        <div class="mb-4">
                            <label for="email" class="form-label fw-semibold">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email"
                                       value="<?php echo htmlspecialchars($email); ?>"
                                       placeholder="you@example.com" required autofocus>
                            </div>
                        </div>

                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-gold btn-lg">
                                <i class="fas fa-paper-plane me-2"></i>Send Reset Link
                            </button>
                        </div>

                        <p class="text-center mb-0">
                            Remembered your password?
                            <a href="<?php echo htmlspecialchars(SITE_URL . '/login.php'); ?>" class="text-gold fw-semibold">Sign In</a>
                        </p>
                    </form>
                <?php endif; ?>

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
.key-icon-wrap, .success-icon-wrap { width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #fdf3dc, #fef9ee); display: flex; align-items: center; justify-content: center; border: 2px solid #e8c96e; }
.input-group-text { background: #fdf9f0; border-color: #ddd; color: #c9a84c; }
.form-control:focus { border-color: #c9a84c; box-shadow: 0 0 0 0.2rem rgba(201,168,76,0.2); }
</style>

<?php require_once 'includes/footer.php'; ?>
