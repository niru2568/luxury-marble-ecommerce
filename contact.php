<?php
ob_start();
session_start();
require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/functions.php';

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $name    = sanitize($_POST['name']    ?? '');
        $email   = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $subject = sanitize($_POST['subject'] ?? '');
        $message = sanitize($_POST['message'] ?? '');

        if (!$name)    $errors[] = 'Name is required.';
        if (!$email)   $errors[] = 'A valid email address is required.';
        if (!$subject) $errors[] = 'Subject is required.';
        if (!$message) $errors[] = 'Message is required.';

        if (empty($errors)) {
            $mail_body = "Name: {$name}\nEmail: {$email}\n\nMessage:\n{$message}";
            $headers   = 'From: ' . $email . "\r\nReply-To: " . $email;
            mail(SITE_EMAIL, $subject . ' - Contact Form', $mail_body, $headers);
            $success = true;
        }
    }
}

$page_title       = 'Contact Us';
$meta_description = 'Get in touch with Luxe Marble for inquiries, quotes, and custom marble solutions.';
require_once 'includes/header.php';
?>

<!-- Hero Section -->
<section class="page-hero" style="background: #f8f5f0; padding: 80px 0;">
    <div class="container text-center">
        <h1 class="display-4 fw-bold" style="font-family: 'Playfair Display', serif;">Contact Us</h1>
        <p class="lead text-muted mt-3">We'd love to hear from you. Reach out for quotes, custom projects, or any enquiry.</p>
    </div>
</section>

<!-- Breadcrumb -->
<div class="container mt-3 mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Contact</li>
        </ol>
    </nav>
</div>

<!-- Main Content -->
<section class="py-5">
    <div class="container">
        <div class="row g-5">

            <!-- Contact Form -->
            <div class="col-lg-6">
                <h3 class="fw-bold mb-4" style="font-family: 'Playfair Display', serif;">Send Us a Message</h3>

                <?php if ($success): ?>
                <div class="alert alert-success d-flex align-items-center" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    Thank you! Your message has been sent. We'll get back to you soon.
                </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?>
                            <li><?php echo htmlspecialchars($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name" class="form-control"
                               value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                               placeholder="Your full name" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                        <input type="email" id="email" name="email" class="form-control"
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                               placeholder="your@email.com" required>
                    </div>

                    <div class="mb-3">
                        <label for="subject" class="form-label fw-semibold">Subject <span class="text-danger">*</span></label>
                        <input type="text" id="subject" name="subject" class="form-control"
                               value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>"
                               placeholder="How can we help you?" required>
                    </div>

                    <div class="mb-4">
                        <label for="message" class="form-label fw-semibold">Message <span class="text-danger">*</span></label>
                        <textarea id="message" name="message" class="form-control" rows="6"
                                  placeholder="Tell us about your project or inquiry..." required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                    </div>

                    <button type="submit" class="btn text-white px-5 py-2"
                            style="background: #c9a96e; border-color: #c9a96e; font-size: 1rem;">
                        <i class="fas fa-paper-plane me-2"></i>Send Message
                    </button>
                </form>
            </div>

            <!-- Contact Info -->
            <div class="col-lg-6">
                <h3 class="fw-bold mb-4" style="font-family: 'Playfair Display', serif;">Get In Touch</h3>
                <p class="text-muted mb-4">
                    Whether you're planning a grand renovation or looking for the perfect decorative piece,
                    our experts are here to help you find the finest marble solutions.
                </p>

                <div class="d-flex mb-4">
                    <div class="flex-shrink-0 me-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width: 50px; height: 50px; background: #f8f5f0; border: 2px solid #c9a96e;">
                            <i class="fas fa-phone-alt" style="color: #c9a96e;"></i>
                        </div>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Phone</h6>
                        <p class="text-muted mb-0">
                            <a href="tel:<?php echo defined('SITE_PHONE') ? SITE_PHONE : '+919876543210'; ?>"
                               class="text-decoration-none text-muted">
                                <?php echo defined('SITE_PHONE') ? htmlspecialchars(SITE_PHONE) : '+91 98765 43210'; ?>
                            </a>
                        </p>
                        <small class="text-muted">Mon – Sat, 9 AM – 7 PM</small>
                    </div>
                </div>

                <div class="d-flex mb-4">
                    <div class="flex-shrink-0 me-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width: 50px; height: 50px; background: #f8f5f0; border: 2px solid #c9a96e;">
                            <i class="fas fa-envelope" style="color: #c9a96e;"></i>
                        </div>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Email</h6>
                        <p class="text-muted mb-0">
                            <a href="mailto:<?php echo htmlspecialchars(SITE_EMAIL); ?>"
                               class="text-decoration-none text-muted">
                                <?php echo htmlspecialchars(SITE_EMAIL); ?>
                            </a>
                        </p>
                        <small class="text-muted">We reply within 24 hours</small>
                    </div>
                </div>

                <div class="d-flex mb-4">
                    <div class="flex-shrink-0 me-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width: 50px; height: 50px; background: #f8f5f0; border: 2px solid #c9a96e;">
                            <i class="fas fa-map-marker-alt" style="color: #c9a96e;"></i>
                        </div>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Showroom Address</h6>
                        <p class="text-muted mb-0">
                            Plot No. 42, Marble Market Complex<br>
                            Kishangarh, Rajasthan – 305801<br>
                            India
                        </p>
                    </div>
                </div>

                <div class="d-flex mb-4">
                    <div class="flex-shrink-0 me-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width: 50px; height: 50px; background: #f8f5f0; border: 2px solid #c9a96e;">
                            <i class="fas fa-clock" style="color: #c9a96e;"></i>
                        </div>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Business Hours</h6>
                        <p class="text-muted mb-0">Monday – Saturday: 9:00 AM – 7:00 PM</p>
                        <p class="text-muted mb-0">Sunday: 10:00 AM – 4:00 PM</p>
                    </div>
                </div>

                <!-- Social Links -->
                <div class="mt-4">
                    <h6 class="fw-bold mb-3">Follow Us</h6>
                    <div class="d-flex gap-3">
                        <a href="#" class="text-decoration-none d-flex align-items-center justify-content-center rounded-circle"
                           style="width: 40px; height: 40px; background: #c9a96e; color: #fff;">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="text-decoration-none d-flex align-items-center justify-content-center rounded-circle"
                           style="width: 40px; height: 40px; background: #c9a96e; color: #fff;">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="text-decoration-none d-flex align-items-center justify-content-center rounded-circle"
                           style="width: 40px; height: 40px; background: #c9a96e; color: #fff;">
                            <i class="fab fa-pinterest-p"></i>
                        </a>
                        <a href="#" class="text-decoration-none d-flex align-items-center justify-content-center rounded-circle"
                           style="width: 40px; height: 40px; background: #c9a96e; color: #fff;">
                            <i class="fab fa-youtube"></i>
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
