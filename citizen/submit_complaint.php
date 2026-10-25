<?php
/**
 * Citizen — Submit Complaint
 */
$requiredRole = 'citizen';
require_once __DIR__ . '/../middleware/role_guard.php';
require_once __DIR__ . '/../services/AssignmentService.php';

$citizenId = $_SESSION['user_id'];
$pdo       = db();
$errors    = [];

// Load categories & wards
$categories = $pdo->query(
    "SELECT id, category_name, default_priority FROM categories WHERE status='active' ORDER BY category_name"
)->fetchAll();
$wards = $pdo->query(
    "SELECT id, ward_name FROM wards WHERE status='active' ORDER BY ward_name"
)->fetchAll();

// Get citizen's ward as default
$citizenWard = $pdo->prepare("SELECT ward_id FROM users WHERE id = :id");
$citizenWard->execute([':id' => $citizenId]);
$defaultWardId = $citizenWard->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $title       = sanitize($_POST['title']       ?? '');
    $categoryId  = (int)($_POST['category_id']    ?? 0);
    $wardId      = (int)($_POST['ward_id']         ?? 0);
    $description = sanitize($_POST['description'] ?? '');
    $address     = sanitize($_POST['address']      ?? '');
    $latitude    = !empty($_POST['latitude'])  ? (float)$_POST['latitude']  : null;
    $longitude   = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null;

    // ── Validation ────────────────────────────────────────────
    if (empty($title) || strlen($title) < 5)   $errors[] = 'Title must be at least 5 characters.';
    if ($categoryId < 1)                        $errors[] = 'Please select a complaint category.';
    if ($wardId < 1)                            $errors[] = 'Please select the ward.';
    if (strlen($description) < 20)             $errors[] = 'Description must be at least 20 characters.';

    // ── File upload ───────────────────────────────────────────
    $uploadedPaths = [];
    $uploadedMeta  = [];
    if (!empty($_FILES['photos']['name'])) {
        $names = is_array($_FILES['photos']['name']) ? $_FILES['photos']['name'] : [$_FILES['photos']['name']];
        $tmpNames = is_array($_FILES['photos']['tmp_name']) ? $_FILES['photos']['tmp_name'] : [$_FILES['photos']['tmp_name']];
        $sizes = is_array($_FILES['photos']['size']) ? $_FILES['photos']['size'] : [$_FILES['photos']['size']];
        $errorsList = is_array($_FILES['photos']['error']) ? $_FILES['photos']['error'] : [$_FILES['photos']['error']];
        $types = is_array($_FILES['photos']['type']) ? $_FILES['photos']['type'] : [$_FILES['photos']['type']];

        $fileCount = count($names);
        if ($fileCount > MAX_PHOTOS) {
            $errors[] = 'Maximum ' . MAX_PHOTOS . ' photos allowed.';
        } else {
            for ($i = 0; $i < $fileCount; $i++) {
                $singleFile = [
                    'name'     => $names[$i] ?? '',
                    'tmp_name' => $tmpNames[$i] ?? '',
                    'size'     => $sizes[$i] ?? 0,
                    'error'    => $errorsList[$i] ?? UPLOAD_ERR_NO_FILE,
                    'type'     => $types[$i] ?? '',
                ];
                if ($singleFile['error'] === UPLOAD_ERR_NO_FILE || empty($singleFile['tmp_name'])) continue;

                $uploadErrors = validateUpload($singleFile);
                if (!empty($uploadErrors)) {
                    $errors = array_merge($errors, $uploadErrors);
                    break;
                }

                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime  = $finfo->file($singleFile['tmp_name']);
                $path  = saveUpload($singleFile, 'complaints');
                if ($path) {
                    $uploadedPaths[] = $path;
                    $uploadedMeta[]  = [
                        'path'      => $path,
                        'original'  => $singleFile['name'],
                        'mime'      => $mime,
                        'size'      => $singleFile['size'],
                    ];
                } else {
                    $errors[] = 'Failed to save uploaded file. Please try again.';
                }
            }
        }
    }

    if (empty($errors)) {
        try {
            $service = new AssignmentService($pdo);
            $result  = $service->createAndAssign([
                'title'       => $title,
                'category_id' => $categoryId,
                'ward_id'     => $wardId,
                'description' => $description,
                'address'     => $address,
                'latitude'    => $latitude,
                'longitude'   => $longitude,
            ], $citizenId);

            // Save attachment records
            foreach ($uploadedMeta as $meta) {
                $pdo->prepare(
                    "INSERT INTO complaint_attachments
                        (complaint_id, uploaded_by, uploaded_by_role, file_path, original_filename, mime_type, file_size, attachment_type)
                     VALUES (:cid, :uid, 'citizen', :path, :orig, :mime, :size, 'complaint_photo')"
                )->execute([
                    ':cid'  => $result['complaint_id'],
                    ':uid'  => $citizenId,
                    ':path' => $meta['path'],
                    ':orig' => $meta['original'],
                    ':mime' => $meta['mime'],
                    ':size' => $meta['size'],
                ]);
            }

            // Redirect to confirmation
            flash('success', 'Complaint ' . $result['complaint_code'] . ' submitted successfully!', 'success');
            redirectTo(APP_URL . '/citizen/complaint_details.php?id=' . $result['complaint_id'] . '&new=1');
        } catch (Throwable $e) {
            error_log('[submit_complaint] ' . $e->getMessage());
            $errors[] = 'An error occurred while submitting your complaint. Please try again.';
            // Clean up uploaded files if complaint creation failed
            foreach ($uploadedPaths as $p) {
                @unlink(UPLOAD_DIR . $p);
            }
        }
    }
}

