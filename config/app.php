<?php
/**
 * CivicFix Pro — Application Configuration
 */

// ── Timezone ────────────────────────────────────────────────
define('APP_TIMEZONE', 'Asia/Kolkata');
date_default_timezone_set(APP_TIMEZONE);

// ── App identity ─────────────────────────────────────────────
define('APP_NAME',    'CivicFix Pro');
define('APP_VERSION', '1.0.0');

// Dynamically detect host & protocol (supports Cloudflare/Render reverse proxies)
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            ? 'https://' : 'http://';
$detectedUrl = isset($_SERVER['HTTP_HOST']) ? ($protocol . $_SERVER['HTTP_HOST']) : 'http://localhost:8000';
define('APP_URL', rtrim(getenv('APP_URL') ?: $detectedUrl, '/'));   // no trailing slash

// ── Session ──────────────────────────────────────────────────
define('SESSION_NAME',     'CIVICFIX_SESSION');
define('SESSION_LIFETIME', 7200);   // 2 hours in seconds

// ── Upload settings ──────────────────────────────────────────
define('UPLOAD_DIR',         __DIR__ . '/../uploads/');
define('UPLOAD_URL',         APP_URL . '/uploads/');
define('MAX_UPLOAD_SIZE',    5 * 1024 * 1024);  // 5 MB
define('MAX_PHOTOS',         3);
define('ALLOWED_MIME_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// ── Complaint ID ──────────────────────────────────────────────
define('COMPLAINT_ID_PREFIX', 'CFX');

// ── Pagination ───────────────────────────────────────────────
define('ITEMS_PER_PAGE', 15);

// ── SLA thresholds (days) ────────────────────────────────────
define('SLA_LOW',      7);
define('SLA_MEDIUM',   5);
define('SLA_HIGH',     3);
define('SLA_CRITICAL', 1);

// ── Environment ──────────────────────────────────────────────
define('APP_ENV', getenv('APP_ENV') ?: 'development');   // 'production' on live server

// Show PHP errors only in development
if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}
