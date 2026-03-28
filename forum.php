<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!function_exists('forum_get_index_data')) {
    include THEMES . setting('current_theme', CURRENT_THEME) . '/header.php';
    echo '<div class="alert alert-warning">Forumo infusion dar neįdiegta arba išjungta.</div>';
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
                        <p class="text-secondary mb-0">Diskusijos, klausimai, temos ir atsakymai vienoje vietoje. Temos palaiko BBCode ir smailus.</p>
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
                        <h2 class="h5 mb-1"><?= e($category['title']) ?></h2>
                        <?php if (!empty($category['description'])): ?>
                            <div class="small text-secondary"><?= e($category['description']) ?></div>
                        <?php endif; ?>
                    </div>
                    <span class="badge text-bg-secondary"><?= count($category['forums']) ?> forumai</span>
                </div>
                <div class="card-body p-0">
                    <?php if (!$category['forums']): ?>
                        <div class="p-4 text-secondary"><?= e(__('forum.forums.empty')) ?></div>
                    <?php else: ?>
                        <?php foreach ($category['forums'] as $forum): ?>
                            <article class="forum-forum-row">
                                <div class="forum-forum-main">
                                    <h3 class="h5 mb-1">
                                        <a class="text-decoration-none" href="<?= forum_forum_url((int)$forum['id']) ?>"><?= e($forum['title']) ?></a>
                                    </h3>
                                    <?php if (!empty($forum['description'])): ?>
                                        <p class="text-secondary mb-2"><?= e($forum['description']) ?></p>
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

                                <div class="forum-forum-stats">
                                    <div><strong><?= (int)$forum['topics_count'] ?></strong><span><?= e(__('forum.topics')) ?></span></div>
                                    <div><strong><?= (int)$forum['posts_count'] ?></strong><span><?= e(__('forum.posts')) ?></span></div>
                                </div>

                                <div class="forum-forum-latest">
                                    <?php if (!empty($forum['last_topic_id'])): ?>
                                        <div class="small text-secondary mb-1"><?= e(__('forum.latest_activity')) ?></div>
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
