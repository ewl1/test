<?php
// infusion-admin.php already ran _guard.php — here we only check the module permission
require_permission('downloads.admin');

$pdo = $GLOBALS['pdo'];
$message = null;
$messageType = 'success';

// Initialize settings object (needed in POST handler and display)
$settings = new \App\MiniCMS\ModuleSettings($pdo, 'downloads', [
    'max_file_size' => '52428800',
    'show_thumbnails' => '1',
]);

$allowed_extensions = ['zip', 'rar', '7z', 'tar', 'gz', 'pdf', 'exe', 'msi', 'dmg', 'pkg', 'deb', 'rpm', 'apk', 'iso'];

$upload_dir = downloads_upload_dir();
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
    file_put_contents($upload_dir . '.htaccess', "php_flag engine off\nOptions -Indexes\n");
}

$thumbs_dir = downloads_thumbs_dir();
if (!is_dir($thumbs_dir)) {
    mkdir($thumbs_dir, 0755, true);
}

$allowed_image_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

function downloads_admin_redirect(string $url): void
{
    if (!headers_sent()) {
        redirect($url);
    }

    echo '<script>window.location.href=' . json_encode($url, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ';</script>';
    echo '<noscript><meta http-equiv="refresh" content="0;url=' . e($url) . '"></noscript>';
    exit;
}

// Helper: applies thumbnail change and returns final filename string
function dl_apply_thumbnail(PDO $pdo, int $dl_id, ?string $thumb_name, string $current_thumb, string $thumbs_dir): string
{
    if ($thumb_name === null) {
        // No change
        return $current_thumb;
    }
    // Delete old thumbnail from disk (if any)
    if ($current_thumb !== '' && is_file($thumbs_dir . $current_thumb)) {
        unlink($thumbs_dir . $current_thumb);
    }
    return $thumb_name; // '' means cleared, 'filename' means new
}

// -------------------------------------------------------------------------
// POST actions
// -------------------------------------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');

    // --- Save settings ---
    if ($action === 'save_settings') {
        $max_size = (int)($_POST['downloads_max_file_size'] ?? 52428800);
        $show_thumbs = (isset($_POST['downloads_show_thumbnails']) && $_POST['downloads_show_thumbnails'] === '1') ? 1 : 0;

        if ($max_size < 1048576) { // Min 1MB
            $message = __('downloads.settings.max_file_size.min_error');
            $messageType = 'danger';
        } else {
            // Save to site_settings using ModuleSettings
            $settings->set('max_file_size', (string)$max_size);
            $settings->set('show_thumbnails', (string)$show_thumbs);

            audit_log(current_user()['id'], 'downloads_settings_update', 'downloads_settings', 0, [
                'max_size' => $max_size,
                'show_thumbs' => $show_thumbs,
            ]);
            flash('success', __('downloads.settings.success'));
            downloads_admin_redirect(public_path('administration/infusion-admin.php?folder=downloads'));
        }
    }

    // --- Save category ---
    if ($action === 'save_category') {
        $cat_id   = (int)($_POST['download_cat_id'] ?? 0);
        $cat_name = trim((string)($_POST['download_cat_name'] ?? ''));
        $cat_desc = trim((string)($_POST['download_cat_description'] ?? ''));

        if ($cat_name === '') {
            $message = __('downloads.categories.error.empty_name');
            $messageType = 'danger';
        } elseif ($cat_id > 0) {
            $pdo->prepare("UPDATE " . DB_DOWNLOAD_CATS . " SET download_cat_name = :name, download_cat_description = :desc WHERE download_cat_id = :id")
                ->execute([':name' => $cat_name, ':desc' => $cat_desc, ':id' => $cat_id]);
            audit_log(current_user()['id'], 'download_cat_update', 'download_cats', $cat_id, ['name' => $cat_name]);
            flash('success', __('downloads.categories.success.updated'));
            downloads_admin_redirect(public_path('administration/infusion-admin.php?folder=downloads'));
        } else {
            $stmt = $pdo->prepare("INSERT INTO " . DB_DOWNLOAD_CATS . " (download_cat_name, download_cat_description) VALUES (:name, :desc)");
            $stmt->execute([':name' => $cat_name, ':desc' => $cat_desc]);
            audit_log(current_user()['id'], 'download_cat_create', 'download_cats', (int)$pdo->lastInsertId(), ['name' => $cat_name]);
            flash('success', __('downloads.categories.success.created'));
            downloads_admin_redirect(public_path('administration/infusion-admin.php?folder=downloads'));
        }
    }

    // --- Delete category ---
    if ($action === 'delete_category') {
        $cat_id = (int)($_POST['download_cat_id'] ?? 0);
        if ($cat_id > 0) {
            $pdo->prepare("UPDATE " . DB_DOWNLOADS . " SET download_cat_id = 0 WHERE download_cat_id = :id")
                ->execute([':id' => $cat_id]);
            $pdo->prepare("DELETE FROM " . DB_DOWNLOAD_CATS . " WHERE download_cat_id = :id")
                ->execute([':id' => $cat_id]);
            audit_log(current_user()['id'], 'download_cat_delete', 'download_cats', $cat_id);
        }
        flash('success', __('downloads.categories.success.deleted'));
        downloads_admin_redirect(public_path('administration/infusion-admin.php?folder=downloads'));
    }

    // --- Save download ---
    if ($action === 'save_download') {
        $dl_id       = (int)($_POST['download_id'] ?? 0);
        $title       = trim((string)($_POST['download_title'] ?? ''));
        $desc        = trim((string)($_POST['download_description'] ?? ''));
        $cat_id      = (int)($_POST['download_cat_id'] ?? 0);
        $source_type = ($_POST['source_type'] ?? 'file') === 'url' ? 'url' : 'file';

        // --- Thumbnail upload (shared for both file and URL types) ---
        $thumb_name = null; // null = no change; '' = delete; 'filename' = new thumb
        $thumb_upload = $_FILES['download_thumbnail'] ?? null;
        if ($thumb_upload && isset($thumb_upload['error'])) {
            if ($thumb_upload['error'] === UPLOAD_ERR_OK) {
                $t_orig = (string)($thumb_upload['name'] ?? '');
                $t_ext  = strtolower(pathinfo($t_orig, PATHINFO_EXTENSION));
                if (!in_array($t_ext, $allowed_image_extensions, true)) {
                    $message = __('downloads.downloads.error.thumbnail.invalid_type');
                    $messageType = 'danger';
                } else {
                    $t_safe = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($t_orig, PATHINFO_FILENAME));
                    $t_safe = trim($t_safe, '_') ?: 'thumb';
                    $thumb_name = $t_safe . '_' . time() . '.' . $t_ext;
                    if (!move_uploaded_file($thumb_upload['tmp_name'], $thumbs_dir . $thumb_name)) {
                        $message = __('downloads.downloads.error.thumbnail.upload_failed');
                        $messageType = 'danger';
                        $thumb_name = null;
                    }
                }
            } elseif ($thumb_upload['error'] !== UPLOAD_ERR_NO_FILE) {
                $message = __('downloads.downloads.error.thumbnail.upload_failed');
                $messageType = 'danger';
            }
        }
        // "Delete thumbnail" checkbox
        if (isset($_POST['delete_thumbnail']) && $_POST['delete_thumbnail'] === '1' && $thumb_name === null) {
            $thumb_name = ''; // signal to clear
        }

        if ($title === '') {
            $message = __('downloads.downloads.error.empty_title');
            $messageType = 'danger';
        } elseif ($source_type === 'url') {
            // --- URL type ---
            $dl_url = trim((string)($_POST['download_url'] ?? ''));

            if ($dl_url === '' || !is_safe_output_url($dl_url, ['http', 'https'], false)) {
                $message = __('downloads.downloads.error.invalid_url');
                $messageType = 'danger';
            } else {
                // If switching from file → delete old file from disk
                $old_file = (string)($_POST['current_file'] ?? '');
                if ($old_file !== '' && is_file($upload_dir . $old_file)) {
                    unlink($upload_dir . $old_file);
                }

                $final_thumb = dl_apply_thumbnail($pdo, $dl_id, $thumb_name, $edit_dl['download_thumbnail'] ?? '', $thumbs_dir);
                if ($dl_id > 0) {
                    $pdo->prepare("UPDATE " . DB_DOWNLOADS . " SET download_title = :title, download_description = :desc, download_cat_id = :cat, download_url = :url, download_file = '', download_size = 0, download_thumbnail = :thumb WHERE download_id = :id")
                        ->execute([':title' => $title, ':desc' => $desc, ':cat' => $cat_id, ':url' => $dl_url, ':thumb' => $final_thumb, ':id' => $dl_id]);
                    audit_log(current_user()['id'], 'download_update', 'downloads', $dl_id, ['title' => $title, 'type' => 'url']);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO " . DB_DOWNLOADS . " (download_title, download_description, download_cat_id, download_url, download_file, download_size, download_thumbnail, download_datestamp, download_user) VALUES (:title, :desc, :cat, :url, '', 0, :thumb, :ts, :user)");
                    $stmt->execute([':title' => $title, ':desc' => $desc, ':cat' => $cat_id, ':url' => $dl_url, ':thumb' => $final_thumb, ':ts' => time(), ':user' => (int)current_user()['id']]);
                    audit_log(current_user()['id'], 'download_create', 'downloads', (int)$pdo->lastInsertId(), ['title' => $title, 'type' => 'url']);
                }
                flash('success', $dl_id > 0 ? __('downloads.downloads.success.updated') : __('downloads.downloads.success.created'));
                downloads_admin_redirect(public_path('administration/infusion-admin.php?folder=downloads'));
            }
        } else {
            // --- File upload type ---
            $file_name = (string)($_POST['current_file'] ?? '');
            $file_size = (int)($_POST['current_size'] ?? 0);

            $upload = $_FILES['download_file'] ?? null;
            if ($upload && isset($upload['error']) && $upload['error'] === UPLOAD_ERR_OK) {
                $orig_name = (string)($upload['name'] ?? '');
                $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

                if (!in_array($ext, $allowed_extensions, true)) {
                    $message = __('downloads.downloads.error.invalid_type', ['types' => implode(', ', $allowed_extensions)]);
                    $messageType = 'danger';
                } else {
                    $safe_name = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($orig_name, PATHINFO_FILENAME));
                    $safe_name = trim($safe_name, '_') ?: 'file';
                    $new_file_name = $safe_name . '_' . time() . '.' . $ext;

                    if (!move_uploaded_file($upload['tmp_name'], $upload_dir . $new_file_name)) {
                        $message = __('downloads.downloads.error.upload_failed');
                        $messageType = 'danger';
                    } else {
                        // Remove old file if replacing
                        if ($file_name !== '' && $file_name !== $new_file_name && is_file($upload_dir . $file_name)) {
                            unlink($upload_dir . $file_name);
                        }
                        $file_name = $new_file_name;
                        $file_size = (int)$upload['size'];
                    }
                }
            } elseif ($dl_id === 0 && (empty($upload['error']) || $upload['error'] === UPLOAD_ERR_NO_FILE)) {
                $message = __('downloads.downloads.error.no_file');
                $messageType = 'danger';
            }

            if ($message === null) {
                $final_thumb = dl_apply_thumbnail($pdo, $dl_id, $thumb_name, $edit_dl['download_thumbnail'] ?? '', $thumbs_dir);
                if ($dl_id > 0) {
                    $pdo->prepare("UPDATE " . DB_DOWNLOADS . " SET download_title = :title, download_description = :desc, download_cat_id = :cat, download_file = :file, download_size = :size, download_url = '', download_thumbnail = :thumb WHERE download_id = :id")
                        ->execute([':title' => $title, ':desc' => $desc, ':cat' => $cat_id, ':file' => $file_name, ':size' => $file_size, ':thumb' => $final_thumb, ':id' => $dl_id]);
                    audit_log(current_user()['id'], 'download_update', 'downloads', $dl_id, ['title' => $title, 'type' => 'file']);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO " . DB_DOWNLOADS . " (download_title, download_description, download_cat_id, download_file, download_size, download_url, download_thumbnail, download_datestamp, download_user) VALUES (:title, :desc, :cat, :file, :size, '', :thumb, :ts, :user)");
                    $stmt->execute([':title' => $title, ':desc' => $desc, ':cat' => $cat_id, ':file' => $file_name, ':size' => $file_size, ':thumb' => $final_thumb, ':ts' => time(), ':user' => (int)current_user()['id']]);
                    audit_log(current_user()['id'], 'download_create', 'downloads', (int)$pdo->lastInsertId(), ['title' => $title, 'type' => 'file']);
                }
                flash('success', $dl_id > 0 ? __('downloads.downloads.success.updated') : __('downloads.downloads.success.created'));
                downloads_admin_redirect(public_path('administration/infusion-admin.php?folder=downloads'));
            }
        }
    }

    // --- Delete download ---
    if ($action === 'delete_download') {
        $dl_id = (int)($_POST['download_id'] ?? 0);
        if ($dl_id > 0) {
            $stmt = $pdo->prepare("SELECT download_file, download_thumbnail FROM " . DB_DOWNLOADS . " WHERE download_id = :id");
            $stmt->execute([':id' => $dl_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                if ($row['download_file'] !== '' && is_file($upload_dir . $row['download_file'])) {
                    unlink($upload_dir . $row['download_file']);
                }
                if (!empty($row['download_thumbnail']) && is_file($thumbs_dir . $row['download_thumbnail'])) {
                    unlink($thumbs_dir . $row['download_thumbnail']);
                }
            }
            $pdo->prepare("DELETE FROM " . DB_DOWNLOADS . " WHERE download_id = :id")
                ->execute([':id' => $dl_id]);
            audit_log(current_user()['id'], 'download_delete', 'downloads', $dl_id);
        }
        flash('success', __('downloads.downloads.success.deleted'));
        downloads_admin_redirect(public_path('administration/infusion-admin.php?folder=downloads'));
    }
}

