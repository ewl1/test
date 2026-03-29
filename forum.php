<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!function_exists('forum_get_index_data')) {
    include THEMES . setting('current_theme', CURRENT_THEME) . '/header.php';
    echo '<div class="alert alert-warning">' . e(__('forum.unavailable')) . '</div>';
    include THEMES . setting('current_theme', CURRENT_THEME) . '/footer.php';
    return;
}

$categories = forum_get_index_data();
include THEMES . setting('current_theme', CURRENT_THEME) . '/header.php';
?>
<div class="row justify-content-center">
    <div class="col-xl-10">
        <div class="card forum-hero-card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <div>
                        <h1 class="h3 mb-2"><?= e(__('forum.title')) ?></h1>
                        <p class="text-secondary mb-0"><?= e(__('forum.description')) ?></p>
                    </div>
                    <div class="forum-hero-actions">
                        <a class="btn btn-outline-primary" href="<?= forum_index_url() ?>"><?= e(__('forum.refresh')) ?></a>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!$categories): ?>
            <div class="alert alert-info"><?= e(__('forum.categories.empty')) ?></div>
        <?php endif; ?>

        <?php foreach ($categories as $category): ?>
            <section class="card forum-category-card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <?= forum_render_node_visual($category, 'forum-category-visual') ?>
                            <h2 class="h5 mb-0"><?= e($category['title']) ?></h2>
                        </div>
                        <?php if (!empty($category['description_html'])): ?>
                            <div class="small text-secondary"><?= $category['description_html'] ?></div>
                        <?php endif; ?>
                    </div>
                    <span class="badge text-bg-secondary"><?= count($category['forums']) ?> <?= e(__('forum.forum_count')) ?></span>
                </div>
                <div class="card-body p-0">
                    <?php if (!$category['forums']): ?>
                        <div class="p-4 text-secondary"><?= e(__('forum.forums.empty')) ?></div>
                    <?php else: ?>
                        <?php foreach ($category['forums'] as $forum): ?>
                            <article class="forum-forum-row">
                                <div class="forum-forum-main">
                                    <div class="d-flex align-items-start gap-3">
                                        <?= forum_render_node_visual($forum) ?>
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                                <h3 class="h5 mb-0">
                                                    <a class="text-decoration-none" href="<?= forum_forum_url((int)$forum['id']) ?>"><?= e($forum['title']) ?></a>
                                                </h3>
                                                <?php if (($forum['forum_type'] ?? 'forum') === 'help'): ?>
                                                    <span class="badge text-bg-info">Pagalba</span>
                                                <?php endif; ?>
                                                <?php if (!empty($forum['is_locked'])): ?>
                                                    <span class="badge text-bg-dark">Užrakintas</span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($forum['description_html'])): ?>
                                                <div class="text-secondary mb-2"><?= $forum['description_html'] ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($forum['keywords_list'])): ?>
                                                <div class="d-flex flex-wrap gap-2 mb-2">
                                                    <?php foreach ($forum['keywords_list'] as $keyword): ?>
                                                        <span class="badge text-bg-light"><?= e($keyword) ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($forum['subforums'])): ?>
                                                <div class="forum-subforum-list">
                                                    <span class="forum-subforum-label"><?= e(__('forum.subforums')) ?>:</span>
                                                    <?php foreach ($forum['subforums'] as $subforum): ?>
                                                        <a class="forum-subforum-link" href="<?= forum_forum_url((int)$subforum['id']) ?>">
                                                            <?= e($subforum['title']) ?>
                                                        </a>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="forum-forum-stats">
                                    <div><strong><?= (int)$forum['topics_count'] ?></strong><span><?= e(__('forum.topics')) ?></span></div>
                                    <div><strong><?= (int)$forum['posts_count'] ?></strong><span><?= e(__('forum.posts')) ?></span></div>
                                </div>

                                <div class="forum-forum-latest">
                                    <?php if (!empty($forum['last_topic_id'])): ?>
                                        <div class="small text-secondary mb-1"><?= e(__('forum.latest_activity')) ?></div>
                                        <?php if (forum_show_last_post_avatar_enabled() && !empty($forum['last_post_avatar'])): ?>
                                            <img src="<?= escape_url(user_avatar_url(['avatar' => $forum['last_post_avatar']])) ?>" alt="" class="forum-avatar forum-avatar-sm mb-2">
                                        <?php endif; ?>
                                        <a class="fw-semibold text-decoration-none d-block mb-1" href="<?= forum_topic_url((int)$forum['last_topic_id']) ?>">
                                            <?= e($forum['last_topic_title']) ?>
                                        </a>
                                        <div class="small text-secondary">
                                            <?php if (!empty($forum['last_post_user_id'])): ?>
                                                <a class="text-decoration-none" href="<?= user_profile_url((int)$forum['last_post_user_id']) ?>">
                                                    <?= e($forum['last_post_username'] ?: __('member.none')) ?>
                                                </a>
                                            <?php else: ?>
                                                <?= e($forum['last_post_username'] ?: __('member.guest')) ?>
                                            <?php endif; ?>
                                            · <?= e(format_dt($forum['last_post_at'])) ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="small text-secondary"><?= e(__('forum.no_activity')) ?></div>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
</div>
<?php include THEMES . setting('current_theme', CURRENT_THEME) . '/footer.php'; ?>
