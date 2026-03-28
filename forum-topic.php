<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!function_exists('forum_get_topic')) {
    include THEMES . setting('current_theme', CURRENT_THEME) . '/header.php';
    echo '<div class="alert alert-warning">' . e(__('forum.unavailable')) . '</div>';
    include THEMES . setting('current_theme', CURRENT_THEME) . '/footer.php';
    return;
}

$topicId = (int)($_GET['id'] ?? 0);
$topic = forum_get_topic($topicId);
if (!$topic) {
    abort_http(404, __('forum.topic.not_found'));
}

$currentPage = max(1, (int)($_GET['page'] ?? 1));
$canModerateTopic = forum_can_moderate_topic($topic);
$topicEditMode = $canModerateTopic && (string)($_GET['mode'] ?? '') === 'edit';
$topicEditError = null;
$moderationError = null;
$topicEditTitle = (string)$topic['title'];
$topicEditContent = (string)$topic['content'];
$replyError = null;
$replyContent = '';

$replyEditId = (int)($_GET['edit_reply'] ?? 0);
$replyEdit = $replyEditId > 0 ? forum_get_reply($replyEditId) : null;
if ($replyEdit && (int)$replyEdit['topic_id'] !== (int)$topic['id']) {
    $replyEdit = null;
}
$replyEditMode = $replyEdit && forum_can_moderate_reply($replyEdit);
$replyEditError = null;
$replyEditContent = $replyEdit ? (string)$replyEdit['content'] : '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verify_csrf();
    $action = (string)($_POST['forum_action'] ?? '');

    if ($action === 'reply') {
        $replyContent = (string)($_POST['content'] ?? '');
        [$ok, $message, $postId] = forum_create_reply($topic['id'], $replyContent);
        if ($ok && $postId) {
            flash('forum_success', $message);
            $lastPage = forum_topic_last_page($topic['id']);
            redirect(forum_topic_url($topic['id'], $lastPage) . '#forum-reply-' . (int)$postId);
        }

        $replyError = $message;
    } elseif ($action === 'toggle_pin') {
        [$ok, $message] = forum_set_topic_flag($topic['id'], 'is_pinned', (int)$topic['is_pinned'] === 1 ? 0 : 1);
        if ($ok) {
            flash('forum_success', $message);
            redirect(forum_topic_url($topic['id'], $currentPage));
        }
        $moderationError = $message;
    } elseif ($action === 'toggle_lock') {
        [$ok, $message] = forum_set_topic_flag($topic['id'], 'is_locked', (int)$topic['is_locked'] === 1 ? 0 : 1);
        if ($ok) {
            flash('forum_success', $message);
            redirect(forum_topic_url($topic['id'], $currentPage));
        }
        $moderationError = $message;
    } elseif ($action === 'delete_topic') {
        [$ok, $message, $forumId] = forum_delete_topic($topic['id']);
        if ($ok && $forumId) {
            flash('forum_success', $message);
            redirect(forum_forum_url($forumId));
        }
        $moderationError = $message;
    } elseif ($action === 'edit_topic') {
        $topicEditTitle = trim((string)($_POST['title'] ?? ''));
        $topicEditContent = (string)($_POST['content'] ?? '');
        [$ok, $message] = forum_update_topic($topic['id'], $topicEditTitle, $topicEditContent);
        if ($ok) {
            flash('forum_success', $message);
            redirect(forum_topic_url($topic['id'], $currentPage));
        }
        $topicEditMode = true;
        $topicEditError = $message;
    } elseif ($action === 'edit_reply') {
        $replyId = (int)($_POST['reply_id'] ?? 0);
        $replyPage = max(1, (int)($_POST['reply_page'] ?? $currentPage));
        $replyEdit = forum_get_reply($replyId);
        if (!$replyEdit || (int)$replyEdit['topic_id'] !== (int)$topic['id']) {
            $moderationError = __('forum.reply.not_found');
        } else {
            $replyEditMode = forum_can_moderate_reply($replyEdit);
            $replyEditContent = (string)($_POST['content'] ?? '');
            [$ok, $message] = forum_update_reply($replyId, $replyEditContent);
            if ($ok) {
                flash('forum_success', $message);
                redirect(forum_topic_url($topic['id'], $replyPage) . '#forum-reply-' . $replyId);
            }
            $replyEditError = $message;
        }
    } elseif ($action === 'delete_reply') {
        $replyId = (int)($_POST['reply_id'] ?? 0);
        $replyPage = max(1, (int)($_POST['reply_page'] ?? $currentPage));
        $reply = forum_get_reply($replyId);
        if (!$reply || (int)$reply['topic_id'] !== (int)$topic['id']) {
            $moderationError = __('forum.reply.not_found');
        } else {
            [$ok, $message] = forum_delete_reply($replyId);
            if ($ok) {
                flash('forum_success', $message);
                $targetPage = min($replyPage, forum_topic_last_page($topic['id']));
                redirect(forum_topic_url($topic['id'], $targetPage));
            }
            $moderationError = $message;
        }
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    forum_increment_topic_views($topic['id']);
}

