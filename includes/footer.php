<!-- ═══════════════════════════════════════════════════════════ -->
<!--  PUBLIC FOOTER                                            -->
<!-- ═══════════════════════════════════════════════════════════ -->
<footer class="footer-custom mt-5 py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-buildings-fill fs-3 text-primary-light"></i>
                    <span class="fw-bold fs-5 text-white"><?= e(APP_NAME) ?></span>
                </div>
                <p class="text-footer-muted small">
                    A digital platform for smart, transparent municipal grievance management. 
                    Report civic issues and track their resolution in real time.
                </p>
            </div>
            <div class="col-lg-2 col-sm-6">
                <h6 class="text-white fw-semibold mb-3">Quick Links</h6>
                <ul class="list-unstyled small">
                    <li><a href="<?= APP_URL ?>/" class="footer-link">Home</a></li>
                    <li><a href="<?= APP_URL ?>/about.php" class="footer-link">About</a></li>
                    <li><a href="<?= APP_URL ?>/contact.php" class="footer-link">Contact</a></li>
                </ul>
            </div>
            <div class="col-lg-3 col-sm-6">
                <h6 class="text-white fw-semibold mb-3">Citizen Portal</h6>
                <ul class="list-unstyled small">
                    <li><a href="<?= APP_URL ?>/auth/citizen_register.php" class="footer-link">Register</a></li>
                    <li><a href="<?= APP_URL ?>/auth/citizen_login.php" class="footer-link">Login</a></li>
                    <li><a href="<?= APP_URL ?>/citizen/submit_complaint.php" class="footer-link">Report an Issue</a></li>
                </ul>
            </div>
            <div class="col-lg-3 col-sm-6">
                <h6 class="text-white fw-semibold mb-3">Municipal Staff</h6>
                <ul class="list-unstyled small">
                    <li><a href="<?= APP_URL ?>/auth/staff_login.php" class="footer-link">Staff Login</a></li>
                </ul>
                <div class="mt-3">
                    <small class="text-footer-muted">
                        <i class="bi bi-telephone me-1"></i> Helpline: 1800-CIVIC-FIX
                    </small>
                </div>
            </div>
        </div>

        <hr class="border-secondary mt-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <p class="mb-0 small text-footer-muted">
                &copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. All rights reserved.
            </p>
            <p class="mb-0 small text-footer-muted">
                Built with PHP <?= PHP_MAJOR_VERSION ?>.<?= PHP_MINOR_VERSION ?> &amp; MySQL
            </p>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- App JS -->
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body>
</html>
