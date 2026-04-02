<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Handle file/URL dispatch before any output (headers must be clean)
if (isset($_GET['action']) && $_GET['action'] === 'download' && isset($_GET['id'])) {
    require_permission('downloads.view');

    $pdo = $GLOBALS['pdo'];
    $download_id = (int)$_GET['id'];

    $stmt = $pdo->prepare("SELECT * FROM " . DB_DOWNLOADS . " WHERE download_id = :id");
    $stmt->execute([':id' => $download_id]);
    $download = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$download) {
        abort_http(404);
    }

    // Increment counter regardless of source type
    $pdo->prepare("UPDATE " . DB_DOWNLOADS . " SET download_count = download_count + 1 WHERE download_id = :id")
        ->execute([':id' => $download_id]);

    // --- External URL: redirect ---
    if (!empty($download['download_url'])) {
        $safe_url = escape_url((string)$download['download_url']);
        header('Location: ' . $safe_url);
        exit;
    }

    // --- Local file: serve ---
    $file_path = downloads_upload_dir() . $download['download_file'];

    if ($download['download_file'] === '' || !file_exists($file_path)) {
        abort_http(404);
    }

    $mime = (function_exists('mime_content_type') ? mime_content_type($file_path) : null) ?: 'application/octet-stream';
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . basename($download['download_file']) . '"');
    header('Content-Length: ' . filesize($file_path));
    header('Cache-Control: no-store');
    readfile($file_path);
    exit;
}

require_permission('downloads.view');

$pdo = $GLOBALS['pdo'];
$user = current_user();
$detail_id = (int)($_GET['view'] ?? 0);
$commentError = null;
$commentDraft = '';

// Active tab: 'all' or 'mine'
$tab = ($user && isset($_GET['tab']) && $_GET['tab'] === 'mine') ? 'mine' : 'all';

// Active category filter (if set and valid)
$filter_cat_id = null;
if (isset($_GET['category'])) {
    $filter_cat_id = (int)$_GET['category'];
    if ($filter_cat_id < 0) {
        $filter_cat_id = null;
    }
}

// Fetch categories
$categories = $pdo->query(
    "SELECT * FROM " . DB_DOWNLOAD_CATS . " ORDER BY download_cat_name ASC"
)->fetchAll(PDO::FETCH_ASSOC);

