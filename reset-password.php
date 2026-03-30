<?php
ob_start();
session_start();
require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$token = sanitize($_GET['token'] ?? '');
if (!$token) { redirect(SITE_URL . '/forgot-password.php'); }

$token_stmt = $conn->prepare("SELECT id, email FROM users WHERE reset_token = ? AND reset_expires_at > NOW()");
$token_stmt->bind_param('s', $token);
$token_stmt->execute();
$token_result = $token_stmt->get_result();
$user = $token_result->fetch_assoc();

if (!$user) {
    flashMessage('error', 'Invalid or expired reset link. Please request a new one.');
    redirect(SITE_URL . '/forgot-password.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters';
        if ($password !== $confirm) $errors[] = 'Passwords do not match';

        if (empty($errors)) {
            $result = resetPassword($conn, $token, $password);
            if ($result['success']) {
                flashMessage('success', 'Password reset successfully! Please login.');
                redirect(SITE_URL . '/login.php');
            } else {
                $errors[] = $result['message'];
            }
        }
    }
}

$page_title = 'Reset Password';
require_once 'includes/header.php';
?>

<section class="auth-section py-5">
    <div class="container">
        <div class="auth-container d-flex justify-content-center align-items-center">
            <div class="auth-card card shadow-lg p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="lock-icon-wrap mx-auto mb-3">
                        <i class="fas fa-lock-open" style="font-size:2.5rem;color:#c9a84c;"></i>
                    </div>
                    <h2 class="auth-title" style="font-family:'Playfair Display',serif;">Reset Password</h2>
                    <div class="gold-divider mx-auto my-3"></div>
                    <p class="text-muted">Create a new password for <strong><?php echo htmlspecialchars($user['email']); ?></strong></p>
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

                <form method="POST" action="<?php echo htmlspecialchars(SITE_URL . '/reset-password.php?token=' . urlencode($token)); ?>" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">New Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password"
                                   placeholder="Minimum 8 characters" required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword" tabindex="-1">
                                <i class="fas fa-eye" id="togglePasswordIcon"></i>
                            </button>
                        </div>
                        <div id="password-strength" class="mt-2" style="display:none;">
                            <div class="progress" style="height:6px;">
                                <div id="strength-bar" class="progress-bar" role="progressbar" style="width:0%;"></div>
                            </div>
                            <small id="strength-text" class="text-muted mt-1 d-block"></small>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="confirm_password" class="form-label fw-semibold">Confirm New Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                   placeholder="Re-enter new password" required>
                            <button class="btn btn-outline-secondary" type="button" id="toggleConfirm" tabindex="-1">
                                <i class="fas fa-eye" id="toggleConfirmIcon"></i>
                            </button>
                        </div>
                        <small id="password-match" class="mt-1 d-block" style="display:none;"></small>
                    </div>

                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-gold btn-lg">
                            <i class="fas fa-save me-2"></i>Reset Password
                        </button>
                    </div>

                    <p class="text-center mb-0">
                        <a href="<?php echo htmlspecialchars(SITE_URL . '/login.php'); ?>" class="text-gold">
                            <i class="fas fa-arrow-left me-1"></i>Back to Login
                        </a>
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
.lock-icon-wrap { width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #fdf3dc, #fef9ee); display: flex; align-items: center; justify-content: center; border: 2px solid #e8c96e; }
.input-group-text { background: #fdf9f0; border-color: #ddd; color: #c9a84c; }
.form-control:focus { border-color: #c9a84c; box-shadow: 0 0 0 0.2rem rgba(201,168,76,0.2); }
</style>

<script>
document.getElementById('togglePassword').addEventListener('click', function () {
    const pw = document.getElementById('password');
    const icon = document.getElementById('togglePasswordIcon');
    pw.type = pw.type === 'password' ? 'text' : 'password';
    icon.classList.toggle('fa-eye');
    icon.classList.toggle('fa-eye-slash');
});

document.getElementById('toggleConfirm').addEventListener('click', function () {
    const pw = document.getElementById('confirm_password');
    const icon = document.getElementById('toggleConfirmIcon');
    pw.type = pw.type === 'password' ? 'text' : 'password';
    icon.classList.toggle('fa-eye');
    icon.classList.toggle('fa-eye-slash');
});

document.getElementById('password').addEventListener('input', function () {
    const val = this.value;
    const strengthDiv = document.getElementById('password-strength');
    const bar = document.getElementById('strength-bar');
    const text = document.getElementById('strength-text');

    if (!val) { strengthDiv.style.display = 'none'; return; }
    strengthDiv.style.display = 'block';

    let score = 0;
    if (val.length >= 8) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const levels = [
        { pct: 25, cls: 'bg-danger',  label: 'Weak' },
        { pct: 50, cls: 'bg-warning', label: 'Fair' },
        { pct: 75, cls: 'bg-info',    label: 'Good' },
        { pct: 100, cls: 'bg-success', label: 'Strong' }
    ];
    const level = levels[score - 1] || levels[0];
    bar.style.width = level.pct + '%';
    bar.className = 'progress-bar ' + level.cls;
    text.textContent = 'Password strength: ' + level.label;
    checkMatch();
});

document.getElementById('confirm_password').addEventListener('input', checkMatch);

function checkMatch() {
    const pw = document.getElementById('password').value;
    const cpw = document.getElementById('confirm_password').value;
    const el = document.getElementById('password-match');
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
