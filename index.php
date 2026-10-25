<?php
/**
 * Smart City Grievance Management System (CivicFix Pro)
 * Public Landing Page & Role Selection Dashboard
 */
require_once __DIR__ . '/includes/boot.php';

$pageTitle = 'Role Selection';

// Role Cards configuration as required by PRD
$roleCards = [
    [
        'id'          => 'citizen',
        'title'       => 'Citizen Portal',
        'badge'       => 'Public Access',
        'badge_class' => 'bg-primary-subtle text-primary border border-primary-subtle',
        'icon'        => 'bi-person-circle',
        'icon_color'  => '#2563eb',
        'icon_bg'     => '#eff6ff',
        'description' => 'Register complaints, track progress, and view complaint history.',
        'button'      => 'Continue as Citizen',
        'btn_class'   => 'btn-primary-custom',
        'route'       => APP_URL . '/citizen/login.php',
    ],
    [
        'id'          => 'official',
        'title'       => 'Work Official Portal',
        'badge'       => 'Field Workforce',
        'badge_class' => 'bg-success-subtle text-success border border-success-subtle',
        'icon'        => 'bi-tools',
        'icon_color'  => '#0d9488',
        'icon_bg'     => '#f0fdfa',
        'description' => 'View assigned complaints, update work progress, and submit completion evidence.',
        'button'      => 'Continue as Work Official',
        'btn_class'   => 'btn-teal-custom',
        'route'       => APP_URL . '/official/login.php',
    ],
    [
        'id'          => 'dept_admin',
        'title'       => 'Department Admin Portal',
        'badge'       => 'Departmental Operations',
        'badge_class' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
        'icon'        => 'bi-shield-check',
        'icon_color'  => '#0284c7',
        'icon_bg'     => '#f0f9ff',
        'description' => 'Assign and monitor work, verify completion, and manage department complaints.',
        'button'      => 'Continue as Department Admin',
        'btn_class'   => 'btn-navy-custom',
        'route'       => APP_URL . '/department-admin/login.php',
    ],
    [
        'id'          => 'super_admin',
        'title'       => 'Super Admin Portal',
        'badge'       => 'System Governance',
        'badge_class' => 'bg-dark-subtle text-dark border border-dark-subtle',
        'icon'        => 'bi-shield-lock-fill',
        'icon_color'  => '#1e293b',
        'icon_bg'     => '#f8fafc',
        'description' => 'Manage departments, users, system configuration, reports, and overall operations.',
        'button'      => 'Continue as Super Admin',
        'btn_class'   => 'btn-dark-custom',
        'route'       => APP_URL . '/super-admin/login.php',
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart City Grievance Management System — CivicFix Pro</title>

    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- App Stylesheet -->
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">

    <style>
        .role-portal-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 2rem 1.75rem;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05);
            text-decoration: none !important;
            color: inherit;
        }

        .role-portal-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.12);
            border-color: #cbd5e1;
            color: inherit;
        }

        .role-icon-box {
            width: 68px;
            height: 68px;
            border-radius: 0.85rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1.25rem;
            transition: transform 0.2s ease;
        }

        .role-portal-card:hover .role-icon-box {
            transform: scale(1.08);
        }

        .btn-teal-custom {
            background-color: #0d9488;
            color: #ffffff;
            border: none;
            font-weight: 600;
        }
        .btn-teal-custom:hover {
            background-color: #0f766e;
            color: #ffffff;
        }

        .btn-navy-custom {
            background-color: #1a3c6e;
            color: #ffffff;
            border: none;
            font-weight: 600;
        }
        .btn-navy-custom:hover {
            background-color: #122d55;
            color: #ffffff;
        }

        .btn-dark-custom {
            background-color: #1e293b;
            color: #ffffff;
            border: none;
            font-weight: 600;
        }
        .btn-dark-custom:hover {
            background-color: #0f172a;
            color: #ffffff;
        }

        .hero-banner {
            background: linear-gradient(135deg, #1a3c6e 0%, #122d55 55%, #082f49 100%);
            color: #ffffff;
            padding: 4.5rem 0 3.75rem;
            position: relative;
            overflow: hidden;
        }

        .hero-banner::after {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 80% 20%, rgba(13, 148, 136, 0.25) 0%, transparent 50%);
            pointer-events: none;
        }

        .header-brand-logo {
            width: 44px;
            height: 44px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 0.6rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #93c5fd;
        }
    </style>
