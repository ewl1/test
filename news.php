<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once INFUSIONS . 'news/support/helpers.php';

require_permission('news_view');

$newsId = (int)($_GET['id'] ?? 0);
$newsSlug = trim((string)($_GET['slug'] ?? ''));
$viewer = current_user();
$commentError = null;
$commentDraft = '';

function news_fetch_item($id = 0, $slug = '')
{
    $where = '';
    $params = [];

    if ($id > 0) {
        $where = 'id = :id';
        $params[':id'] = (int)$id;
    } elseif ($slug !== '' && news_has_slug_column()) {
        $where = 'slug = :slug';
        $params[':slug'] = $slug;
    } else {
        return null;
    }

    $stmt = $GLOBALS['pdo']->prepare('SELECT * FROM ' . news_table_name() . ' WHERE ' . $where . ' LIMIT 1');
    $stmt->execute($params);
    return $stmt->fetch() ?: null;
}

function news_render_summary_html($summary)
{
    if (news_editor_mode() === 'tinymce' || news_editor_mode() === 'mixed') {
        return (string)$summary;
    }

    return bbcode_to_html((string)$summary, [
        'allowed_tags' => news_allowed_bbcode_tags(),
        'max_length' => 20000,
    ]);
}

function news_item_url(array $item)
{
    if (!empty($item['slug'])) {
        return public_path('news.php?slug=' . rawurlencode((string)$item['slug']));
    }

    return public_path('news.php?id=' . (int)$item['id']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (!$viewer) {
        abort_http(401, 'Prisijungimas reikalingas.');
    }

    $action = (string)($_POST['comment_action'] ?? '');
    if ($action === 'create') {
        $commentDraft = (string)($_POST['comment'] ?? '');
        [$ok, $message, $commentId] = create_content_comment('news', (int)($_POST['content_id'] ?? 0), (int)$viewer['id'], $commentDraft);
        if ($ok) {
            flash('content_comment_success', $message);
            redirect(public_path('news.php?id=' . (int)($_POST['content_id'] ?? 0) . '#content-comment-' . (int)$commentId));
        }
        $commentError = $message;
    } elseif ($action === 'delete') {
        [$ok, $message, $meta] = delete_content_comment((int)($_POST['comment_id'] ?? 0), $viewer);
        if ($ok) {
            flash('content_comment_success', $message);
            redirect(public_path('news.php?id=' . (int)($meta['content_id'] ?? 0)));
        }
        $commentError = $message;
    }
}

$item = news_fetch_item($newsId, $newsSlug);
$commentPager = null;
$comments = [];
$commentCount = 0;
if ($item) {
    $commentCount = content_comments_count('news', (int)$item['id']);
    $commentPage = max(1, (int)($_GET['page'] ?? 1));
    $commentPager = paginate($commentCount, content_comments_per_page_setting(), $commentPage);
    $comments = fetch_content_comments('news', (int)$item['id'], $commentPager['per_page'], $commentPager['offset']);
}

$listItems = !$item ? news_recent_items(20) : [];
$successMessage = flash('content_comment_success');

include __DIR__ . '/themes/default/header.php';
?>
<div class="container my-4">
    <?php if ($item): ?>
        <?php if ($successMessage): ?><div class="alert alert-success"><?= e($successMessage) ?></div><?php endif; ?>
        <?php if ($commentError): ?><div class="alert alert-danger"><?= e($commentError) ?></div><?php endif; ?>

        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <div>
                        <h1 class="h3 mb-2"><?= e($item['title']) ?></h1>
                        <div class="small text-secondary"><?= e(format_dt($item['created_at'])) ?></div>
                    </div>
                    <a class="btn btn-outline-secondary btn-sm" href="<?= public_path('news.php') ?>">Visos naujienos</a>
                </div>
                <div class="mt-4">
                    <?= news_render_summary_html($item['summary'] ?? '') ?>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Komentarai (<?= (int)$commentCount ?>)</div>
            <div class="card-body">
                <?php if (!$viewer): ?>
                    <div class="alert alert-info">Komentuoti gali tik prisijunge nariai.</div>
                <?php else: ?>
                    <form method="post" class="mb-4">
                        <?= csrf_field() ?>
                        <input type="hidden" name="comment_action" value="create">
                        <input type="hidden" name="content_id" value="<?= (int)$item['id'] ?>">
                        <div class="mb-3">
                            <label class="form-label" for="news-comment">Komentaras</label>
                            <textarea class="form-control" id="news-comment" name="comment" rows="5" maxlength="3000" required><?= e($commentDraft) ?></textarea>
                        </div>
                        <button class="btn btn-primary">Paskelbti komentara</button>
                    </form>
                <?php endif; ?>

                <div class="profile-comments-list">
                    <?php if (!$comments): ?>
                        <div class="text-secondary">Komentaru dar nera.</div>
                    <?php else: ?>
                        <?php foreach ($comments as $comment): ?>
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
                                            <?php if (can_manage_content_comment($comment, $viewer)): ?>
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
                <?php if (($commentPager['pages'] ?? 0) > 1): ?>
                    <div class="mt-4">
                        <?= render_pagination(news_item_url($item), $commentPager) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-header">Naujienos</div>
            <div class="card-body">
                <?php if (!$listItems): ?>
                    <div class="text-secondary">Naujienu dar nera.</div>
                <?php else: ?>
                    <?php foreach ($listItems as $row): ?>
                        <article class="mb-4 pb-4 border-bottom">
                            <h2 class="h5 mb-2"><a class="text-decoration-none" href="<?= e(news_item_url($row)) ?>"><?= e($row['title']) ?></a></h2>
                            <div class="small text-secondary mb-2"><?= e(format_dt($row['created_at'])) ?></div>
                            <div><?= e(news_summary_excerpt($row['summary'] ?? '', 220)) ?></div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/themes/default/footer.php'; ?>
