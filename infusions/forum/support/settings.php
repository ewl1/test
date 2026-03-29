<?php
function forum_setting_defaults()
{
    return [
        'threads_per_page' => '12',
        'posts_per_page' => '10',
        'recent_threads_limit' => '5',
        'popular_thread_days' => '14',
        'show_latest_posts_below_reply_form' => '1',
        'show_reputation' => '1',
        'picture_style' => 'image',
        'thread_notification' => '0',
        'enable_ranks' => '1',
        'rank_style' => 'label',
        'max_photo_size_kb' => '2048',
        'attachments_max_size_kb' => '5120',
        'attachments_max_count' => '5',
        'allowed_file_types' => 'jpg,jpeg,png,gif,webp,pdf,txt,zip',
        'edit_time_limit_minutes' => '30',
        'show_ip_publicly' => '0',
        'show_last_post_avatar' => '1',
        'lock_edit' => '1',
        'update_time_on_edit' => '1',
    ];
}

function forum_setting($key, $default = null)
{
    $defaults = forum_setting_defaults();
    $fallback = array_key_exists($key, $defaults) ? $defaults[$key] : $default;
    return setting('forum_' . $key, $fallback);
}

function forum_save_setting($key, $value)
{
    return save_setting('forum_' . $key, (string)$value);
}

function forum_ensure_setting_defaults()
{
    foreach (forum_setting_defaults() as $key => $value) {
        if (setting('forum_' . $key, null) === null) {
            forum_save_setting($key, $value);
        }
    }
}

function forum_topics_per_page_setting()
{
    return max(5, min(100, (int)forum_setting('threads_per_page', '12')));
}

function forum_posts_per_page_setting()
{
    return max(5, min(100, (int)forum_setting('posts_per_page', '10')));
}

function forum_recent_threads_limit_setting()
{
    return max(1, min(20, (int)forum_setting('recent_threads_limit', '5')));
}

function forum_popular_thread_days_setting()
{
    return max(1, min(365, (int)forum_setting('popular_thread_days', '14')));
}

function forum_show_latest_posts_below_reply_form()
{
    return forum_setting('show_latest_posts_below_reply_form', '1') === '1';
}

function forum_show_reputation_enabled()
{
    return forum_setting('show_reputation', '1') === '1';
}

function forum_picture_style()
{
    $style = trim((string)forum_setting('picture_style', 'image'));
    return in_array($style, ['image', 'icon'], true) ? $style : 'image';
}

function forum_thread_notification_enabled()
{
    return forum_setting('thread_notification', '0') === '1';
}

function forum_ranks_enabled()
{
    return forum_setting('enable_ranks', '1') === '1';
}

function forum_rank_style()
{
    $style = trim((string)forum_setting('rank_style', 'label'));
    return in_array($style, ['label', 'image'], true) ? $style : 'label';
}

function forum_max_photo_size_bytes()
{
    return max(256, (int)forum_setting('max_photo_size_kb', '2048')) * 1024;
}

function forum_attachment_max_size_bytes()
{
    return max(256, (int)forum_setting('attachments_max_size_kb', '5120')) * 1024;
}

function forum_attachment_max_count()
{
    return max(0, min(20, (int)forum_setting('attachments_max_count', '5')));
}

function forum_attachment_allowed_extensions()
{
    $raw = trim((string)forum_setting('allowed_file_types', 'jpg,jpeg,png,gif,webp,pdf,txt,zip'));
    $parts = preg_split('/[\s,;]+/', $raw);
    $extensions = [];
    foreach ($parts as $part) {
        $part = strtolower(trim((string)$part));
        if ($part === '') {
            continue;
        }
        $extensions[$part] = true;
    }

    return array_keys($extensions);
}

function forum_edit_time_limit_minutes()
{
    return max(0, min(1440, (int)forum_setting('edit_time_limit_minutes', '30')));
}

function forum_show_ip_publicly()
{
    return forum_setting('show_ip_publicly', '0') === '1';
}

function forum_show_last_post_avatar_enabled()
{
    return forum_setting('show_last_post_avatar', '1') === '1';
}

function forum_lock_edit_enabled()
{
    return forum_setting('lock_edit', '1') === '1';
}

function forum_update_time_on_edit_enabled()
{
    return forum_setting('update_time_on_edit', '1') === '1';
}


function forum_save_settings(array $data)
{
    $allowedTypes = preg_split('/[\s,;]+/', (string)($data['allowed_file_types'] ?? ''));
    $normalizedAllowed = [];
    foreach ($allowedTypes as $allowedType) {
        $allowedType = strtolower(trim((string)$allowedType));
        if ($allowedType === '') {
            continue;
        }
        $normalizedAllowed[$allowedType] = true;
    }

    $values = [
        'threads_per_page' => max(5, min(100, (int)($data['threads_per_page'] ?? 12))),
        'posts_per_page' => max(5, min(100, (int)($data['posts_per_page'] ?? 10))),
        'recent_threads_limit' => max(1, min(20, (int)($data['recent_threads_limit'] ?? 5))),
        'popular_thread_days' => max(1, min(365, (int)($data['popular_thread_days'] ?? 14))),
        'show_latest_posts_below_reply_form' => !empty($data['show_latest_posts_below_reply_form']) ? '1' : '0',
        'show_reputation' => !empty($data['show_reputation']) ? '1' : '0',
        'picture_style' => (($data['picture_style'] ?? 'image') === 'icon') ? 'icon' : 'image',
        'thread_notification' => !empty($data['thread_notification']) ? '1' : '0',
        'enable_ranks' => !empty($data['enable_ranks']) ? '1' : '0',
        'rank_style' => (($data['rank_style'] ?? 'label') === 'image') ? 'image' : 'label',
        'max_photo_size_kb' => max(128, min(10240, (int)($data['max_photo_size_kb'] ?? 2048))),
        'attachments_max_size_kb' => max(128, min(51200, (int)($data['attachments_max_size_kb'] ?? 5120))),
        'attachments_max_count' => max(0, min(20, (int)($data['attachments_max_count'] ?? 5))),
        'allowed_file_types' => $normalizedAllowed ? implode(',', array_keys($normalizedAllowed)) : implode(',', forum_attachment_allowed_extensions()),
        'edit_time_limit_minutes' => max(0, min(1440, (int)($data['edit_time_limit_minutes'] ?? 30))),
        'show_ip_publicly' => !empty($data['show_ip_publicly']) ? '1' : '0',
        'show_last_post_avatar' => !empty($data['show_last_post_avatar']) ? '1' : '0',
        'lock_edit' => !empty($data['lock_edit']) ? '1' : '0',
        'update_time_on_edit' => !empty($data['update_time_on_edit']) ? '1' : '0',
    ];

    foreach ($values as $key => $value) {
        forum_save_setting($key, $value);
    }

    return [true, 'Forumo nustatymai išsaugoti.'];
}

