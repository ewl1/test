<?php
$topics = forum_recent_topics(forum_panel_topics_limit());
?>
<div class="forum-panel">
    <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
        <strong>Paskutinės temos</strong>
        <a class="small text-decoration-none" href="<?= forum_index_url() ?>"><?= e(__('forum.all')) ?></a>
    </div>

    <?php if (!$topics): ?>
        <div class="text-secondary small">Forume temų dar nėra.</div>
    <?php else: ?>
        <div class="vstack gap-3">
            <?php foreach ($topics as $topic): ?>
                <article class="forum-panel-item">
                    <div class="d-flex gap-3 align-items-start">
                        <img src="<?= escape_url(user_avatar_url($topic)) ?>" alt="" class="forum-avatar forum-avatar-sm">
                        <div class="flex-grow-1 min-w-0">
                            <a class="fw-semibold text-decoration-none d-block forum-panel-topic-link" href="<?= forum_topic_url((int)$topic['id']) ?>">
                                <?= e($topic['title']) ?>
                            </a>
                            <div class="small text-secondary">
                                <?php if (!empty($topic['user_id'])): ?>
                                    <a class="text-decoration-none" href="<?= user_profile_url((int)$topic['user_id']) ?>"><?= e($topic['username'] ?? __('member.none')) ?></a>
                                <?php else: ?>
                                    <?= e($topic['username'] ?? __('member.guest')) ?>
                                <?php endif; ?>
                                · <?= e(format_dt($topic['last_post_at'] ?: $topic['created_at'])) ?>
                            </div>
                            <div class="small text-secondary"><?= e($topic['forum_title']) ?></div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