// Fetch all downloads
$downloads_raw = $pdo->query("
    SELECT d.*, u.username AS uploader_name
    FROM " . DB_DOWNLOADS . " d
    LEFT JOIN users u ON u.id = d.download_user
    ORDER BY d.download_datestamp DESC
")->fetchAll(PDO::FETCH_ASSOC);

$downloadDetail = null;
if ($detail_id > 0) {
    $detailStmt = $pdo->prepare("
        SELECT d.*, u.username AS uploader_name
        FROM " . DB_DOWNLOADS . " d
        LEFT JOIN users u ON u.id = d.download_user
        WHERE d.download_id = :id
        LIMIT 1
    ");
    $detailStmt->execute([':id' => $detail_id]);
    $downloadDetail = $detailStmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (!$user) {
        abort_http(401, 'Prisijungimas reikalingas.');
    }

    $commentAction = (string)($_POST['comment_action'] ?? '');
    if ($commentAction === 'create') {
        $commentDraft = (string)($_POST['comment'] ?? '');
        [$ok, $message, $commentId] = create_content_comment('download', (int)($_POST['content_id'] ?? 0), (int)$user['id'], $commentDraft);
        if ($ok) {
            flash('content_comment_success', $message);
            redirect(public_path('downloads.php?view=' . (int)($_POST['content_id'] ?? 0) . '#content-comment-' . (int)$commentId));
        }
        $commentError = $message;
    } elseif ($commentAction === 'delete') {
        [$ok, $message, $meta] = delete_content_comment((int)($_POST['comment_id'] ?? 0), $user);
        if ($ok) {
            flash('content_comment_success', $message);
            redirect(public_path('downloads.php?view=' . (int)($meta['content_id'] ?? 0)));
        }
        $commentError = $message;
    }
}

// Group by category
$by_cat = [];
foreach ($downloads_raw as $dl) {
    $by_cat[(int)$dl['download_cat_id']][] = $dl;
}

// "Mano įkelti" — current user's uploads
$my_downloads = [];
if ($user) {
    foreach ($downloads_raw as $dl) {
        if ((int)$dl['download_user'] === (int)$user['id']) {
            $my_downloads[] = $dl;
        }
    }
}

// Calculate category counts
$cat_counts = ['total' => count($downloads_raw)];
foreach ($categories as $cat) {
    $cat_id = (int)$cat['download_cat_id'];
    $cat_counts[$cat_id] = count($by_cat[$cat_id] ?? []);
}
if (isset($by_cat[0])) {
    $cat_counts[0] = count($by_cat[0]);
}

include THEMES . setting('current_theme', CURRENT_THEME) . '/header.php';
?>
<div class="container my-4">
    <?php if ($downloadDetail): ?>
        <?php
        $successMessage = flash('content_comment_success');
        $downloadCommentCount = content_comments_count('download', (int)$downloadDetail['download_id']);
        $downloadCommentPage = max(1, (int)($_GET['page'] ?? 1));
        $downloadCommentPager = paginate($downloadCommentCount, content_comments_per_page_setting(), $downloadCommentPage);
        $downloadComments = fetch_content_comments('download', (int)$downloadDetail['download_id'], $downloadCommentPager['per_page'], $downloadCommentPager['offset']);
        $downloadActionUrl = public_path('downloads.php?action=download&id=' . (int)$downloadDetail['download_id']);
        ?>
        <?php if ($successMessage): ?><div class="alert alert-success"><?= e($successMessage) ?></div><?php endif; ?>
        <?php if ($commentError): ?><div class="alert alert-danger"><?= e($commentError) ?></div><?php endif; ?>

        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <div>
                        <h1 class="h3 mb-2"><?= e($downloadDetail['download_title']) ?></h1>
                        <div class="small text-secondary mb-3">
                            Ikelta: <?= e(date('Y-m-d', (int)$downloadDetail['download_datestamp'])) ?>
                            · Ikeles: <?= e($downloadDetail['uploader_name'] ?? '—') ?>
                            · Atsisiusta: <?= (int)$downloadDetail['download_count'] ?>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a class="btn btn-primary" href="<?= e($downloadActionUrl) ?>">Atsisiusti</a>
                        <a class="btn btn-outline-secondary" href="<?= public_path('downloads.php') ?>">Visi siuntiniai</a>
                    </div>
                </div>
                <?php if (!empty($downloadDetail['download_description'])): ?>
                    <div class="mt-3"><?= nl2br(e((string)$downloadDetail['download_description'])) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Komentarai (<?= (int)$downloadCommentCount ?>)</div>
            <div class="card-body">
                <?php if (!$user): ?>
                    <div class="alert alert-info">Komentuoti gali tik prisijunge nariai.</div>
                <?php else: ?>
                    <form method="post" class="mb-4">
                        <?= csrf_field() ?>
                        <input type="hidden" name="comment_action" value="create">
                        <input type="hidden" name="content_id" value="<?= (int)$downloadDetail['download_id'] ?>">
                        <div class="mb-3">
                            <label class="form-label" for="download-comment">Komentaras</label>
                            <textarea class="form-control" id="download-comment" name="comment" rows="5" maxlength="3000" required><?= e($commentDraft) ?></textarea>
                        </div>
                        <button class="btn btn-primary">Paskelbti komentara</button>
                    </form>
                <?php endif; ?>

                <div class="profile-comments-list">
                    <?php if (!$downloadComments): ?>
                        <div class="text-secondary">Komentaru dar nera.</div>
                    <?php else: ?>
                        <?php foreach ($downloadComments as $comment): ?>
                            <article class="profile-comment-item" id="content-comment-<?= (int)$comment['id'] ?>">
                                <div class="d-flex align-items-start gap-3">
                                    <a href="<?= user_profile_url((int)$comment['author_user_id']) ?>" class="text-decoration-none">
                                        <img src="<?= escape_url(user_avatar_url(['avatar' => $comment['author_avatar'] ?? null, 'email' => $comment['author_email'] ?? null])) ?>" alt="" class="member-panel-avatar">
                                    </a>
                                    <div class="min-w-0 flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                            <div>
                                                <a class="fw-semibold text-decoration-none" href="<?= user_profile_url((int)$comment['author_user_id']) ?>"><?= e($comment['author_username'] ?? __('member.none')) ?></a>
                                                <div class="small text-secondary"><?= e(format_dt($comment['created_at'])) ?></div>
                                            </div>
                                            <?php if (can_manage_content_comment($comment, $user)): ?>
                                                <form method="post">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="comment_action" value="delete">
                                                    <input type="hidden" name="comment_id" value="<?= (int)$comment['id'] ?>">
                                                    <button class="btn btn-sm btn-outline-danger" type="submit">Trinti</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                        <div class="profile-comment-body mt-2"><?= content_comment_render_body($comment['content']) ?></div>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php if (($downloadCommentPager['pages'] ?? 0) > 1): ?>
                    <div class="mt-4">
                        <?= render_pagination(public_path('downloads.php?view=' . (int)$downloadDetail['download_id']), $downloadCommentPager) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><?= __('downloads.frontend.page.title') ?></h1>
    </div>

    <?php if ($user): ?>
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'all' ? 'active' : '' ?>"
               href="<?= e(public_path('downloads.php')) ?>">
                <?= __('downloads.frontend.all_downloads') ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'mine' ? 'active' : '' ?>"
               href="<?= e(public_path('downloads.php?tab=mine')) ?>">
                <?= __('downloads.frontend.my_downloads') ?>
                <?php if ($my_downloads): ?>
                    <span class="badge bg-secondary"><?= count($my_downloads) ?></span>
                <?php endif; ?>
            </a>
        </li>
    </ul>
    <?php endif; ?>

    <?php if ($tab === 'mine' && $user): ?>
        <?php if (empty($my_downloads)): ?>
            <div class="alert alert-info"><?= __('downloads.frontend.no_files_uploaded') ?></div>
        <?php else: ?>
            <?= render_downloads_table($my_downloads) ?>
        <?php endif; ?>

    <?php else: ?>
        <?php if (empty($categories)): ?>
            <div class="alert alert-info"><?= __('downloads.frontend.no_categories') ?></div>
        <?php else: ?>
            <!-- Category Filter Pills -->
            <div class="mb-4">
                <h5 class="mb-2"><?= __('downloads.frontend.filter.label') ?></h5>
                <div class="btn-group flex-wrap gap-2" role="group">
                    <a href="<?= e(public_path('downloads.php')) ?>"
                       class="btn btn-sm <?= $filter_cat_id === null ? 'btn-primary' : 'btn-outline-primary' ?>">
                        <?= __('downloads.frontend.filter.all') ?>
                        <span class="badge bg-<?= $filter_cat_id === null ? 'light text-dark' : 'secondary' ?>">
                            <?= (int)($cat_counts['total'] ?? 0) ?>
                        </span>
                    </a>
                    <?php foreach ($categories as $cat): ?>
                        <?php $cat_id = (int)$cat['download_cat_id']; ?>
                        <a href="<?= e(public_path('downloads.php?category=' . $cat_id)) ?>"
                           class="btn btn-sm <?= $filter_cat_id === $cat_id ? 'btn-primary' : 'btn-outline-primary' ?>">
                            <?= e($cat['download_cat_name']) ?>
                            <span class="badge bg-<?= $filter_cat_id === $cat_id ? 'light text-dark' : 'secondary' ?>">
                                <?= (int)($cat_counts[$cat_id] ?? 0) ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                    <?php if (isset($by_cat[0]) && (int)($cat_counts[0] ?? 0) > 0): ?>
                        <a href="<?= e(public_path('downloads.php?category=0')) ?>"
                           class="btn btn-sm <?= $filter_cat_id === 0 ? 'btn-primary' : 'btn-outline-primary' ?>">
                            <?= __('downloads.frontend.filter.uncategorized') ?>
                            <span class="badge bg-<?= $filter_cat_id === 0 ? 'light text-dark' : 'secondary' ?>">
                                <?= (int)($cat_counts[0] ?? 0) ?>
                            </span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Downloads Display -->
            <?php if ($filter_cat_id !== null): ?>
                <!-- Filtered: show only selected category -->
                <?php if ($filter_cat_id === 0): ?>
                    <!-- "Be kategorijos" filter -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h2 class="h5 mb-0 text-muted"><?= __('downloads.frontend.filter.uncategorized') ?></h2>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($by_cat[0])): ?>
                                <p class="text-muted fst-italic px-3 py-3 mb-0"><?= __('downloads.frontend.filter.empty') ?></p>
                            <?php else: ?>
                                <?= render_downloads_table($by_cat[0]) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Regular category filter -->
                    <?php $cat = array_values(array_filter($categories, fn($c) => (int)$c['download_cat_id'] === $filter_cat_id))[0] ?? null; ?>
                    <?php if ($cat): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h2 class="h5 mb-0"><?= e($cat['download_cat_name']) ?></h2>
                            </div>
                            <div class="card-body p-0">
                                <?php if (!empty($cat['download_cat_description'])): ?>
                                    <p class="text-muted px-3 pt-3 mb-0"><?= e($cat['download_cat_description']) ?></p>
                                <?php endif; ?>

                                <?php if (empty($by_cat[$filter_cat_id])): ?>
                                    <p class="text-muted fst-italic px-3 py-3 mb-0"><?= __('downloads.frontend.filter.empty') ?></p>
                                <?php else: ?>
                                    <?= render_downloads_table($by_cat[$filter_cat_id]) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            <?php else: ?>
                <!-- Show all categories -->
                <?php foreach ($categories as $cat): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h2 class="h5 mb-0"><?= e($cat['download_cat_name']) ?></h2>
                        </div>
                        <div class="card-body p-0">
                            <?php if (!empty($cat['download_cat_description'])): ?>
                                <p class="text-muted px-3 pt-3 mb-0"><?= e($cat['download_cat_description']) ?></p>
                            <?php endif; ?>

                            <?php if (empty($by_cat[(int)$cat['download_cat_id']])): ?>
                                <p class="text-muted fst-italic px-3 py-3 mb-0"><?= __('downloads.frontend.filter.empty') ?></p>
                            <?php else: ?>
                                <?= render_downloads_table($by_cat[(int)$cat['download_cat_id']]) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (!empty($by_cat[0])): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h2 class="h5 mb-0 text-muted"><?= __('downloads.frontend.filter.uncategorized') ?></h2>
                        </div>
                        <div class="card-body p-0">
                            <?= render_downloads_table($by_cat[0]) ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php
    endif;
include THEMES . setting('current_theme', CURRENT_THEME) . '/footer.php';

// -------------------------------------------------------------------------
// Helper: render a downloads table
// -------------------------------------------------------------------------
function render_downloads_table(array $rows): string
{
    ob_start();
    ?>
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th style="width:64px"></th>
                <th><?= __('downloads.frontend.table.title') ?></th>
                <th><?= __('downloads.frontend.table.size') ?></th>
                <th><?= __('downloads.frontend.table.downloads') ?></th>
                <th><?= __('downloads.frontend.table.uploader') ?></th>
                <th><?= __('downloads.frontend.table.date') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $dl): ?>
                <?php
                    $is_url = !empty($dl['download_url']);
                    $detail_link = public_path('downloads.php?view=' . (int)$dl['download_id']);
                    $link   = public_path('downloads.php?action=download&id=' . (int)$dl['download_id']);
                    $thumb  = $dl['download_thumbnail'] ?? '';
                ?>
                <tr>
                    <td class="ps-3">
                        <?php if ($thumb !== ''): ?>
                            <img src="<?= e(downloads_thumb_url($thumb)) ?>"
                                 alt="" style="width:56px;height:42px;object-fit:cover;border-radius:4px;">
                        <?php elseif ($is_url): ?>
                            <span class="text-info fs-4"><i class="fa-solid fa-link"></i></span>
                        <?php else: ?>
                            <?php $icon = downloads_file_icon(strtolower(pathinfo((string)$dl['download_file'], PATHINFO_EXTENSION))); ?>
                            <span class="<?= e($icon['color']) ?> fs-4"><i class="<?= e($icon['icon']) ?>"></i></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?= e($detail_link) ?>">
                            <?= e($dl['download_title']) ?>
                        </a>
                        <?php if (!empty($dl['download_description'])): ?>
                            <div class="text-muted small"><?= e($dl['download_description']) ?></div>
                        <?php endif; ?>
                        <div class="mt-1">
                            <a class="small text-decoration-none" href="<?= e($link) ?>"<?= $is_url ? ' target="_blank" rel="noopener noreferrer"' : '' ?>>Atsisiusti</a>
                            <span class="text-muted">·</span>
                            <a class="small text-decoration-none" href="<?= e($detail_link) ?>">Perziureti</a>
                        </div>
                    </td>
                    <td class="text-nowrap"><?= $is_url ? '<span class="text-muted">—</span>' : e(format_bytes_human((int)$dl['download_size'])) ?></td>
                    <td><?= (int)$dl['download_count'] ?></td>
                    <td><?= e($dl['uploader_name'] ?? '—') ?></td>
                    <td class="text-nowrap"><?= e(date('Y-m-d', (int)$dl['download_datestamp'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
    return (string)ob_get_clean();
}
