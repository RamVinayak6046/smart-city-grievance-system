<?php
require_once __DIR__ . '/includes/boot.php';
$pageTitle   = 'About Us';
$currentPage = 'about';
include __DIR__ . '/includes/header.php';
?>
<section class="py-5">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <span class="badge bg-primary mb-3 px-3 py-2">About CivicFix Pro</span>
                <h1 class="section-title">A Smarter Way to Manage Municipal Complaints</h1>
                <p class="text-muted">
                    CivicFix Pro is a full-stack PHP/MySQL web application designed to digitize civic complaint
                    management for urban local bodies. Citizens can report public infrastructure issues,
                    and the system automatically routes each complaint to the appropriate department and
                    assigns it to a qualified work official.
                </p>
                <p class="text-muted">
                    Built on transparent, auditable workflows — every action is logged, every status
                    change tracked, and every resolution verified by a Department Administrator before
                    the complaint is closed.
                </p>
                <div class="row g-3 mt-2">
                    <div class="col-6">
                        <div class="p-3 bg-light rounded text-center">
                            <i class="bi bi-people fs-2 text-primary mb-2 d-block"></i>
                            <div class="fw-bold">4 User Roles</div>
                            <small class="text-muted">Citizen, Official, Dept Admin, Super Admin</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-light rounded text-center">
                            <i class="bi bi-diagram-3 fs-2 text-primary mb-2 d-block"></i>
                            <div class="fw-bold">Auto-Routing</div>
                            <small class="text-muted">Ward + workload based assignment</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-light rounded text-center">
                            <i class="bi bi-database fs-2 text-primary mb-2 d-block"></i>
                            <div class="fw-bold">MySQL Backed</div>
                            <small class="text-muted">Persistent, relational, transactional</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-light rounded text-center">
                            <i class="bi bi-shield-check fs-2 text-primary mb-2 d-block"></i>
                            <div class="fw-bold">Secure</div>
                            <small class="text-muted">CSRF, prepared statements, role guards</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3 text-primary">Technology Stack</h5>
                        <ul class="list-group list-group-flush">
                            <?php
                            $stack = [
                                ['Frontend',     'HTML5, CSS3, Bootstrap 5'],
                                ['Backend',      'PHP 8.2+'],
                                ['Database',     'MySQL 8 / MariaDB (PDO)'],
                                ['Auth',         'PHP Sessions + password_hash()'],
                                ['Security',     'CSRF tokens, prepared statements'],
                                ['Charts',       'Chart.js 4'],
                                ['PDF Reports',  'Dompdf'],
                                ['Excel Reports','PhpSpreadsheet'],
                                ['Icons',        'Bootstrap Icons'],
                                ['Server',       'XAMPP (Apache + MySQL)'],
                            ];
                            foreach ($stack as [$label, $value]):
                            ?>
                                <li class="list-group-item d-flex justify-content-between px-0 py-2 border-top">
                                    <span class="fw-semibold text-muted small"><?= e($label) ?></span>
                                    <span class="small text-end"><?= e($value) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