</head>
<body class="bg-light d-flex flex-column min-vh-100">

<!-- ═══════════════════════════════════════════════════════════ -->
<!--  SITE HEADER                                               -->
<!-- ═══════════════════════════════════════════════════════════ -->
<header class="bg-white border-bottom sticky-top py-2 shadow-sm">
    <div class="container d-flex align-items-center justify-content-between">
        <a href="<?= APP_URL ?>/" class="d-flex align-items-center gap-2 text-decoration-none">
            <div class="header-brand-logo bg-primary text-white">
                <i class="bi bi-buildings-fill"></i>
            </div>
            <div>
                <span class="fw-bold fs-5 text-primary-custom d-block lh-1"><?= e(APP_NAME) ?></span>
                <span class="text-muted" style="font-size: 0.72rem; letter-spacing: 0.05em; text-transform: uppercase;">Smart City Grievance Platform</span>
            </div>
        </a>

        <div class="d-flex align-items-center gap-2">
            <?php if (isLoggedIn()): ?>
                <?php
                $dashUrl = match($_SESSION['role'] ?? '') {
                    'citizen'          => APP_URL . '/citizen/dashboard.php',
                    'work_official'    => APP_URL . '/official/dashboard.php',
                    'department_admin' => APP_URL . '/department/dashboard.php',
                    'super_admin'      => APP_URL . '/superadmin/dashboard.php',
                    default            => APP_URL . '/',
                };
                ?>
                <a href="<?= $dashUrl ?>" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-speedometer2 me-1"></i> My Dashboard
                </a>
                <a href="<?= APP_URL ?>/auth/logout.php" class="btn btn-sm btn-light text-danger">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            <?php else: ?>
                <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill small border border-success-subtle">
                    <i class="bi bi-circle-fill me-1" style="font-size: 0.55rem;"></i> System Live & Operational
                </span>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- ═══════════════════════════════════════════════════════════ -->
<!--  HERO INTRODUCTION                                         -->
<!-- ═══════════════════════════════════════════════════════════ -->
<section class="hero-banner text-center">
    <div class="container position-relative" style="z-index: 1;">
        <div class="badge px-3 py-2 rounded-pill mb-3" style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25); color: #bae6fd;">
            <i class="bi bi-shield-check me-1"></i> Municipal Corporation Digital Gateway
        </div>
        <h1 class="display-5 fw-bold mb-3 text-white">Smart City Grievance Management System</h1>
        <p class="lead text-info-emphasis mb-3 mx-auto" style="color: #cbd5e1 !important; max-width: 680px; font-weight: 400;">
            <em>One platform for a cleaner, safer, better-managed city.</em>
        </p>
        <p class="text-white-50 mx-auto small mb-0" style="max-width: 640px; font-size: 0.95rem; line-height: 1.6;">
            Citizens can report civic problems including potholes, streetlights, and sanitation, while municipal authorities and field teams automatically route, manage, and verify issues until full resolution.
        </p>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════ -->
