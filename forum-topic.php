<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!function_exists('forum_get_topic')) {
    include THEMES . setting('current_theme', CURRENT_THEME) . '/header.php';
    echo '<div class="alert alert-warning">Forumo infusion dar neidiegta arba isjungta.</div>';
    include THEMES . setting('current_theme', CURRENT_THEME) . '/footer.php';
    return;
}

$topicId = (int)($_GET['id'] ?? 0);
$topic = forum_get_topic($topicId);
if (!$topic) {
    abort_http(404, 'Tema nerasta.');
}

$replyError = null;
$replyContent = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && (string)($_POST['forum_action'] ?? '') === 'reply') {
    verify_csrf();
    $replyContent = (string)($_POST['content'] ?? '');
    [$ok, $message, $postId] = forum_create_reply($topic['id'], $replyContent);
    if ($ok && $postId) {
        flash('forum_success', $message);
        $lastPage = forum_topic_last_page($topic['id']);
        redirect(forum_topic_url($topic['id'], $lastPage) . '#forum-reply-' . (int)$postId);
    }

    $replyError = $message;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    forum_increment_topic_views($topic['id']);
    $topic = forum_get_topic($topic['id']);
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = forum_posts_per_page();
$replyTotal = forum_count_replies($topic['id']);
$pager = paginate($replyTotal, $perPage, $page);
if (($pager['pages'] ?? 0) > 0 && $page > (int)$pager['pages']) {
    $page = (int)$pager['pages'];
    $pager = paginate($replyTotal, $perPage, $page);
}

$replies = forum_get_replies($topic['id'], $perPage, (int)$pager['offset']);
$successMessage = flash('forum_success');
$forum = forum_get_forum((int)$topic['forum_id']);
$parentForum = $forum && !empty($forum['parent_id']) ? forum_get_forum((int)$forum['parent_id']) : null;

include THEMES . setting('current_theme', CURRENT_THEME) . '/header.php';
?>
<div class="row justify-content-center">
    <div class="col-xl-10">
        <?php
        $breadcrumbs = [
            ['title' => 'Forumas', 'url' => forum_index_url()],
            ['title' => $topic['category_title'], 'url' => forum_index_url()],
        ];
        if ($parentForum) {
            $breadcrumbs[] = ['title' => $parentForum['title'], 'url' => forum_forum_url((int)$parentForum['id'])];
        }
        if ($forum) {
            $breadcrumbs[] = ['title' => $forum['title'], 'url' => forum_forum_url((int)$forum['id'])];
        }
        $breadcrumbs[] = ['title' => $topic['title'], 'url' => ''];
        forum_render_breadcrumb($breadcrumbs);
        ?>

        <?php if ($successMessage): ?>
            <div class="alert alert-success"><?= e($successMessage) ?></div>
        <?php endif; ?>

        <div class="card forum-topic-header-card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <div>
                        <div class="d-flex gap-2 align-items-center flex-wrap mb-2">
                            <?php if ((int)$topic['is_pinned'] === 1): ?>
                                <span class="badge text-bg-warning">Prisegta</span>
                            <?php endif; ?>
                            <?php if ((int)$topic['is_locked'] === 1): ?>
                                <span class="badge text-bg-dark">Uzrakinta</span>
                            <?php endif; ?>
                        </div>
                        <h1 class="h3 mb-2"><?= e($topic['title']) ?></h1>
                        <div class="text-secondary small">
                            Perziuros: <?= (int)$topic['views'] ?>
                            · Atsakymai: <?= (int)$topic['reply_count'] ?>
                            · Sukurta: <?= e(format_dt($topic['created_at'])) ?>
                        </div>
                    </div>
                    <?php if ($forum): ?>
                        <a class="btn btn-outline-secondary" href="<?= forum_forum_url((int)$forum['id']) ?>">Atgal i foruma</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <article class="card forum-post-card mb-4">
            <div class="card-body forum-post-layout">
                <aside class="forum-post-author">
                    <div class="forum-post-author-box">
                        <img src="<?= escape_url(user_avatar_url($topic)) ?>" alt="" class="forum-avatar forum-avatar-lg mb-3">
                        <div class="fw-semibold">
                            <?php if (!empty($topic['user_id'])): ?>
                                <a class="text-decoration-none" href="<?= user_profile_url((int)$topic['user_id']) ?>"><?= e($topic['username'] ?? 'Narys') ?></a>
                            <?php else: ?>
                                <?= e($topic['username'] ?? 'Svečias') ?>
                            <?php endif; ?>
                        </div>
                        <div class="small text-secondary"><?= e(format_dt($topic['created_at'])) ?></div>
                    </div>
                </aside>
                <div class="forum-post-content">
                    <div class="forum-post-meta">
                        <span class="badge text-bg-primary">Tema</span>
                    </div>
                    <div class="forum-post-body"><?= forum_format_body($topic['content']) ?></div>
                </div>
            </div>
        </article>

        <?php foreach ($replies as $reply): ?>
            <article class="card forum-post-card mb-3" id="forum-reply-<?= (int)$reply['id'] ?>">
                <div class="card-body forum-post-layout">
                    <aside class="forum-post-author">
                        <div class="forum-post-author-box">
                            <img src="<?= escape_url(user_avatar_url($reply)) ?>" alt="" class="forum-avatar forum-avatar-lg mb-3">
                            <div class="fw-semibold">
                                <?php if (!empty($reply['user_id'])): ?>
                                    <a class="text-decoration-none" href="<?= user_profile_url((int)$reply['user_id']) ?>"><?= e($reply['username'] ?? 'Narys') ?></a>
                                <?php else: ?>
                                    <?= e($reply['username'] ?? 'Svečias') ?>
                                <?php endif; ?>
                            </div>
                            <div class="small text-secondary"><?= e(format_dt($reply['created_at'])) ?></div>
                        </div>
                    </aside>
                    <div class="forum-post-content">
                        <div class="forum-post-meta">
                            <span class="badge text-bg-secondary">Atsakymas</span>
                        </div>
                        <div class="forum-post-body"><?= forum_format_body($reply['content']) ?></div>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>

        <?php $pagination = render_pagination(forum_topic_url((int)$topic['id']), $pager); ?>
        <?php if ($pagination !== ''): ?>
            <div class="mb-4"><?= $pagination ?></div>
        <?php endif; ?>

        <div class="card forum-editor-card">
            <div class="card-header">Atsakyti i tema</div>
            <div class="card-body">
                <?php if ($replyError): ?>
                    <div class="alert alert-danger"><?= e($replyError) ?></div>
                <?php endif; ?>

                <?php if ((int)$topic['is_locked'] === 1): ?>
                    <div class="alert alert-warning mb-0">Tema uzrakinta. Nauji atsakymai negalimi.</div>
                <?php elseif (!current_user()): ?>
                    <div class="alert alert-info mb-0">Atsakyti gali tik prisijunge nariai. <a href="<?= public_path('login.php') ?>">Prisijunkite</a>.</div>
                <?php else: ?>
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="forum_action" value="reply">

                        <div class="mb-2 d-flex flex-wrap gap-2">
                            <?php forum_render_editor_toolbar('forum-reply-content'); ?>
                        </div>
                        <div class="mb-2 d-flex flex-wrap gap-2">
                            <?php forum_render_smileys('forum-reply-content'); ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="forum-reply-content">Jusu atsakymas</label>
                            <textarea class="form-control" id="forum-reply-content" name="content" rows="7" maxlength="15000" required><?= e($replyContent) ?></textarea>
                            <div class="form-text">Leidziamas BBCode: [b], [i], [u], [quote], [code], [url=...][/url]</div>
                        </div>

                        <button class="btn btn-primary">Atsakyti</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include THEMES . setting('current_theme', CURRENT_THEME) . '/footer.php'; ?>
