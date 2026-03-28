<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!function_exists('forum_get_forum')) {
    include THEMES . setting('current_theme', CURRENT_THEME) . '/header.php';
    echo '<div class="alert alert-warning">Forumo infusion dar neįdiegta arba išjungta.</div>';
    include THEMES . setting('current_theme', CURRENT_THEME) . '/footer.php';
    return;
}

$forumId = (int)($_GET['id'] ?? 0);
$forum = forum_get_forum($forumId);
if (!$forum) {
    abort_http(404, 'Forumas nerastas.');
}

$formError = null;
$topicTitle = '';
$topicContent = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && (string)($_POST['forum_action'] ?? '') === 'create_topic') {
    verify_csrf();
    $topicTitle = trim((string)($_POST['title'] ?? ''));
    $topicContent = (string)($_POST['content'] ?? '');
    [$ok, $message, $topicId] = forum_create_topic($forum['id'], $topicTitle, $topicContent);
    if ($ok && $topicId) {
        flash('forum_success', $message);
        redirect(forum_topic_url($topicId));
    }

    $formError = $message;
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = forum_topics_per_page();
$total = forum_count_topics($forum['id']);
$pager = paginate($total, $perPage, $page);
if (($pager['pages'] ?? 0) > 0 && $page > (int)$pager['pages']) {
    $page = (int)$pager['pages'];
    $pager = paginate($total, $perPage, $page);
}

$topics = forum_get_topics($forum['id'], $perPage, (int)$pager['offset']);
$parentForum = !empty($forum['parent_id']) ? forum_get_forum((int)$forum['parent_id']) : null;
$successMessage = flash('forum_success');

include THEMES . setting('current_theme', CURRENT_THEME) . '/header.php';
?>
<div class="row justify-content-center">
    <div class="col-xl-10">
        <?php
        $breadcrumbs = [
            ['title' => __('forum.title'), 'url' => forum_index_url()],
        ];
        if ($parentForum) {
            $breadcrumbs[] = ['title' => $forum['category_title'], 'url' => forum_index_url()];
            $breadcrumbs[] = ['title' => $parentForum['title'], 'url' => forum_forum_url((int)$parentForum['id'])];
        } else {
            $breadcrumbs[] = ['title' => $forum['category_title'], 'url' => forum_index_url()];
        }
        $breadcrumbs[] = ['title' => $forum['title'], 'url' => ''];
        forum_render_breadcrumb($breadcrumbs);
        ?>

        <div class="card forum-forum-header-card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <div>
                        <h1 class="h3 mb-2"><?= e($forum['title']) ?></h1>
                        <?php if (!empty($forum['description'])): ?>
                            <p class="text-secondary mb-0"><?= e($forum['description']) ?></p>
                        <?php endif; ?>
                    </div>
                    <a class="btn btn-outline-secondary" href="<?= forum_index_url() ?>"><?= e(__('forum.all')) ?></a>
                </div>
            </div>
        </div>

        <?php if ($successMessage): ?>
            <div class="alert alert-success"><?= e($successMessage) ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center gap-3">
                <span><?= e(__('forum.topics')) ?></span>
                <span class="badge text-bg-secondary"><?= (int)$total ?> viso</span>
            </div>
            <div class="card-body p-0">
                <?php if (!$topics): ?>
                    <div class="p-4 text-secondary">Šiame forume temų dar nėra.</div>
                <?php else: ?>
                    <?php foreach ($topics as $topic): ?>
                        <article class="forum-topic-list-item">
                            <div class="forum-topic-author">
                                <a href="<?= !empty($topic['user_id']) ? user_profile_url((int)$topic['user_id']) : '#' ?>" class="text-decoration-none">
                                    <img src="<?= escape_url(user_avatar_url($topic)) ?>" alt="" class="forum-avatar forum-avatar-md">
                                </a>
                            </div>
                            <div class="forum-topic-main">
                                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                    <?php if ((int)$topic['is_pinned'] === 1): ?>
                                        <span class="badge text-bg-warning"><?= e(__('forum.pinned')) ?></span>
                                    <?php endif; ?>
                                    <?php if ((int)$topic['is_locked'] === 1): ?>
                                        <span class="badge text-bg-dark"><?= e(__('forum.locked')) ?></span>
                                    <?php endif; ?>
                                    <h2 class="h5 mb-0">
                                        <a class="text-decoration-none" href="<?= forum_topic_url((int)$topic['id']) ?>"><?= e($topic['title']) ?></a>
                                    </h2>
                                </div>
                                <div class="small text-secondary mb-2">
                                    <?php if (!empty($topic['user_id'])): ?>
                                        <a class="text-decoration-none" href="<?= user_profile_url((int)$topic['user_id']) ?>"><?= e($topic['username'] ?? __('member.none')) ?></a>
                                    <?php else: ?>
                                        <?= e($topic['username'] ?? __('member.guest')) ?>
                                    <?php endif; ?>
                                    · <?= e(format_dt($topic['created_at'])) ?>
                                </div>
                                <p class="mb-0"><?= e(forum_excerpt($topic['content'])) ?></p>
                            </div>
                            <div class="forum-topic-stats">
                                <div><strong><?= (int)$topic['reply_count'] ?></strong><span><?= e(__('forum.replies')) ?></span></div>
                                <div><strong><?= (int)$topic['views'] ?></strong><span><?= e(__('forum.views')) ?></span></div>
                            </div>
                            <div class="forum-topic-last">
                                <div class="small text-secondary mb-1"><?= e(__('forum.last_reply')) ?></div>
                                <div class="fw-semibold">
                                    <?php if (!empty($topic['last_post_user_id'])): ?>
                                        <a class="text-decoration-none" href="<?= user_profile_url((int)$topic['last_post_user_id']) ?>">
                                            <?= e($topic['last_post_username'] ?? __('member.none')) ?>
                                        </a>
                                    <?php else: ?>
                                        <?= e($topic['last_post_username'] ?? __('member.guest')) ?>
                                    <?php endif; ?>
                                </div>
                                <div class="small text-secondary"><?= e(format_dt($topic['last_post_at'])) ?></div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php $pagination = render_pagination(forum_forum_url((int)$forum['id']), $pager); ?>
        <?php if ($pagination !== ''): ?>
            <div class="mb-4"><?= $pagination ?></div>
        <?php endif; ?>

        <div class="card forum-editor-card">
            <div class="card-header"><?= e(__('forum.topic.create')) ?></div>
            <div class="card-body">
                <?php if ($formError): ?>
                    <div class="alert alert-danger"><?= e($formError) ?></div>
                <?php endif; ?>

                <?php if (!current_user()): ?>
                    <div class="alert alert-info mb-0"><?= e(__('forum.login_to_post')) ?> <a href="<?= public_path('login.php') ?>"><?= e(__('nav.login')) ?></a>.</div>
                <?php else: ?>
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="forum_action" value="create_topic">

                        <div class="mb-2 d-flex flex-wrap gap-2">
                            <?php forum_render_editor_toolbar('forum-topic-content'); ?>
                        </div>
                        <div class="mb-2 d-flex flex-wrap gap-2">
                            <?php forum_render_smileys('forum-topic-content'); ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="forum-topic-title"><?= e(__('forum.topic.title')) ?></label>
                            <input class="form-control" id="forum-topic-title" name="title" maxlength="190" value="<?= e($topicTitle) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="forum-topic-content"><?= e(__('forum.message')) ?></label>
                            <textarea class="form-control" id="forum-topic-content" name="content" rows="7" maxlength="15000" required><?= e($topicContent) ?></textarea>
                            <div class="form-text"><?= e(__('forum.allowed_bbcode')) ?></div>
                        </div>

                        <button class="btn btn-primary"><?= e(__('forum.create')) ?></button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include THEMES . setting('current_theme', CURRENT_THEME) . '/footer.php'; ?>