$topic = forum_get_topic($topic['id']);
$page = $currentPage;
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
$canModerateTopic = forum_can_moderate_topic($topic);

include THEMES . setting('current_theme', CURRENT_THEME) . '/header.php';
?>
<div class="row justify-content-center">
    <div class="col-xl-10">
        <?php
        $breadcrumbs = [
            ['title' => __('forum.title'), 'url' => forum_index_url()],
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
        <?php if ($moderationError): ?>
            <div class="alert alert-danger"><?= e($moderationError) ?></div>
        <?php endif; ?>

        <div class="card forum-topic-header-card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <div>
                        <div class="d-flex gap-2 align-items-center flex-wrap mb-2">
                            <?php if ((int)$topic['is_pinned'] === 1): ?>
                                <span class="badge text-bg-warning"><?= e(__('forum.pinned')) ?></span>
                            <?php endif; ?>
                            <?php if ((int)$topic['is_locked'] === 1): ?>
                                <span class="badge text-bg-dark"><?= e(__('forum.locked')) ?></span>
                            <?php endif; ?>
                        </div>
                        <h1 class="h3 mb-2"><?= e($topic['title']) ?></h1>
                        <div class="text-secondary small">
                            <?= e(__('forum.views')) ?>: <?= (int)$topic['views'] ?>
                            · <?= e(__('forum.replies')) ?>: <?= (int)$topic['reply_count'] ?>
                            · <?= e(__('forum.created')) ?>: <?= e(format_dt($topic['created_at'])) ?>
                        </div>
                    </div>
                    <?php if ($forum): ?>
                        <a class="btn btn-outline-secondary" href="<?= forum_forum_url((int)$forum['id']) ?>"><?= e(__('forum.back')) ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($canModerateTopic): ?>
            <div class="card forum-moderation-card mb-4">
                <div class="card-body d-flex justify-content-between align-items-center gap-3 flex-wrap">
                    <div>
                        <strong><?= e(__('forum.moderation')) ?></strong>
                        <div class="small text-secondary"><?= e(__('forum.moderation.help')) ?></div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <form method="post" class="d-inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="forum_action" value="toggle_pin">
                            <button class="btn btn-sm btn-outline-warning" type="submit"><?= e((int)$topic['is_pinned'] === 1 ? __('forum.unpin') : __('forum.pin')) ?></button>
                        </form>
                        <form method="post" class="d-inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="forum_action" value="toggle_lock">
                            <button class="btn btn-sm btn-outline-secondary" type="submit"><?= e((int)$topic['is_locked'] === 1 ? __('forum.unlock') : __('forum.lock')) ?></button>
                        </form>
                        <a class="btn btn-sm btn-outline-primary" href="<?= forum_topic_url((int)$topic['id'], $page) . '&mode=edit' ?>"><?= e(__('forum.edit')) ?></a>
                        <form method="post" class="d-inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="forum_action" value="delete_topic">
                            <button class="btn btn-sm btn-outline-danger" type="submit" data-confirm-message="<?= e(__('forum.delete_topic.confirm')) ?>"><?= e(__('forum.delete_topic')) ?></button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($canModerateTopic && $topicEditMode): ?>
            <div class="card forum-editor-card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center gap-3">
                    <span><?= e(__('forum.edit')) ?></span>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= forum_topic_url((int)$topic['id'], $page) ?>"><?= e(__('forum.close')) ?></a>
                </div>
                <div class="card-body">
                    <?php if ($topicEditError): ?>
                        <div class="alert alert-danger"><?= e($topicEditError) ?></div>
                    <?php endif; ?>
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="forum_action" value="edit_topic">

                        <div class="mb-2 d-flex flex-wrap gap-2">
                            <?php forum_render_editor_toolbar('forum-edit-topic-content'); ?>
                        </div>
                        <div class="mb-2 d-flex flex-wrap gap-2">
                            <?php forum_render_smileys('forum-edit-topic-content'); ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="forum-edit-topic-title"><?= e(__('forum.topic.title')) ?></label>
                            <input class="form-control" id="forum-edit-topic-title" name="title" maxlength="190" value="<?= e($topicEditTitle) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="forum-edit-topic-content"><?= e(__('forum.content')) ?></label>
                            <textarea class="form-control" id="forum-edit-topic-content" name="content" rows="8" maxlength="15000" required><?= e($topicEditContent) ?></textarea>
                            <div class="form-text"><?= e(__('forum.allowed_bbcode')) ?></div>
                        </div>

                        <button class="btn btn-primary"><?= e(__('forum.save_changes')) ?></button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <article class="card forum-post-card mb-4">
            <div class="card-body forum-post-layout">
                <aside class="forum-post-author">
                    <div class="forum-post-author-box">
                        <img src="<?= escape_url(user_avatar_url($topic)) ?>" alt="" class="forum-avatar forum-avatar-lg mb-3">
                        <div class="fw-semibold">
                            <?php if (!empty($topic['user_id'])): ?>
                                <a class="text-decoration-none" href="<?= user_profile_url((int)$topic['user_id']) ?>"><?= e($topic['username'] ?? __('member.none')) ?></a>
                            <?php else: ?>
                                <?= e($topic['username'] ?? __('member.guest')) ?>
                            <?php endif; ?>
                        </div>
                        <div class="small text-secondary"><?= e(format_dt($topic['created_at'])) ?></div>
                    </div>
                </aside>
                <div class="forum-post-content">
                    <div class="forum-post-meta">
                        <span class="badge text-bg-primary"><?= e(__('forum.topic_badge')) ?></span>
                    </div>
                    <div class="forum-post-body"><?= forum_format_body($topic['content']) ?></div>
                </div>
            </div>
        </article>

        <?php foreach ($replies as $reply): ?>
            <?php $canModerateReply = forum_can_moderate_reply($reply); ?>
            <article class="card forum-post-card mb-3" id="forum-reply-<?= (int)$reply['id'] ?>">
                <div class="card-body forum-post-layout">
                    <aside class="forum-post-author">
                        <div class="forum-post-author-box">
                            <img src="<?= escape_url(user_avatar_url($reply)) ?>" alt="" class="forum-avatar forum-avatar-lg mb-3">
                            <div class="fw-semibold">
                                <?php if (!empty($reply['user_id'])): ?>
                                    <a class="text-decoration-none" href="<?= user_profile_url((int)$reply['user_id']) ?>"><?= e($reply['username'] ?? __('member.none')) ?></a>
                                <?php else: ?>
                                    <?= e($reply['username'] ?? __('member.guest')) ?>
                                <?php endif; ?>
                            </div>
                            <div class="small text-secondary"><?= e(format_dt($reply['created_at'])) ?></div>
                            <?php if (!empty($reply['updated_at']) && $reply['updated_at'] !== $reply['created_at']): ?>
                                <div class="small text-secondary"><?= e(__('forum.edited')) ?>: <?= e(format_dt($reply['updated_at'])) ?></div>
                            <?php endif; ?>
                        </div>
                    </aside>
                    <div class="forum-post-content">
                        <div class="forum-post-meta d-flex justify-content-between align-items-center gap-3 flex-wrap">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="badge text-bg-secondary"><?= e(__('forum.reply_badge')) ?></span>
                            </div>
                            <?php if ($canModerateReply): ?>
                                <div class="d-flex flex-wrap gap-2">
                                    <a class="btn btn-sm btn-outline-primary" href="<?= forum_topic_url((int)$topic['id'], $page) . '&edit_reply=' . (int)$reply['id'] . '#forum-reply-' . (int)$reply['id'] ?>"><?= e(__('forum.edit')) ?></a>
                                    <form method="post" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="forum_action" value="delete_reply">
                                        <input type="hidden" name="reply_id" value="<?= (int)$reply['id'] ?>">
                                        <input type="hidden" name="reply_page" value="<?= (int)$page ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit" data-confirm-message="<?= e(__('forum.reply.delete.confirm')) ?>"><?= e(__('forum.reply.delete')) ?></button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($replyEditMode && $replyEdit && (int)$reply['id'] === (int)$replyEdit['id']): ?>
                            <div class="forum-reply-edit-box">
                                <?php if ($replyEditError): ?>
                                    <div class="alert alert-danger"><?= e($replyEditError) ?></div>
                                <?php endif; ?>
                                <form method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="forum_action" value="edit_reply">
                                    <input type="hidden" name="reply_id" value="<?= (int)$reply['id'] ?>">
                                    <input type="hidden" name="reply_page" value="<?= (int)$page ?>">

                                    <div class="mb-2 d-flex flex-wrap gap-2">
                                        <?php forum_render_editor_toolbar('forum-edit-reply-content-' . (int)$reply['id']); ?>
                                    </div>
                                    <div class="mb-2 d-flex flex-wrap gap-2">
                                        <?php forum_render_smileys('forum-edit-reply-content-' . (int)$reply['id']); ?>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label" for="forum-edit-reply-content-<?= (int)$reply['id'] ?>"><?= e(__('forum.reply.body')) ?></label>
                                        <textarea class="form-control" id="forum-edit-reply-content-<?= (int)$reply['id'] ?>" name="content" rows="7" maxlength="15000" required><?= e($replyEditContent) ?></textarea>
                                    </div>

                                    <div class="d-flex flex-wrap gap-2">
                                        <button class="btn btn-primary"><?= e(__('forum.reply.save')) ?></button>
                                        <a class="btn btn-outline-secondary" href="<?= forum_topic_url((int)$topic['id'], $page) . '#forum-reply-' . (int)$reply['id'] ?>"><?= e(__('forum.cancel')) ?></a>
                                    </div>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="forum-post-body"><?= forum_format_body($reply['content']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>

        <?php $pagination = render_pagination(forum_topic_url((int)$topic['id']), $pager); ?>
        <?php if ($pagination !== ''): ?>
            <div class="mb-4"><?= $pagination ?></div>
        <?php endif; ?>

        <div class="card forum-editor-card">
            <div class="card-header"><?= e(__('forum.reply_to_topic')) ?></div>
            <div class="card-body">
                <?php if ($replyError): ?>
                    <div class="alert alert-danger"><?= e($replyError) ?></div>
                <?php endif; ?>

                <?php if ((int)$topic['is_locked'] === 1): ?>
                    <div class="alert alert-warning mb-0"><?= e(__('forum.reply_locked')) ?></div>
                <?php elseif (!current_user()): ?>
                    <div class="alert alert-info mb-0"><?= e(__('forum.reply_login')) ?> <a href="<?= public_path('login.php') ?>"><?= e(__('nav.login')) ?></a>.</div>
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
                            <label class="form-label" for="forum-reply-content"><?= e(__('forum.your_reply')) ?></label>
                            <textarea class="form-control" id="forum-reply-content" name="content" rows="7" maxlength="15000" required><?= e($replyContent) ?></textarea>
                            <div class="form-text"><?= e(__('forum.allowed_bbcode')) ?></div>
                        </div>

                        <button class="btn btn-primary"><?= e(__('forum.reply')) ?></button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include THEMES . setting('current_theme', CURRENT_THEME) . '/footer.php'; ?>
