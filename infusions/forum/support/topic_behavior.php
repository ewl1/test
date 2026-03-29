<?php
function forum_is_edit_window_open($createdAt)
{
    $minutes = forum_edit_time_limit_minutes();
    if ($minutes <= 0) {
        return true;
    }

    $createdTs = strtotime((string)$createdAt);
    if (!$createdTs) {
        return false;
    }

    return (time() - $createdTs) <= ($minutes * 60);
}

function forum_can_edit_own_topic(array $topic)
{
    $user = current_user();
    if (!$user || (int)$user['id'] < 1 || (int)$topic['user_id'] !== (int)$user['id']) {
        return false;
    }

    if (!forum_lock_edit_enabled()) {
        return true;
    }

    return forum_is_edit_window_open($topic['created_at'] ?? null);
}

function forum_can_edit_own_reply(array $reply)
{
    $user = current_user();
    if (!$user || (int)$user['id'] < 1 || (int)$reply['user_id'] !== (int)$user['id']) {
        return false;
    }

    if (!forum_lock_edit_enabled()) {
        return true;
    }

    return forum_is_edit_window_open($reply['created_at'] ?? null);
}

function forum_get_participants($forumId, $limit = 12)
{
    $limit = max(1, min(50, (int)$limit));
    $forumId = (int)$forumId;

    $stmt = $GLOBALS['pdo']->query('
        SELECT u.id, u.username, u.avatar, MAX(x.activity_at) AS last_activity_at
        FROM (
            SELECT user_id, created_at AS activity_at FROM ' . forum_table_topics() . ' WHERE forum_id = ' . $forumId . ' AND user_id IS NOT NULL
            UNION ALL
            SELECT user_id, created_at AS activity_at FROM ' . forum_table_posts() . ' WHERE forum_id = ' . $forumId . ' AND user_id IS NOT NULL
        ) x
        INNER JOIN users u ON u.id = x.user_id
        GROUP BY u.id, u.username, u.avatar
        ORDER BY last_activity_at DESC
        LIMIT ' . $limit . '
    ');

    return $stmt->fetchAll();
}

function forum_is_popular_topic(array $topic)
{
    $thresholdDays = forum_popular_thread_days_setting();
    $createdTs = strtotime((string)($topic['created_at'] ?? ''));
    if (!$createdTs) {
        return false;
    }

    if ($createdTs < strtotime('-' . $thresholdDays . ' days')) {
        return false;
    }

    return ((int)($topic['views'] ?? 0) >= 10) || ((int)($topic['reply_count'] ?? 0) >= 8);
}

function forum_fetch_latest_reply_previews($topicId, $limit = 3)
{
    $limit = max(1, min(10, (int)$limit));
    $stmt = $GLOBALS['pdo']->prepare('
        SELECT p.*, u.username, u.avatar
        FROM ' . forum_table_posts() . ' p
        LEFT JOIN users u ON u.id = p.user_id
        WHERE p.topic_id = :topic_id
        ORDER BY p.created_at DESC, p.id DESC
        LIMIT ' . $limit . '
    ');
    $stmt->execute([':topic_id' => (int)$topicId]);
    return $stmt->fetchAll();
}

