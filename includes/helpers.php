<?php
/**
 * CivicFix Pro — Global Helper Functions
 */

// ── Output / Security ─────────────────────────────────────────
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function sanitize(string $value): string
{
    return trim(strip_tags($value));
}

// ── Session helpers ──────────────────────────────────────────
function sessionStart(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => false,   // set true on HTTPS
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id'], $_SESSION['role']);
}

function requireLogin(?string $redirect = null): void
{
    if (!isLoggedIn()) {
        redirectTo($redirect ?? (APP_URL . '/auth/citizen_login.php'));
    }
}

function requireRole(string $role): void
{
    requireLogin();
    if ($_SESSION['role'] !== $role) {
        http_response_code(403);
        include __DIR__ . '/../includes/403.php';
        exit;
    }
}

function currentUser(): array
{
    return [
        'id'   => $_SESSION['user_id'] ?? null,
        'name' => $_SESSION['user_name'] ?? 'Guest',
        'role' => $_SESSION['role'] ?? null,
        'dept' => $_SESSION['dept_id'] ?? null,
    ];
}

// ── CSRF ─────────────────────────────────────────────────────
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function verifyCsrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrfToken(), $token)) {
        http_response_code(403);
        die('CSRF token mismatch. Please go back and try again.');
    }
}

// ── Flash messages ────────────────────────────────────────────
function flash(string $key, string $message, string $type = 'info'): void
{
    $_SESSION['flash'][$key] = ['message' => $message, 'type' => $type];
}

function getFlash(string $key): ?array
{
    if (isset($_SESSION['flash'][$key])) {
        $data = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $data;
    }
    return null;
}

// ── Redirect ─────────────────────────────────────────────────
function redirectTo(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function redirectBack(string $fallback = '/'): never
{
    $ref = $_SERVER['HTTP_REFERER'] ?? $fallback;
    redirectTo($ref);
}

// ── Complaint ID generation ────────────────────────────────────
function generateComplaintCode(PDO $pdo): string
{
    $year   = date('Y');
    $prefix = COMPLAINT_ID_PREFIX . '-' . $year . '-';

    // Get the highest existing sequence for this year
    $stmt = $pdo->prepare(
        "SELECT complaint_code FROM complaints
         WHERE complaint_code LIKE :prefix
         ORDER BY id DESC LIMIT 1
         FOR UPDATE"
    );
    $stmt->execute([':prefix' => $prefix . '%']);
    $last = $stmt->fetchColumn();

    $seq = 1;
    if ($last) {
        $parts = explode('-', $last);
        $seq   = (int) end($parts) + 1;
    }

    return $prefix . str_pad($seq, 6, '0', STR_PAD_LEFT);
}

// ── Date formatting ───────────────────────────────────────────
function formatDate(string $datetime, string $format = 'd M Y, h:i A'): string
{
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00') {
        return '—';
    }
    try {
        $dt = new DateTime($datetime, new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone(APP_TIMEZONE));
        return $dt->format($format);
    } catch (Exception) {
        return $datetime;
    }
}

function timeAgo(string $datetime): string
{
    try {
        $dt   = new DateTime($datetime, new DateTimeZone('UTC'));
        $now  = new DateTime('now', new DateTimeZone('UTC'));
        $diff = $now->diff($dt);

        if ($diff->days === 0) {
            if ($diff->h === 0) return $diff->i . ' min ago';
            return $diff->h . ' hr ago';
        }
        if ($diff->days < 7) return $diff->days . ' day' . ($diff->days > 1 ? 's' : '') . ' ago';
        if ($diff->days < 30) return floor($diff->days / 7) . ' week' . (floor($diff->days / 7) > 1 ? 's' : '') . ' ago';
        return $dt->setTimezone(new DateTimeZone(APP_TIMEZONE))->format('d M Y');
    } catch (Exception) {
        return $datetime;
    }
}

// ── Status badge HTML ─────────────────────────────────────────
function statusBadge(string $status): string
{
    $map = [
        'pending'               => ['Pending',                'badge-pending'],
        'assigned'              => ['Assigned',               'badge-assigned'],
        'in_progress'           => ['In Progress',            'badge-inprogress'],
        'awaiting_verification' => ['Awaiting Verification',  'badge-awaiting'],
        'rework_required'       => ['Rework Required',        'badge-rework'],
        'resolved'              => ['Resolved',               'badge-resolved'],
        'rejected'              => ['Rejected',               'badge-rejected'],
        'cancelled'             => ['Cancelled',              'badge-cancelled'],
    ];
    $info  = $map[$status] ?? [ucwords(str_replace('_', ' ', $status)), 'bg-secondary'];
    return '<span class="badge ' . e($info[1]) . '">' . e($info[0]) . '</span>';
}

function priorityBadge(string $priority): string
{
    $map = [
        'low'      => 'badge-priority-low',
        'medium'   => 'badge-priority-medium',
        'high'     => 'badge-priority-high',
        'critical' => 'badge-priority-critical',
    ];
    $cls = $map[$priority] ?? 'bg-secondary';
    return '<span class="badge ' . e($cls) . '">' . e(ucfirst($priority)) . '</span>';
}

// ── Pagination ────────────────────────────────────────────────
function paginate(int $total, int $perPage, int $current): array
{
    $totalPages = (int) ceil($total / $perPage);
    return [
        'total'       => $total,
        'per_page'    => $perPage,
        'current'     => $current,
        'total_pages' => $totalPages,
        'offset'      => ($current - 1) * $perPage,
        'has_prev'    => $current > 1,
        'has_next'    => $current < $totalPages,
    ];
}

// ── File upload ───────────────────────────────────────────────
function validateUpload(array $file): array
{
    $errors = [];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Upload error code: ' . $file['error'];
        return $errors;
    }
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        $errors[] = 'File size exceeds ' . (MAX_UPLOAD_SIZE / 1024 / 1024) . ' MB limit.';
    }

    // Validate MIME from actual file content
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    if (!in_array($mimeType, ALLOWED_MIME_TYPES, true)) {
        $errors[] = 'File type not allowed. Use JPG, PNG, GIF, or WebP.';
    }

    // Validate extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS, true)) {
        $errors[] = 'File extension not allowed.';
    }

    return $errors;
}

function saveUpload(array $file, string $subdir = ''): ?string
{
    $errors = validateUpload($file);
    if (!empty($errors)) return null;

    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    $dir = UPLOAD_DIR . ($subdir ? rtrim($subdir, '/') . '/' : '');
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $dest     = $dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return null;
    }

    return ($subdir ? $subdir . '/' : '') . $filename;
}

// ── Notifications count (for nav bar) ─────────────────────────
function unreadNotificationCount(int $recipientId, string $recipientType): int
{
    try {
        $stmt = db()->prepare(
            "SELECT COUNT(*) FROM notifications
             WHERE recipient_id = :rid AND recipient_type = :rtype AND is_read = 0"
        );
        $stmt->execute([':rid' => $recipientId, ':rtype' => $recipientType]);
        return (int) $stmt->fetchColumn();
    } catch (PDOException) {
        return 0;
    }
}

// ── Role → type string ─────────────────────────────────────────
function roleToRecipientType(string $role): string
{
    return match ($role) {
        'work_official'    => 'work_official',
        'department_admin' => 'department_admin',
        'super_admin'      => 'super_admin',
        default            => 'citizen',
    };
}
