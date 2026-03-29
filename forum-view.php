<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!function_exists('forum_get_forum')) {
    include THEMES . setting('current_theme', CURRENT_THEME) . '/header.php';
    echo '<div class="alert alert-warning">' . e(__('forum.unavailable')) . '</div>';
    include THEMES . setting('current_theme', CURRENT_THEME) . '/footer.php';
    return;
}

$forumId = (int)($_GET['id'] ?? 0);
$forum = forum_get_forum($forumId);
if (!$forum) {
    abort_http(404, __('forum.message.forum_not_found'));
}

$formError = null;
$topicTitle = '';
$topicContent = '';
$topicMoodId = 0;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && (string)($_POST['forum_action'] ?? '') === 'create_topic') {
    verify_csrf();
    $topicTitle = trim((string)($_POST['title'] ?? ''));
    $topicContent = (string)($_POST['content'] ?? '');
    $topicMoodId = (int)($_POST['mood_id'] ?? 0);
    [$ok, $message, $topicId] = forum_create_topic($forum['id'], $topicTitle, $topicContent, $topicMoodId, $_FILES['attachments'] ?? []);
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
                    <div class="d-flex align-items-start gap-3">
                        <?= forum_render_node_visual($forum, 'forum-header-visual') ?>
                        <div>
                            <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                                <h1 class="h3 mb-0"><?= e($forum['title']) ?></h1>
                                <?php if (($forum['forum_type'] ?? 'forum') === 'help'): ?>
                                    <span class="badge text-bg-info">Pagalba ir atsakymai</span>
                                <?php endif; ?>
                                <?php if (!empty($forum['is_locked'])): ?>
                                    <span class="badge text-bg-dark">Užrakintas</span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($forum['description_html'])): ?>
                                <div class="text-secondary"><?= $forum['description_html'] ?></div>
                            <?php endif; ?>
                            <?php if (!empty($forum['keywords_list'])): ?>
                                <div class="d-flex flex-wrap gap-2 mt-3">
                                    <?php foreach ($forum['keywords_list'] as $keyword): ?>
                                        <span class="badge text-bg-light"><?= e($keyword) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <a class="btn btn-outline-secondary" href="<?= forum_index_url() ?>"><?= e(__('forum.all')) ?></a>
                </div>
            </div>
        </div>

        <?php if (!empty($forum['rules_html'])): ?>
            <div class="alert alert-warning forum-rules-box mb-4">
                <div class="fw-semibold mb-2">Forumo taisyklės ir perspėjimai</div>
                <div><?= $forum['rules_html'] ?></div>
            </div>
        <?php endif; ?>

        <?php if ($successMessage): ?>
            <div class="alert alert-success"><?= e($successMessage) ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center gap-3">
                <span><?= e(__('forum.topics')) ?></span>
                <span class="badge text-bg-secondary"><?= (int)$total ?> <?= e(__('forum.total')) ?></span>
            </div>
            <div class="card-body p-0">
                <?php if (!$topics): ?>
                    <div class="p-4 text-secondary"><?= e(__('forum.no_topics')) ?></div>
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
                                    <?= forum_render_mood_badge((int)($topic['mood_id'] ?? 0)) ?>
                                    <?php if (forum_is_popular_topic($topic)): ?>
                                        <span class="badge text-bg-danger">Populiari</span>
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

        <?php if (!empty($forum['show_participants'])): ?>
            <?php $participants = forum_get_participants((int)$forum['id']); ?>
            <?php if ($participants): ?>
                <div class="card mb-4">
                    <div class="card-header">Dalyvaujantys nariai</div>
                    <div class="card-body d-flex flex-wrap gap-2">
                        <?php foreach ($participants as $participant): ?>
                            <a class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-2" href="<?= user_profile_url((int)$participant['id']) ?>">
                                <img src="<?= escape_url(user_avatar_url($participant)) ?>" alt="" class="forum-avatar forum-avatar-sm">
                                <span><?= e($participant['username']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="card forum-editor-card">
            <div class="card-header"><?= e(__('forum.topic.create')) ?></div>
            <div class="card-body">
                <?php if ($formError): ?>
                    <div class="alert alert-danger"><?= e($formError) ?></div>
                <?php endif; ?>

                <?php if (!current_user()): ?>
                    <div class="alert alert-info mb-0"><?= e(__('forum.login_to_post')) ?> <a href="<?= public_path('login.php') ?>"><?= e(__('nav.login')) ?></a>.</div>
                <?php elseif (!empty($forum['is_locked'])): ?>
                    <div class="alert alert-warning mb-0">Šis forumas užrakintas. Naujos temos negalimos.</div>
                <?php else: ?>
                    <form method="post" enctype="multipart/form-data">
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

                        <?php if ($moods = forum_get_moods(true)): ?>
                            <div class="mb-3">
                                <label class="form-label" for="forum-topic-mood">Forumo nuotaika</label>
                                <select class="form-select" id="forum-topic-mood" name="mood_id">
                                    <option value="0">Be nuotaikos</option>
                                    <?php foreach ($moods as $mood): ?>
                                        <option value="<?= (int)$mood['id'] ?>" <?= $topicMoodId === (int)$mood['id'] ? 'selected' : '' ?>><?= e($mood['title']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($forum['allow_attachments'])): ?>
                            <div class="mb-3">
                                <label class="form-label" for="forum-topic-attachments">Prisegti failus</label>
                                <input class="form-control" id="forum-topic-attachments" type="file" name="attachments[]" multiple>
                                <div class="form-text">Leidžiami tipai: <?= e(implode(', ', forum_attachment_allowed_extensions())) ?></div>
                            </div>
                        <?php endif; ?>

                        <button class="btn btn-primary"><?= e(__('forum.create')) ?></button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include THEMES . setting('current_theme', CURRENT_THEME) . '/footer.php'; ?>
