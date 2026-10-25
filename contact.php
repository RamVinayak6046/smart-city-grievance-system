<?php
require_once __DIR__ . '/includes/boot.php';
$pageTitle   = 'Contact Us';
$currentPage = 'contact';

$success = false;
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name    = sanitize($_POST['name']    ?? '');
    $email   = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $subject = sanitize($_POST['subject'] ?? '');
    $message = sanitize($_POST['message'] ?? '');

    if (empty($name))    $errors[] = 'Name is required.';
    if (!$email)         $errors[] = 'A valid email address is required.';
    if (empty($subject)) $errors[] = 'Subject is required.';
    if (strlen($message) < 20) $errors[] = 'Message must be at least 20 characters.';

    if (empty($errors)) {
        // In a real deployment, send email here.
        // For now, just record the contact request in audit log if DB is up.
        try {
            $pdo = db();
            $stmt = $pdo->prepare(
                "INSERT INTO audit_logs (actor_id, actor_role, action, entity_type, details, ip_address)
                 VALUES (NULL, 'system', 'contact_form_submitted', 'contact', :details, :ip)"
            );
            $stmt->execute([
                ':details' => json_encode(['name' => $name, 'email' => $email, 'subject' => $subject]),
                ':ip'      => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
        } catch (PDOException) {
            // Silently ignore if DB not set up yet
        }
        $success = true;
    }
}

include __DIR__ . '/includes/header.php';
?>
<section class="py-5">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-5">
                <h1 class="section-title">Contact Us</h1>
                <p class="text-muted mb-4">Have questions about CivicFix Pro or need help? Reach out below.</p>
                <ul class="list-unstyled">
                    <li class="d-flex gap-3 mb-3">
                        <i class="bi bi-telephone-fill fs-4 text-primary"></i>
                        <div>
                            <div class="fw-semibold">Helpline</div>
                            <div class="text-muted">1800-CIVIC-FIX (Mon–Sat, 9am–6pm)</div>
                        </div>
                    </li>
                    <li class="d-flex gap-3 mb-3">
                        <i class="bi bi-envelope-fill fs-4 text-primary"></i>
                        <div>
                            <div class="fw-semibold">Email</div>
                            <div class="text-muted">support@civicfix.local</div>
                        </div>
                    </li>
                    <li class="d-flex gap-3 mb-3">
                        <i class="bi bi-geo-alt-fill fs-4 text-primary"></i>
                        <div>
                            <div class="fw-semibold">Office</div>
                            <div class="text-muted">Municipal Corporation Building,<br>Civil Lines, Ward 5</div>
                        </div>
                    </li>
                </ul>
            </div>

            <div class="col-lg-7">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle me-2"></i>
                        Thank you for reaching out! Your message has been recorded. We will respond shortly.
                    </div>
                <?php else: ?>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <?= implode('<br>', array_map('e', $errors)) ?>
                        </div>
                    <?php endif; ?>
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-4">Send a Message</h5>
                            <form method="post" novalidate>
                                <?= csrfField() ?>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Your Name <span class="required-star">*</span></label>
                                        <input type="text" name="name" class="form-control" maxlength="120"
                                               value="<?= e($_POST['name'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email Address <span class="required-star">*</span></label>
                                        <input type="email" name="email" class="form-control"
                                               value="<?= e($_POST['email'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Subject <span class="required-star">*</span></label>
                                        <input type="text" name="subject" class="form-control" maxlength="150"
                                               value="<?= e($_POST['subject'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Message <span class="required-star">*</span></label>
                                        <textarea name="message" class="form-control" rows="6" maxlength="2000" required><?= e($_POST['message'] ?? '') ?></textarea>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary px-5 fw-semibold">
                                            <i class="bi bi-send me-2"></i>Send Message
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
