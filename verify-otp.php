<?php
ob_start();
session_start();
require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

if (!isset($_SESSION['pending_email'])) { redirect(SITE_URL . '/register.php'); }

$email = $_SESSION['pending_email'];
$masked_email = substr($email, 0, 3) . '****' . substr($email, strpos($email, '@'));
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $otp = '';
        for ($i = 1; $i <= 6; $i++) {
            $otp .= sanitize($_POST['otp_' . $i] ?? '');
        }

        if (strlen($otp) !== 6 || !ctype_digit($otp)) {
            $errors[] = 'Please enter a valid 6-digit OTP';
        } else {
            $result = verifyOTP($conn, $email, $otp);
            if ($result['success']) {
                unset($_SESSION['pending_email']);
                flashMessage('success', 'Email verified successfully! You can now login.');
                redirect(SITE_URL . '/login.php');
            } else {
                $errors[] = $result['message'];
            }
        }
    }
}

$page_title = 'Verify OTP';
require_once 'includes/header.php';
?>

<section class="auth-section py-5">
    <div class="container">
        <div class="auth-container d-flex justify-content-center align-items-center">
            <div class="auth-card card shadow-lg p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="otp-icon-wrap mx-auto mb-3">
                        <i class="fas fa-envelope-open-text otp-icon"></i>
                    </div>
                    <h2 class="auth-title" style="font-family:'Playfair Display',serif;">Verify Your Email</h2>
                    <div class="gold-divider mx-auto my-3"></div>
                    <p class="text-muted mb-1">We sent a 6-digit OTP to</p>
                    <p class="fw-semibold text-dark"><?php echo htmlspecialchars($masked_email); ?></p>
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

                <form method="POST" action="<?php echo htmlspecialchars(SITE_URL . '/verify-otp.php'); ?>" id="otp-form" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">

                    <div class="otp-inputs d-flex justify-content-center gap-2 mb-4">
                        <?php for ($i = 1; $i <= 6; $i++): ?>
                            <input type="text" inputmode="numeric" pattern="[0-9]"
                                   class="form-control otp-input text-center fw-bold fs-4"
                                   id="otp-<?php echo $i; ?>"
                                   name="otp_<?php echo $i; ?>"
                                   maxlength="1"
                                   autocomplete="<?php echo $i === 1 ? 'one-time-code' : 'off'; ?>"
                                   required>
                        <?php endfor; ?>
                    </div>

                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-gold btn-lg">
                            <i class="fas fa-check-circle me-2"></i>Verify OTP
                        </button>
                    </div>

                    <p class="text-center mb-0">
                        Didn't receive OTP?
                        <a href="#" id="resend-otp-btn" class="text-gold fw-semibold">Resend OTP</a>
                    </p>
                    <div id="resend-message" class="text-center mt-2" style="display:none;"></div>
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
.otp-icon-wrap { width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #fdf3dc, #fef9ee); display: flex; align-items: center; justify-content: center; border: 2px solid #e8c96e; }
.otp-icon { font-size: 2.2rem; color: #c9a84c; }
.otp-input { width: 50px !important; height: 56px; border-radius: 8px; border: 1.5px solid #ddd; transition: border-color 0.2s, box-shadow 0.2s; }
.otp-input:focus { border-color: #c9a84c; box-shadow: 0 0 0 0.2rem rgba(201,168,76,0.25); outline: none; }
</style>

<script>
(function () {
    const inputs = Array.from(document.querySelectorAll('.otp-input'));

    inputs.forEach(function (input, idx) {
        input.addEventListener('input', function () {
            // Strip non-digits
            this.value = this.value.replace(/\D/g, '').slice(0, 1);
            if (this.value && idx < inputs.length - 1) {
                inputs[idx + 1].focus();
            }
        });

        input.addEventListener('keydown', function (e) {
            if (e.key === 'Backspace' && !this.value && idx > 0) {
                inputs[idx - 1].focus();
            }
        });

        input.addEventListener('paste', function (e) {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
            pasted.split('').slice(0, 6).forEach(function (digit, i) {
                if (inputs[idx + i]) inputs[idx + i].value = digit;
            });
            const next = Math.min(idx + pasted.length, inputs.length - 1);
            inputs[next].focus();
        });
    });

    // Resend OTP via AJAX
    document.getElementById('resend-otp-btn').addEventListener('click', function (e) {
        e.preventDefault();
        const btn = this;
        const msgEl = document.getElementById('resend-message');
        btn.style.pointerEvents = 'none';
        btn.textContent = 'Sending…';

        const formData = new FormData();
        formData.append('email', document.querySelector('input[name="email"]').value);

        fetch('<?php echo htmlspecialchars(SITE_URL . '/api/send-otp.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            msgEl.style.display = 'block';
            if (data.success) {
                msgEl.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i>' + data.message + '</span>';
            } else {
                msgEl.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>' + data.message + '</span>';
            }
            // Re-enable after 30 seconds
            setTimeout(function () {
                btn.style.pointerEvents = 'auto';
                btn.textContent = 'Resend OTP';
            }, 30000);
        })
        .catch(function () {
            msgEl.style.display = 'block';
            msgEl.innerHTML = '<span class="text-danger">Failed to resend OTP. Please try again.</span>';
            btn.style.pointerEvents = 'auto';
            btn.textContent = 'Resend OTP';
        });
    });
}());
</script>

<?php require_once 'includes/footer.php'; ?>