// -------------------------------------------------------------------------
// Load data for display
// -------------------------------------------------------------------------
$success = flash('success');

$edit_cat = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit_cat' && isset($_GET['cat_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM " . DB_DOWNLOAD_CATS . " WHERE download_cat_id = :id");
    $stmt->execute([':id' => (int)$_GET['cat_id']]);
    $edit_cat = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

$edit_dl = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit_dl' && isset($_GET['dl_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM " . DB_DOWNLOADS . " WHERE download_id = :id");
    $stmt->execute([':id' => (int)$_GET['dl_id']]);
    $edit_dl = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

// Determine current source type when editing
$edit_source_type = 'file';
if ($edit_dl && !empty($edit_dl['download_url'])) {
    $edit_source_type = 'url';
}

$all_cats = $pdo->query("SELECT * FROM " . DB_DOWNLOAD_CATS . " ORDER BY download_cat_name ASC")->fetchAll(PDO::FETCH_ASSOC);

$all_downloads = $pdo->query("
    SELECT d.download_id, d.download_title, d.download_file, d.download_url,
           d.download_thumbnail, d.download_size, d.download_count, dc.download_cat_name
    FROM " . DB_DOWNLOADS . " d
    LEFT JOIN " . DB_DOWNLOAD_CATS . " dc ON dc.download_cat_id = d.download_cat_id
    ORDER BY d.download_datestamp DESC
")->fetchAll(PDO::FETCH_ASSOC);

$base_url = public_path('administration/infusion-admin.php?folder=downloads');
?>

<link rel="stylesheet" href="<?= asset_path('infusions/downloads/assets/css/downloads.css') ?>">

<div class="downloads-admin-container">
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show downloads-alert" role="alert">
            <?= e($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($message): ?>
        <div class="alert alert-<?= e($messageType) ?> downloads-alert" role="alert"><?= e($message) ?></div>
    <?php endif; ?>

    <!-- Nav Tabs -->
    <ul class="nav nav-tabs downloads-nav-tabs mb-4" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-categories-btn" data-bs-toggle="tab" data-bs-target="#tab-categories"
                    type="button" role="tab" aria-controls="tab-categories" aria-selected="true">
                <i class="fa-solid fa-th-list me-2"></i><?= __('downloads.admin.tabs.categories') ?>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-downloads-btn" data-bs-toggle="tab" data-bs-target="#tab-downloads"
                    type="button" role="tab" aria-controls="tab-downloads" aria-selected="false">
                <i class="fa-solid fa-cloud-upload-alt me-2"></i><?= __('downloads.admin.tabs.downloads') ?>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-settings-btn" data-bs-toggle="tab" data-bs-target="#tab-settings"
                    type="button" role="tab" aria-controls="tab-settings" aria-selected="false">
                <i class="fa-solid fa-cog me-2"></i><?= __('downloads.admin.tabs.settings') ?>
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content downloads-tab-content">
        <!-- TAB 1: Categories -->
        <div class="tab-pane fade show active" id="tab-categories" role="tabpanel" aria-labelledby="tab-categories-btn">
            <div class="row g-4 mb-5">
                <div class="col-md-7">
                    <h2 class="h5 mb-3"><?= __('downloads.categories.title') ?></h2>
                    <?php if (empty($all_cats)): ?>
                        <p class="text-muted"><?= __('downloads.categories.empty') ?></p>
                    <?php else: ?>
                        <div class="downloads-table-wrapper table-responsive">
                            <table class="table table-sm table-striped downloads-table">
                                <thead class="table-light">
                                    <tr><th><?= __('downloads.categories.form.title') ?></th><th><?= __('downloads.categories.form.description') ?></th><th></th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($all_cats as $cat): ?>
                                        <tr>
                                            <td><?= e($cat['download_cat_name']) ?></td>
                                            <td><?= e($cat['download_cat_description'] ?? '') ?></td>
                                            <td class="text-end text-nowrap">
                                                <a href="<?= e($base_url . '&action=edit_cat&cat_id=' . (int)$cat['download_cat_id']) ?>"
                                                   class="btn btn-sm btn-outline-primary"><?= __('downloads.categories.actions.edit') ?></a>
                                                <form method="post" action="<?= e($base_url) ?>" class="d-inline"
                                                      onsubmit="return confirm('<?= __('downloads.categories.confirm.delete') ?>');">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="delete_category">
                                                    <input type="hidden" name="download_cat_id" value="<?= (int)$cat['download_cat_id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"><?= __('downloads.categories.actions.delete') ?></button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-md-5">
                    <div class="downloads-form-section">
                        <h2 class="h5 mb-3"><?= $edit_cat ? __('downloads.categories.form.edit') : __('downloads.categories.form.add') ?></h2>
                        <form method="post" action="<?= e($base_url) ?>" class="downloads-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="save_category">
                            <?php if ($edit_cat): ?>
                                <input type="hidden" name="download_cat_id" value="<?= (int)$edit_cat['download_cat_id'] ?>">
                            <?php endif; ?>
                            <div class="mb-3">
                                <label class="form-label"><?= __('downloads.categories.form.title') ?></label>
                                <input type="text" class="form-control" name="download_cat_name"
                                       value="<?= e($edit_cat['download_cat_name'] ?? '') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><?= __('downloads.categories.form.description') ?></label>
                                <textarea class="form-control" name="download_cat_description" rows="2"><?= e($edit_cat['download_cat_description'] ?? '') ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary"><?= __('downloads.categories.form.save') ?></button>
                            <?php if ($edit_cat): ?>
                                <a href="<?= e($base_url) ?>" class="btn btn-secondary"><?= __('downloads.categories.form.cancel') ?></a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: Downloads -->
        <div class="tab-pane fade" id="tab-downloads" role="tabpanel" aria-labelledby="tab-downloads-btn">
            <div class="row g-4">
                <div class="col-md-7">
                    <h2 class="h5 mb-3"><?= __('downloads.downloads.title') ?></h2>
                    <?php if (empty($all_downloads)): ?>
                        <p class="text-muted"><?= __('downloads.downloads.empty') ?></p>
                    <?php else: ?>
                        <div class="downloads-table-wrapper table-responsive">
                            <table class="table table-sm table-striped align-middle downloads-table">
                                <thead class="table-light">
                                    <tr><th></th><th><?= __('downloads.downloads.form.title') ?></th><th><?= __('downloads.downloads.table.type') ?></th><th><?= __('downloads.downloads.table.category') ?></th><th><?= __('downloads.downloads.table.size') ?></th><th><?= __('downloads.downloads.table.downloads') ?></th><th></th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($all_downloads as $dl): ?>
                                        <?php $is_url = !empty($dl['download_url']); ?>
                                        <tr>
                                            <td style="width:52px">
                                                <?php if (!empty($dl['download_thumbnail'])): ?>
                                                    <img src="<?= e(downloads_thumb_url($dl['download_thumbnail'])) ?>"
                                                         alt="" style="width:48px;height:36px;object-fit:cover;border-radius:3px;">
                                                <?php endif; ?>
                                            </td>
                                            <td><?= e($dl['download_title']) ?></td>
                                            <td>
                                                <?php if ($is_url): ?>
                                                    <span class="badge bg-info text-dark" title="<?= e($dl['download_url']) ?>">
                                                        <i class="fa-solid fa-link"></i> <?= __('downloads.downloads.table.type.url') ?>
                                                    </span>
                                                <?php else: ?>
                                                    <?php $icon = downloads_file_icon(strtolower(pathinfo((string)$dl['download_file'], PATHINFO_EXTENSION))); ?>
                                                    <span class="<?= e($icon['color']) ?>" title="<?= e(strtoupper(pathinfo((string)$dl['download_file'], PATHINFO_EXTENSION))) ?>">
                                                        <i class="<?= e($icon['icon']) ?>"></i>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= e($dl['download_cat_name'] ?? '—') ?></td>
                                            <td class="text-nowrap"><?= $is_url ? '—' : e(format_bytes_human((int)$dl['download_size'])) ?></td>
                                            <td><?= (int)$dl['download_count'] ?></td>
                                            <td class="text-end text-nowrap">
                                                <a href="<?= e($base_url . '&action=edit_dl&dl_id=' . (int)$dl['download_id']) ?>"
                                                   class="btn btn-sm btn-outline-primary"><?= __('downloads.downloads.actions.edit') ?></a>
                                                <form method="post" action="<?= e($base_url) ?>" class="d-inline"
                                                      onsubmit="return confirm('<?= $is_url ? __('downloads.downloads.confirm.delete.url') : __('downloads.downloads.confirm.delete.file') ?>');">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="delete_download">
                                                    <input type="hidden" name="download_id" value="<?= (int)$dl['download_id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"><?= __('downloads.downloads.actions.delete') ?></button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-md-5">
                    <div class="downloads-form-section">
                        <h2 class="h5 mb-3"><?= $edit_dl ? __('downloads.downloads.form.edit') : __('downloads.downloads.form.add') ?></h2>
                        <form method="post" action="<?= e($base_url) ?>" enctype="multipart/form-data" id="dl-form" class="downloads-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="save_download">
                            <?php if ($edit_dl): ?>
                                <input type="hidden" name="download_id" value="<?= (int)$edit_dl['download_id'] ?>">
                                <input type="hidden" name="current_file" value="<?= e($edit_dl['download_file'] ?? '') ?>">
                                <input type="hidden" name="current_size" value="<?= (int)($edit_dl['download_size'] ?? 0) ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label class="form-label"><?= __('downloads.downloads.form.title') ?></label>
                                <input type="text" class="form-control" name="download_title"
                                       value="<?= e($edit_dl['download_title'] ?? '') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><?= __('downloads.downloads.form.category') ?></label>
                                <select class="form-select" name="download_cat_id">
                                    <option value="0"><?= __('downloads.downloads.form.no_category') ?></option>
                                    <?php foreach ($all_cats as $cat): ?>
                                        <option value="<?= (int)$cat['download_cat_id'] ?>"
                                            <?= isset($edit_dl['download_cat_id']) && (int)$edit_dl['download_cat_id'] === (int)$cat['download_cat_id'] ? 'selected' : '' ?>>
                                            <?= e($cat['download_cat_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><?= __('downloads.downloads.form.description') ?></label>
                                <textarea class="form-control" name="download_description" rows="2"><?= e($edit_dl['download_description'] ?? '') ?></textarea>
                            </div>

                            <!-- Source type toggle -->
                            <div class="mb-3">
                                <label class="form-label"><?= __('downloads.downloads.form.source') ?></label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="source_type" id="src-file" value="file"
                                           <?= $edit_source_type === 'file' ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-secondary" for="src-file">
                                        <i class="fa fa-upload"></i> <?= __('downloads.downloads.form.source.file') ?>
                                    </label>
                                    <input type="radio" class="btn-check" name="source_type" id="src-url" value="url"
                                           <?= $edit_source_type === 'url' ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-secondary" for="src-url">
                                        <i class="fa fa-link"></i> <?= __('downloads.downloads.form.source.url') ?>
                                    </label>
                                </div>
                            </div>

                            <!-- File upload panel -->
                            <div id="panel-file" class="mb-3" <?= $edit_source_type === 'url' ? 'style="display:none"' : '' ?>>
                                <label class="form-label">
                                    <?= __('downloads.downloads.form.file') ?>
                                    <?php if ($edit_dl): ?>
                                        (<?= __('downloads.downloads.form.file.keep_empty') ?>)
                                    <?php endif; ?>
                                </label>
                                <input type="file" class="form-control" name="download_file"
                                       id="file-input" <?= ($edit_dl || $edit_source_type === 'url') ? '' : 'required' ?>>
                                <?php if ($edit_dl && !empty($edit_dl['download_file']) && $edit_source_type === 'file'): ?>
                                    <div class="form-text">
                                        <?= __('downloads.downloads.form.file.existing', [
                                            'file' => e($edit_dl['download_file']),
                                            'size' => e(format_bytes_human((int)($edit_dl['download_size'] ?? 0))),
                                        ]) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="form-text"><?= __('downloads.downloads.form.file.allowed', ['types' => e(implode(', ', $allowed_extensions))]) ?></div>
                            </div>

                            <!-- URL panel -->
                            <div id="panel-url" class="mb-3" <?= $edit_source_type === 'file' ? 'style="display:none"' : '' ?>>
                                <label class="form-label"><?= __('downloads.downloads.form.url') ?></label>
                                <input type="url" class="form-control" name="download_url"
                                       id="url-input"
                                       placeholder="<?= __('downloads.downloads.form.url.placeholder') ?>"
                                       value="<?= e($edit_dl['download_url'] ?? '') ?>">
                                <div class="form-text"><?= __('downloads.downloads.form.url.info') ?></div>
                            </div>

                            <!-- Thumbnail -->
                            <div class="mb-3">
                                <label class="form-label"><?= __('downloads.downloads.form.thumbnail') ?></label>
                                <?php if ($edit_dl && !empty($edit_dl['download_thumbnail'])): ?>
                                    <div class="mb-2">
                                        <img src="<?= e(downloads_thumb_url($edit_dl['download_thumbnail'])) ?>"
                                             alt="thumbnail" style="max-height:80px; border-radius:4px;">
                                        <div class="form-check mt-1">
                                            <input class="form-check-input" type="checkbox" name="delete_thumbnail" value="1" id="del-thumb">
                                            <label class="form-check-label text-danger" for="del-thumb"><?= __('downloads.downloads.form.thumbnail.delete') ?></label>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <input type="file" class="form-control" name="download_thumbnail" accept="image/*">
                                <div class="form-text"><?= __('downloads.downloads.form.thumbnail.size') ?></div>
                            </div>

                            <button type="submit" class="btn btn-primary"><?= __('downloads.downloads.form.save') ?></button>
                            <?php if ($edit_dl): ?>
                                <a href="<?= e($base_url) ?>" class="btn btn-secondary"><?= __('downloads.downloads.form.cancel') ?></a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 3: Settings -->
        <div class="tab-pane fade" id="tab-settings" role="tabpanel" aria-labelledby="tab-settings-btn">
            <div class="row">
                <div class="col-md-6">
                    <div class="downloads-form-section">
                        <h2 class="h5 mb-4"><?= __('downloads.settings.title') ?></h2>
                        <form method="post" action="<?= e($base_url) ?>" class="downloads-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="save_settings">

                            <div class="mb-3">
                                <label class="form-label"><?= __('downloads.settings.max_file_size') ?></label>
                                <input type="number" class="form-control" name="downloads_max_file_size"
                                       value="<?= (int)$settings->get('max_file_size', '52428800') ?>" min="1048576" step="1048576" required>
                                <div class="form-text">
                                    <?= __('downloads.settings.max_file_size.info', ['size' => format_bytes_human((int)$settings->get('max_file_size', '52428800'))]) ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="downloads_show_thumbnails" id="show-thumbs"
                                           value="1" <?= (int)$settings->get('show_thumbnails', '1') === 1 ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="show-thumbs">
                                        <?= __('downloads.settings.show_thumbnails') ?>
                                    </label>
                                </div>
                                <div class="form-text"><?= __('downloads.settings.show_thumbnails.info') ?></div>
                            </div>

                            <button type="submit" class="btn btn-primary"><?= __('downloads.settings.save') ?></button>
                        </form>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h5 class="card-title"><?= __('downloads.settings.info.title') ?></h5>
                            <p class="card-text">
                                <strong><?= __('downloads.settings.max_file_size') ?>:</strong>
                                <?= __('downloads.settings.info.max_file_size') ?>
                            </p>
                            <p class="card-text">
                                <strong><?= __('downloads.settings.show_thumbnails') ?>:</strong>
                                <?= __('downloads.settings.info.thumbnails') ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var radios = document.querySelectorAll('input[name="source_type"]');
    var panelFile = document.getElementById('panel-file');
    var panelUrl  = document.getElementById('panel-url');
    var fileInput = document.getElementById('file-input');
    var urlInput  = document.getElementById('url-input');

    if (!panelFile || !panelUrl || !fileInput || !urlInput) {
        return; // Not on downloads tab
    }

    function toggle() {
        var isUrl = document.getElementById('src-url').checked;
        panelFile.style.display = isUrl ? 'none' : '';
        panelUrl.style.display  = isUrl ? '' : 'none';
        fileInput.required = !isUrl && !fileInput.dataset.existing;
        urlInput.required  = isUrl;
    }

    <?php if (!$edit_dl): ?>
    // New entry: file is required by default
    fileInput.dataset.existing = '';
    <?php else: ?>
    // Editing: file not required (may keep current)
    fileInput.dataset.existing = '1';
    <?php endif; ?>

    radios.forEach(function (r) { r.addEventListener('change', toggle); });
    toggle();
}());
</script>

<script>
    // Localization strings for JavaScript
    window.downloadsTranslations = {
        fileSelected: '<?= __('downloads.js.file_selected') ?>',
        fileSize: {
            bytes: '<?= __('downloads.js.file_size_bytes') ?>',
            kb: '<?= __('downloads.js.file_size_kb') ?>',
            mb: '<?= __('downloads.js.file_size_mb') ?>',
            gb: '<?= __('downloads.js.file_size_gb') ?>'
        }
    };
</script>
<script src="<?= asset_path('infusions/downloads/assets/js/downloads.js') ?>"></script>