$pageTitle   = 'Report a New Issue';
$breadcrumbs = [
    ['label' => 'Dashboard', 'href' => APP_URL . '/citizen/dashboard.php'],
    ['label' => 'Report New Issue'],
];
include __DIR__ . '/../includes/dash_header.php';
?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <strong><i class="bi bi-exclamation-triangle me-2"></i>Please correct the following:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header">
                <i class="bi bi-plus-circle me-2"></i>Submit a Civic Complaint
            </div>
            <div class="card-body p-4">
                <form method="post" enctype="multipart/form-data" novalidate>
                    <?= csrfField() ?>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Complaint Title <span class="required-star">*</span></label>
                            <input type="text" name="title" class="form-control" maxlength="255"
                                   value="<?= e($_POST['title'] ?? '') ?>"
                                   placeholder="e.g., Large pothole on Main Street near Bus Stop"
                                   required autofocus>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Category <span class="required-star">*</span></label>
                            <select name="category_id" class="form-select" required>
                                <option value="">— Select Category —</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= (int)$cat['id'] ?>"
                                        <?= (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                        <?= e($cat['category_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Ward / Area <span class="required-star">*</span></label>
                            <select name="ward_id" class="form-select" required>
                                <option value="">— Select Ward —</option>
                                <?php foreach ($wards as $w): ?>
                                    <?php
                                    $sel = (isset($_POST['ward_id']) && $_POST['ward_id'] == $w['id'])
                                           || (!isset($_POST['ward_id']) && $defaultWardId == $w['id']);
                                    ?>
                                    <option value="<?= (int)$w['id'] ?>" <?= $sel ? 'selected' : '' ?>>
                                        <?= e($w['ward_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Description <span class="required-star">*</span></label>
                            <textarea name="description" class="form-control" rows="5"
                                      maxlength="3000" required
                                      placeholder="Describe the issue in detail — what, where, and how severe it is."><?= e($_POST['description'] ?? '') ?></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Location / Address</label>
                            <input type="text" name="address" class="form-control" maxlength="500"
                                   value="<?= e($_POST['address'] ?? '') ?>"
                                   placeholder="Street name, landmark, house number…">
                        </div>

                        <!-- GPS -->
                        <div class="col-12">
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <button type="button" id="btnGetLocation" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-geo-alt me-1"></i>Use My GPS Location
                                </button>
                                <small id="gpsStatus" class="text-muted">GPS coordinates are optional.</small>
                            </div>
                            <input type="hidden" name="latitude"  id="latitude"  value="<?= e($_POST['latitude']  ?? '') ?>">
                            <input type="hidden" name="longitude" id="longitude" value="<?= e($_POST['longitude'] ?? '') ?>">
                        </div>

                        <!-- Photos -->
                        <div class="col-12">
                            <label class="form-label">Supporting Photos <small class="text-muted">(up to <?= MAX_PHOTOS ?>, JPG/PNG/WebP, max <?= MAX_UPLOAD_SIZE/1024/1024 ?>MB each)</small></label>
                            <input type="file" name="photos[]" id="photoInput" class="form-control"
                                   multiple accept="image/jpeg,image/png,image/gif,image/webp"
                                   data-preview="photoPreview">
                            <div id="photoPreview" class="d-flex flex-wrap mt-2"></div>
                        </div>

                        <div class="col-12 d-flex gap-3 flex-wrap">
                            <button type="submit" class="btn btn-primary px-5 fw-semibold">
                                <i class="bi bi-send me-2"></i>Submit Complaint
                            </button>
                            <a href="<?= APP_URL ?>/citizen/dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/dash_footer.php'; ?>