<!--  ROLE SELECTION DASHBOARD (MAIN CONTENT)                   -->
<!-- ═══════════════════════════════════════════════════════════ -->
<main class="flex-grow-1 py-5">
    <div class="container">
        <?php if (isLoggedIn()): ?>
            <div class="alert alert-primary border-primary-subtle d-flex flex-wrap align-items-center justify-content-between p-3 rounded-3 shadow-sm mb-5">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; font-size: 1.25rem;">
                        <i class="bi bi-person-badge-fill"></i>
                    </div>
                    <div>
                        <div class="text-dark fw-semibold">Active Session: <?= e($_SESSION['user_name'] ?? 'User') ?> <span class="badge bg-primary ms-1"><?= e(ucwords(str_replace('_', ' ', $_SESSION['role'] ?? ''))) ?></span></div>
                        <div class="text-muted small">You are signed in. Continue to your dashboard, or select any role below to switch account.</div>
                    </div>
                </div>
                <div class="d-flex gap-2 mt-3 mt-md-0">
                    <?php
                    $activeDash = match($_SESSION['role'] ?? '') {
                        'citizen'          => APP_URL . '/citizen/dashboard.php',
                        'work_official'    => APP_URL . '/official/dashboard.php',
                        'department_admin' => APP_URL . '/department/dashboard.php',
                        'super_admin'      => APP_URL . '/superadmin/dashboard.php',
                        default            => APP_URL . '/',
                    };
                    ?>
                    <a href="<?= $activeDash ?>" class="btn btn-primary btn-sm px-3">
                        <i class="bi bi-speedometer2 me-1"></i> My Dashboard
                    </a>
                    <a href="<?= APP_URL ?>/auth/logout.php" class="btn btn-outline-danger btn-sm px-3">
                        <i class="bi bi-box-arrow-right me-1"></i> Logout / Switch
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <div class="text-center mb-5">
            <h2 class="fw-bold text-primary-custom mb-2">Select Your Portal</h2>
            <p class="text-muted">Choose your authorized municipal role to proceed to the secure sign-in gateway.</p>
        </div>

        <div class="row g-4">
            <?php foreach ($roleCards as $card): ?>
                <div class="col-xl-3 col-md-6 col-12">
                    <a href="<?= e($card['route']) ?>" class="role-portal-card" aria-label="<?= e($card['title']) ?>">
                        <div>
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="role-icon-box" style="background-color: <?= e($card['icon_bg']) ?>; color: <?= e($card['icon_color']) ?>;">
                                    <i class="bi <?= e($card['icon']) ?>"></i>
                                </div>
                                <span class="badge <?= e($card['badge_class']) ?> rounded-pill small">
                                    <?= e($card['badge']) ?>
                                </span>
                            </div>

                            <h4 class="fw-bold text-dark mb-2"><?= e($card['title']) ?></h4>
                            <p class="text-muted small mb-4" style="line-height: 1.55; min-height: 48px;">
                                <?= e($card['description']) ?>
                            </p>
                        </div>

                        <div>
                            <span class="btn w-100 py-2 d-flex align-items-center justify-content-center gap-2 <?= e($card['btn_class']) ?>">
                                <span><?= e($card['button']) ?></span>
                                <i class="bi bi-arrow-right"></i>
                            </span>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- System Capabilities Info Strip -->
        <div class="card mt-5 border-0 shadow-sm" style="background: #ffffff; border-radius: 1rem;">
            <div class="card-body p-4">
                <div class="row g-4 text-center">
                    <div class="col-md-3 col-6">
                        <div class="d-flex align-items-center justify-content-center gap-2 text-primary mb-1">
                            <i class="bi bi-diagram-3-fill fs-4"></i>
                            <strong class="text-dark">Auto-Routing</strong>
                        </div>
                        <small class="text-muted">Automated category to department allocation</small>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="d-flex align-items-center justify-content-center gap-2 text-success mb-1">
                            <i class="bi bi-clock-history fs-4"></i>
                            <strong class="text-dark">Live Tracking</strong>
                        </div>
                        <small class="text-muted">Real-time milestones from filing to resolution</small>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="d-flex align-items-center justify-content-center gap-2 text-info mb-1">
                            <i class="bi bi-patch-check-fill fs-4"></i>
                            <strong class="text-dark">Admin Verification</strong>
                        </div>
                        <small class="text-muted">Work completion audited with photo evidence</small>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="d-flex align-items-center justify-content-center gap-2 text-warning mb-1">
                            <i class="bi bi-shield-lock-fill fs-4"></i>
                            <strong class="text-dark">Secure & Audited</strong>
                        </div>
                        <small class="text-muted">Role-based permission guards & audit logs</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- ═══════════════════════════════════════════════════════════ -->
<!--  SITE FOOTER                                               -->
<!-- ═══════════════════════════════════════════════════════════ -->
<footer class="bg-white border-top py-4 mt-auto">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-buildings-fill text-primary"></i>
            <span class="small fw-bold text-dark"><?= e(APP_NAME) ?></span>
            <span class="text-muted small">&bull; Municipal Grievance Redressal</span>
        </div>
        <p class="mb-0 small text-muted">
            &copy; <?= date('Y') ?> Municipal Administration & Urban Development. All rights reserved.
        </p>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
