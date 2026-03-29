<?php
function forum_table_category_meta()
{
    return 'infusion_forum_category_meta';
}

function forum_table_meta()
{
    return 'infusion_forum_meta';
}

function forum_table_ranks()
{
    return 'infusion_forum_ranks';
}

function forum_table_moods()
{
    return 'infusion_forum_moods';
}

function forum_table_attachments()
{
    return 'infusion_forum_attachments';
}

function forum_column_exists($table, $column)
{
    static $cache = [];
    $key = $table . '.' . $column;
    if (isset($cache[$key])) {
        return $cache[$key];
    }

    try {
        $stmt = $GLOBALS['pdo']->prepare('SHOW COLUMNS FROM `' . str_replace('`', '``', (string)$table) . '` LIKE :column');
        $stmt->execute([':column' => (string)$column]);
        $cache[$key] = (bool)$stmt->fetch();
    } catch (Throwable $e) {
        $cache[$key] = false;
    }

    return $cache[$key];
}

function forum_ensure_column($table, $column, $definition)
{
    if (forum_column_exists($table, $column)) {
        return;
    }

    try {
        $GLOBALS['pdo']->exec('ALTER TABLE `' . str_replace('`', '``', (string)$table) . '` ADD COLUMN ' . $definition);
    } catch (Throwable $e) {
    }
}

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

function forum_keyword_list($raw)
{
    $raw = preg_replace("/\r\n?/", "\n", (string)$raw);
    $parts = preg_split('/[\n,]+/', $raw);
    $keywords = [];
    foreach ($parts as $part) {
        $part = trim((string)$part);
        if ($part === '') {
            continue;
        }
        $keywords[mb_strtolower($part)] = $part;
    }

    return array_values($keywords);
}

function forum_keywords_to_storage($raw)
{
    $keywords = forum_keyword_list($raw);
    return $keywords ? implode("\n", $keywords) : '';
}

function forum_keywords_to_text($raw)
{
    return implode("\n", forum_keyword_list($raw));
}

function forum_format_meta_body($body)
{
    $body = trim((string)$body);
    if ($body === '') {
        return '';
    }

    return forum_format_body($body);
}

function forum_default_icon_for_type($type)
{
    return match ((string)$type) {
        'category' => 'fa-solid fa-folder-open',
        'help' => 'fa-solid fa-circle-question',
        default => 'fa-solid fa-comments',
    };
}

function forum_normalize_image_source($source)
{
    return in_array((string)$source, ['local', 'url'], true) ? (string)$source : 'local';
}

function forum_image_public_directory()
{
    return 'images/forum';
}

function forum_image_absolute_directory()
{
    return BASEDIR . forum_image_public_directory();
}

function forum_ensure_image_directory()
{
    $dir = forum_image_absolute_directory();
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

function forum_normalize_image_path($source, $path)
{
    $source = forum_normalize_image_source($source);
    $path = trim((string)$path);
    if ($path === '') {
        return '';
    }

    if ($source === 'url') {
        if (validate_url_value($path, true, 'Paveikslėlio nuoroda', ['http', 'https'], false) !== null) {
            return '';
        }

        return $path;
    }

    $path = str_replace('\\', '/', $path);
    $path = ltrim($path, '/');
    if ($path === '') {
        return '';
    }

    if (strpos($path, forum_image_public_directory() . '/') === 0) {
        return $path;
    }

    if (strpos($path, '/') === false) {
        return forum_image_public_directory() . '/' . $path;
    }

    return $path;
}

function forum_store_image_upload(array $file)
{
    [$ok, $validated] = validate_upload_file($file, [
        'required' => false,
        'profile' => 'image',
    ]);

    if (!$ok) {
        return [false, $validated];
    }

    if ($validated === null) {
        return [true, ''];
    }

    forum_ensure_image_directory();

    $filename = $validated['safe_name'] . '-' . bin2hex(random_bytes(6)) . '.' . $validated['extension'];
    $relative = forum_image_public_directory() . '/' . $filename;
    $target = forum_image_absolute_directory() . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($validated['tmp_name'], $target)) {
        return [false, 'Nepavyko išsaugoti forumo paveikslėlio.'];
    }

    return [true, $relative];
}

function forum_node_image_url(array $node)
{
    $source = forum_normalize_image_source($node['image_source'] ?? 'local');
    $path = trim((string)($node['image_path'] ?? ''));
    if ($path === '') {
        return '';
    }

    if ($source === 'url') {
        return $path;
    }

    return public_path(ltrim($path, '/'));
}

function forum_meta_defaults()
{
    return [
        'keywords' => '',
        'rules_content' => '',
        'icon_class' => '',
        'image_source' => 'local',
        'image_path' => '',
        'forum_type' => 'forum',
        'is_locked' => 0,
        'show_participants' => 1,
        'enable_quick_reply' => 1,
        'enable_post_merge' => 0,
        'allow_attachments' => 0,
        'enable_polls' => 0,
        'copy_settings_from' => null,
    ];
}

function forum_get_category_meta_map(array $categoryIds)
{
    $categoryIds = array_values(array_filter(array_map('intval', $categoryIds)));
    if (!$categoryIds) {
        return [];
    }

    $sql = 'SELECT * FROM ' . forum_table_category_meta() . ' WHERE category_id IN (' . implode(',', $categoryIds) . ')';
    $rows = $GLOBALS['pdo']->query($sql)->fetchAll();
    $result = [];
    foreach ($rows as $row) {
        $result[(int)$row['category_id']] = $row;
    }

    return $result;
}

function forum_get_forum_meta_map(array $forumIds)
{
    $forumIds = array_values(array_filter(array_map('intval', $forumIds)));
    if (!$forumIds) {
        return [];
    }

    $sql = 'SELECT * FROM ' . forum_table_meta() . ' WHERE forum_id IN (' . implode(',', $forumIds) . ')';
    $rows = $GLOBALS['pdo']->query($sql)->fetchAll();
    $result = [];
    foreach ($rows as $row) {
        $result[(int)$row['forum_id']] = $row;
    }

    return $result;
}

function forum_apply_category_meta(array $category, array $meta = [])
{
    $category['description_html'] = forum_format_meta_body($category['description'] ?? '');
    $category['keywords'] = forum_keywords_to_text($meta['keywords'] ?? '');
    $category['keywords_list'] = forum_keyword_list($meta['keywords'] ?? '');
    $category['rules_content'] = (string)($meta['rules_content'] ?? '');
    $category['rules_html'] = forum_format_meta_body($category['rules_content']);
    $category['icon_class'] = trim((string)($meta['icon_class'] ?? forum_default_icon_for_type('category')));
    $category['image_source'] = forum_normalize_image_source($meta['image_source'] ?? 'local');
    $category['image_path'] = (string)($meta['image_path'] ?? '');
    $category['image_url'] = forum_node_image_url($category);

    return $category;
}

function forum_apply_forum_meta(array $forum, array $meta = [])
{
    $defaults = forum_meta_defaults();
    $meta = array_merge($defaults, $meta);
    $forum['description_html'] = forum_format_meta_body($forum['description'] ?? '');
    $forum['keywords'] = forum_keywords_to_text($meta['keywords']);
    $forum['keywords_list'] = forum_keyword_list($meta['keywords']);
    $forum['rules_content'] = (string)$meta['rules_content'];
    $forum['rules_html'] = forum_format_meta_body($forum['rules_content']);
    $forum['icon_class'] = trim((string)($meta['icon_class'] ?: forum_default_icon_for_type($meta['forum_type'])));
    $forum['image_source'] = forum_normalize_image_source($meta['image_source']);
    $forum['image_path'] = (string)$meta['image_path'];
    $forum['image_url'] = forum_node_image_url($forum);
    $forum['forum_type'] = in_array((string)$meta['forum_type'], ['forum', 'help'], true) ? (string)$meta['forum_type'] : 'forum';
    $forum['is_locked'] = (int)!empty($meta['is_locked']);
    $forum['show_participants'] = (int)!empty($meta['show_participants']);
    $forum['enable_quick_reply'] = (int)!empty($meta['enable_quick_reply']);
    $forum['enable_post_merge'] = (int)!empty($meta['enable_post_merge']);
    $forum['allow_attachments'] = (int)!empty($meta['allow_attachments']);
    $forum['enable_polls'] = (int)!empty($meta['enable_polls']);
    $forum['copy_settings_from'] = !empty($meta['copy_settings_from']) ? (int)$meta['copy_settings_from'] : null;

    return $forum;
}

function forum_get_forum_meta($forumId)
{
    $map = forum_get_forum_meta_map([(int)$forumId]);
    return $map[(int)$forumId] ?? [];
}

function forum_save_category_meta($categoryId, array $data)
{
    $stmt = $GLOBALS['pdo']->prepare('
        INSERT INTO ' . forum_table_category_meta() . ' (category_id, keywords, rules_content, icon_class, image_source, image_path, updated_at)
        VALUES (:category_id, :keywords, :rules_content, :icon_class, :image_source, :image_path, NOW())
        ON DUPLICATE KEY UPDATE
            keywords = VALUES(keywords),
            rules_content = VALUES(rules_content),
            icon_class = VALUES(icon_class),
            image_source = VALUES(image_source),
            image_path = VALUES(image_path),
            updated_at = NOW()
    ');

    $stmt->execute([
        ':category_id' => (int)$categoryId,
        ':keywords' => forum_keywords_to_storage($data['keywords'] ?? ''),
        ':rules_content' => trim((string)($data['rules_content'] ?? '')),
        ':icon_class' => trim((string)($data['icon_class'] ?? '')),
        ':image_source' => forum_normalize_image_source($data['image_source'] ?? 'local'),
        ':image_path' => forum_normalize_image_path($data['image_source'] ?? 'local', $data['image_path'] ?? ''),
    ]);
}

function forum_save_forum_meta($forumId, array $data)
{
    $stmt = $GLOBALS['pdo']->prepare('
        INSERT INTO ' . forum_table_meta() . ' (
            forum_id, keywords, rules_content, icon_class, image_source, image_path, forum_type,
            is_locked, show_participants, enable_quick_reply, enable_post_merge, allow_attachments,
            enable_polls, copy_settings_from, updated_at
        ) VALUES (
            :forum_id, :keywords, :rules_content, :icon_class, :image_source, :image_path, :forum_type,
            :is_locked, :show_participants, :enable_quick_reply, :enable_post_merge, :allow_attachments,
            :enable_polls, :copy_settings_from, NOW()
        )
        ON DUPLICATE KEY UPDATE
            keywords = VALUES(keywords),
            rules_content = VALUES(rules_content),
            icon_class = VALUES(icon_class),
            image_source = VALUES(image_source),
            image_path = VALUES(image_path),
            forum_type = VALUES(forum_type),
            is_locked = VALUES(is_locked),
            show_participants = VALUES(show_participants),
            enable_quick_reply = VALUES(enable_quick_reply),
            enable_post_merge = VALUES(enable_post_merge),
            allow_attachments = VALUES(allow_attachments),
            enable_polls = VALUES(enable_polls),
            copy_settings_from = VALUES(copy_settings_from),
            updated_at = NOW()
    ');

    $stmt->execute([
        ':forum_id' => (int)$forumId,
        ':keywords' => forum_keywords_to_storage($data['keywords'] ?? ''),
        ':rules_content' => trim((string)($data['rules_content'] ?? '')),
        ':icon_class' => trim((string)($data['icon_class'] ?? '')),
        ':image_source' => forum_normalize_image_source($data['image_source'] ?? 'local'),
        ':image_path' => forum_normalize_image_path($data['image_source'] ?? 'local', $data['image_path'] ?? ''),
        ':forum_type' => in_array((string)($data['forum_type'] ?? 'forum'), ['forum', 'help'], true) ? (string)$data['forum_type'] : 'forum',
        ':is_locked' => !empty($data['is_locked']) ? 1 : 0,
        ':show_participants' => !empty($data['show_participants']) ? 1 : 0,
        ':enable_quick_reply' => !empty($data['enable_quick_reply']) ? 1 : 0,
        ':enable_post_merge' => !empty($data['enable_post_merge']) ? 1 : 0,
        ':allow_attachments' => !empty($data['allow_attachments']) ? 1 : 0,
        ':enable_polls' => !empty($data['enable_polls']) ? 1 : 0,
        ':copy_settings_from' => !empty($data['copy_settings_from']) ? (int)$data['copy_settings_from'] : null,
    ]);
}

function forum_apply_forum_copy_settings(&$data, $sourceForumId)
{
    $sourceForumId = (int)$sourceForumId;
    if ($sourceForumId < 1) {
        return;
    }

    $meta = forum_get_forum_meta($sourceForumId);
    if (!$meta) {
        return;
    }

    foreach (['keywords', 'icon_class', 'image_source', 'image_path', 'forum_type', 'is_locked', 'show_participants', 'enable_quick_reply', 'enable_post_merge', 'allow_attachments', 'enable_polls', 'rules_content'] as $field) {
        if (empty($data[$field]) && isset($meta[$field])) {
            $data[$field] = $meta[$field];
        }
    }
}

function forum_get_moods($activeOnly = false)
{
    $sql = 'SELECT * FROM ' . forum_table_moods();
    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY sort_order ASC, id ASC';

    return $GLOBALS['pdo']->query($sql)->fetchAll();
}

function forum_get_mood($moodId)
{
    $stmt = $GLOBALS['pdo']->prepare('SELECT * FROM ' . forum_table_moods() . ' WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => (int)$moodId]);
    return $stmt->fetch() ?: null;
}

function forum_save_mood(array $data)
{
    $id = (int)($data['id'] ?? 0);
    $title = trim((string)($data['title'] ?? ''));
    $icon = trim((string)($data['icon_class'] ?? ''));
    $sortOrder = (int)($data['sort_order'] ?? 0);
    $isActive = !empty($data['is_active']) ? 1 : 0;

    if (mb_strlen($title) < 2 || mb_strlen($title) > 100) {
        return [false, 'Nuotaikos pavadinimas turi būti nuo 2 iki 100 simbolių.'];
    }

    $slug = forum_make_slug($data['slug'] ?? $title, 'nuotaika');
    $stmt = $GLOBALS['pdo']->prepare(
        $id > 0
            ? 'UPDATE ' . forum_table_moods() . ' SET title = :title, slug = :slug, icon_class = :icon_class, sort_order = :sort_order, is_active = :is_active, updated_at = NOW() WHERE id = :id'
            : 'INSERT INTO ' . forum_table_moods() . ' (title, slug, icon_class, sort_order, is_active, created_at, updated_at) VALUES (:title, :slug, :icon_class, :sort_order, :is_active, NOW(), NOW())'
    );

    $params = [
        ':title' => $title,
        ':slug' => $slug,
        ':icon_class' => $icon,
        ':sort_order' => $sortOrder,
        ':is_active' => $isActive,
    ];
    if ($id > 0) {
        $params[':id'] = $id;
    }

    $stmt->execute($params);
    return [true, $id > 0 ? 'Forumo nuotaika atnaujinta.' : 'Forumo nuotaika sukurta.'];
}

function forum_delete_mood($moodId)
{
    $stmt = $GLOBALS['pdo']->prepare('DELETE FROM ' . forum_table_moods() . ' WHERE id = :id');
    $stmt->execute([':id' => (int)$moodId]);
    return [true, 'Forumo nuotaika ištrinta.'];
}

function forum_get_ranks($activeOnly = false)
{
    $sql = 'SELECT * FROM ' . forum_table_ranks();
    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY min_posts ASC, sort_order ASC, id ASC';

    return $GLOBALS['pdo']->query($sql)->fetchAll();
}

function forum_save_rank(array $data, array $file = [])
{
    $id = (int)($data['id'] ?? 0);
    $title = trim((string)($data['title'] ?? ''));
    $minPosts = max(0, (int)($data['min_posts'] ?? 0));
    $icon = trim((string)($data['icon_class'] ?? ''));
    $sortOrder = (int)($data['sort_order'] ?? 0);
    $isActive = !empty($data['is_active']) ? 1 : 0;
    $imagePath = trim((string)($data['existing_image_path'] ?? ''));

    if (mb_strlen($title) < 2 || mb_strlen($title) > 100) {
        return [false, 'Forumo rango pavadinimas turi būti nuo 2 iki 100 simbolių.'];
    }

    if (!empty($file) && (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        [$ok, $stored] = forum_store_image_upload($file);
        if (!$ok) {
            return [false, $stored];
        }
        if ($stored !== '') {
            $imagePath = $stored;
        }
    }

    $slug = forum_make_slug($data['slug'] ?? $title, 'rangas');
    $stmt = $GLOBALS['pdo']->prepare(
        $id > 0
            ? 'UPDATE ' . forum_table_ranks() . ' SET title = :title, slug = :slug, min_posts = :min_posts, icon_class = :icon_class, image_path = :image_path, sort_order = :sort_order, is_active = :is_active, updated_at = NOW() WHERE id = :id'
            : 'INSERT INTO ' . forum_table_ranks() . ' (title, slug, min_posts, icon_class, image_path, sort_order, is_active, created_at, updated_at) VALUES (:title, :slug, :min_posts, :icon_class, :image_path, :sort_order, :is_active, NOW(), NOW())'
    );

    $params = [
        ':title' => $title,
        ':slug' => $slug,
        ':min_posts' => $minPosts,
        ':icon_class' => $icon,
        ':image_path' => $imagePath !== '' ? $imagePath : null,
        ':sort_order' => $sortOrder,
        ':is_active' => $isActive,
    ];
    if ($id > 0) {
        $params[':id'] = $id;
    }
    $stmt->execute($params);

    return [true, $id > 0 ? 'Forumo rangas atnaujintas.' : 'Forumo rangas sukurtas.'];
}

function forum_delete_rank($rankId)
{
    $stmt = $GLOBALS['pdo']->prepare('DELETE FROM ' . forum_table_ranks() . ' WHERE id = :id');
    $stmt->execute([':id' => (int)$rankId]);
    return [true, 'Forumo rangas ištrintas.'];
}

function forum_resolve_rank_for_user($userId)
{
    if (!forum_ranks_enabled()) {
        return null;
    }

    $count = count_user_forum_messages((int)$userId);
    $rank = null;
    foreach (forum_get_ranks(true) as $candidate) {
        if ($count >= (int)$candidate['min_posts']) {
            $rank = $candidate;
        }
    }

    return $rank;
}

function forum_render_rank_badge($userId)
{
    $userId = (int)$userId;
    if ($userId < 1) {
        return '';
    }

    $rank = forum_resolve_rank_for_user($userId);
    if (!$rank) {
        return '';
    }

    if (forum_rank_style() === 'image' && !empty($rank['image_path'])) {
        return '<span class="forum-rank-badge forum-rank-badge-image"><img src="' . escape_url(public_path(ltrim((string)$rank['image_path'], '/'))) . '" alt="' . e($rank['title']) . '"></span>';
    }

    $icon = trim((string)($rank['icon_class'] ?? ''));
    $iconHtml = $icon !== '' ? '<i class="' . e($icon) . '" aria-hidden="true"></i> ' : '';
    return '<span class="forum-rank-badge forum-rank-badge-label">' . $iconHtml . e($rank['title']) . '</span>';
}

function forum_render_node_visual(array $node, $class = 'forum-node-visual')
{
    $pictureStyle = forum_picture_style();
    $imageUrl = trim((string)($node['image_url'] ?? ''));
    $iconClass = trim((string)($node['icon_class'] ?? forum_default_icon_for_type($node['forum_type'] ?? 'forum')));

    if ($pictureStyle === 'image' && $imageUrl !== '') {
        return '<span class="' . e($class) . ' forum-node-visual-image"><img src="' . escape_url($imageUrl) . '" alt=""></span>';
    }

    return '<span class="' . e($class) . ' forum-node-visual-icon"><i class="' . e($iconClass !== '' ? $iconClass : 'fa-solid fa-comments') . '" aria-hidden="true"></i></span>';
}

function forum_render_mood_badge($moodId)
{
    $moodId = (int)$moodId;
    if ($moodId < 1) {
        return '';
    }

    $mood = forum_get_mood($moodId);
    if (!$mood || empty($mood['is_active'])) {
        return '';
    }

    $icon = trim((string)($mood['icon_class'] ?? ''));
    return '<span class="badge text-bg-info forum-mood-badge">' . ($icon !== '' ? '<i class="' . e($icon) . '" aria-hidden="true"></i> ' : '') . e($mood['title']) . '</span>';
}

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

function forum_normalize_upload_array(array $files)
{
    if (!isset($files['name']) || !is_array($files['name'])) {
        return [];
    }

    $normalized = [];
    foreach (array_keys($files['name']) as $index) {
        $normalized[] = [
            'name' => $files['name'][$index] ?? '',
            'type' => $files['type'][$index] ?? '',
            'tmp_name' => $files['tmp_name'][$index] ?? '',
            'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$index] ?? 0,
        ];
    }

    return $normalized;
}

function forum_attachment_directory_absolute()
{
    return BASEDIR . 'uploads/forum';
}

function forum_attachment_directory_public()
{
    return 'uploads/forum';
}

function forum_attachment_allowed_mime_map()
{
    return [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'gif' => ['image/gif'],
        'webp' => ['image/webp'],
        'pdf' => ['application/pdf'],
        'txt' => ['text/plain'],
        'zip' => ['application/zip', 'application/x-zip-compressed', 'multipart/x-zip'],
    ];
}

function forum_validate_attachment_file(array $file)
{
    if ((int)($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [true, null];
    }

    if ((int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return [false, upload_error_message((int)$file['error'])];
    }

    if (!is_uploaded_file((string)$file['tmp_name'])) {
        return [false, 'Įkeltas failas neatpažintas kaip saugus upload failas.'];
    }

    $size = (int)($file['size'] ?? 0);
    if ($size <= 0) {
        return [false, 'Prisegtas failas yra tuščias.'];
    }
    if ($size > forum_attachment_max_size_bytes()) {
        return [false, 'Prisegtas failas viršija maksimalų leistiną dydį.'];
    }

    $originalName = trim((string)($file['name'] ?? 'file'));
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if ($extension === '') {
        return [false, 'Prisegtas failas privalo turėti plėtinį.'];
    }

    $allowed = forum_attachment_allowed_extensions();
    if ($allowed && !in_array($extension, $allowed, true)) {
        return [false, 'Šio tipo failo prisegti negalima.'];
    }

    $mimeType = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mimeType = (string)finfo_file($finfo, (string)$file['tmp_name']);
            finfo_close($finfo);
        }
    }

    $mimeMap = forum_attachment_allowed_mime_map();
    if (isset($mimeMap[$extension]) && $mimeType !== '' && !in_array($mimeType, $mimeMap[$extension], true)) {
        return [false, 'Failo MIME tipas neatitinka jo plėtinio.'];
    }

    $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
    if ($isImage) {
        if ($size > forum_max_photo_size_bytes()) {
            return [false, 'Paveikslėlis viršija maksimalų leistiną nuotraukos dydį.'];
        }
        if (@getimagesize((string)$file['tmp_name']) === false) {
            return [false, 'Paveikslėlio failas neatpažintas kaip galiojantis paveikslėlis.'];
        }
    }

    $safeBase = normalize_slug(pathinfo($originalName, PATHINFO_FILENAME));
    if ($safeBase === '') {
        $safeBase = 'attachment';
    }

    return [true, [
        'original_name' => $originalName,
        'extension' => $extension,
        'mime_type' => $mimeType,
        'size' => $size,
        'is_image' => $isImage,
        'safe_base' => $safeBase,
        'tmp_name' => (string)$file['tmp_name'],
    ]];
}

function forum_store_attachment_files($forumId, $topicId, $postId, $userId, array $files)
{
    $normalized = forum_normalize_upload_array($files);
    if (!$normalized) {
        return [true, []];
    }

    if (count($normalized) > forum_attachment_max_count()) {
        return [false, 'Pridėta per daug prisegtų failų vienam pranešimui.'];
    }

    $baseDir = forum_attachment_directory_absolute();
    $relativeBase = forum_attachment_directory_public();
    $subdir = date('Y/m');
    $absoluteDir = $baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $subdir);
    if (!is_dir($absoluteDir) && !@mkdir($absoluteDir, 0755, true) && !is_dir($absoluteDir)) {
        return [false, 'Nepavyko sukurti prisegtų failų katalogo.'];
    }

    $saved = [];
    foreach ($normalized as $file) {
        [$ok, $validated] = forum_validate_attachment_file($file);
        if (!$ok) {
            foreach ($saved as $attachment) {
                @unlink(BASEDIR . $attachment['stored_name']);
            }
            return [false, $validated];
        }
        if ($validated === null) {
            continue;
        }

        $storedName = $relativeBase . '/' . $subdir . '/' . $validated['safe_base'] . '-' . bin2hex(random_bytes(6)) . '.' . $validated['extension'];
        $absolutePath = BASEDIR . str_replace('/', DIRECTORY_SEPARATOR, $storedName);
        if (!move_uploaded_file($validated['tmp_name'], $absolutePath)) {
            foreach ($saved as $attachment) {
                @unlink(BASEDIR . $attachment['stored_name']);
            }
            return [false, 'Nepavyko išsaugoti prisegto failo.'];
        }

        $saved[] = [
            'forum_id' => (int)$forumId,
            'topic_id' => (int)$topicId,
            'post_id' => $postId !== null ? (int)$postId : null,
            'user_id' => (int)$userId,
            'original_name' => $validated['original_name'],
            'stored_name' => $storedName,
            'mime_type' => $validated['mime_type'],
            'file_ext' => $validated['extension'],
            'file_size' => $validated['size'],
            'is_image' => $validated['is_image'] ? 1 : 0,
        ];
    }

    if (!$saved) {
        return [true, []];
    }

    $stmt = $GLOBALS['pdo']->prepare('
        INSERT INTO ' . forum_table_attachments() . ' (
            forum_id, topic_id, post_id, user_id, original_name, stored_name, mime_type, file_ext, file_size, is_image, created_at
        ) VALUES (
            :forum_id, :topic_id, :post_id, :user_id, :original_name, :stored_name, :mime_type, :file_ext, :file_size, :is_image, NOW()
        )
    ');

    foreach ($saved as $attachment) {
        $stmt->execute([
            ':forum_id' => $attachment['forum_id'],
            ':topic_id' => $attachment['topic_id'],
            ':post_id' => $attachment['post_id'],
            ':user_id' => $attachment['user_id'],
            ':original_name' => $attachment['original_name'],
            ':stored_name' => $attachment['stored_name'],
            ':mime_type' => $attachment['mime_type'],
            ':file_ext' => $attachment['file_ext'],
            ':file_size' => $attachment['file_size'],
            ':is_image' => $attachment['is_image'],
        ]);
    }

    return [true, $saved];
}

function forum_get_attachments_for_topic($topicId)
{
    $stmt = $GLOBALS['pdo']->prepare('SELECT * FROM ' . forum_table_attachments() . ' WHERE topic_id = :topic_id AND post_id IS NULL ORDER BY id ASC');
    $stmt->execute([':topic_id' => (int)$topicId]);
    return $stmt->fetchAll();
}

function forum_get_attachments_for_posts(array $postIds)
{
    $postIds = array_values(array_filter(array_map('intval', $postIds)));
    if (!$postIds) {
        return [];
    }

    $sql = 'SELECT * FROM ' . forum_table_attachments() . ' WHERE post_id IN (' . implode(',', $postIds) . ') ORDER BY id ASC';
    $rows = $GLOBALS['pdo']->query($sql)->fetchAll();
    $result = [];
    foreach ($rows as $row) {
        $result[(int)$row['post_id']][] = $row;
    }

    return $result;
}

function forum_delete_attachment_records(array $attachments)
{
    foreach ($attachments as $attachment) {
        if (!empty($attachment['stored_name'])) {
            @unlink(BASEDIR . str_replace('/', DIRECTORY_SEPARATOR, (string)$attachment['stored_name']));
        }
    }
}

function forum_delete_attachments_for_topic($topicId)
{
    $attachments = forum_get_attachments_for_topic((int)$topicId);
    $postIds = $GLOBALS['pdo']->query('SELECT id FROM ' . forum_table_posts() . ' WHERE topic_id = ' . (int)$topicId)->fetchAll(PDO::FETCH_COLUMN);
    $replyMap = forum_get_attachments_for_posts(array_map('intval', $postIds));
    foreach ($replyMap as $rows) {
        $attachments = array_merge($attachments, $rows);
    }
    forum_delete_attachment_records($attachments);
    $stmt = $GLOBALS['pdo']->prepare('DELETE FROM ' . forum_table_attachments() . ' WHERE topic_id = :topic_id');
    $stmt->execute([':topic_id' => (int)$topicId]);
}

function forum_delete_attachments_for_post($postId)
{
    $stmt = $GLOBALS['pdo']->prepare('SELECT * FROM ' . forum_table_attachments() . ' WHERE post_id = :post_id');
    $stmt->execute([':post_id' => (int)$postId]);
    $attachments = $stmt->fetchAll();
    forum_delete_attachment_records($attachments);
    $delete = $GLOBALS['pdo']->prepare('DELETE FROM ' . forum_table_attachments() . ' WHERE post_id = :post_id');
    $delete->execute([':post_id' => (int)$postId]);
}

function forum_render_attachments(array $attachments)
{
    if (!$attachments) {
        return '';
    }

    $html = '<div class="forum-attachments">';
    foreach ($attachments as $attachment) {
        $url = public_path(ltrim((string)$attachment['stored_name'], '/'));
        $name = e((string)$attachment['original_name']);
        if (!empty($attachment['is_image'])) {
            $html .= '<a class="forum-attachment forum-attachment-image" href="' . escape_url($url) . '" target="_blank" rel="noopener"><img src="' . escape_url($url) . '" alt="' . $name . '"><span>' . $name . '</span></a>';
        } else {
            $html .= '<a class="forum-attachment forum-attachment-file" href="' . escape_url($url) . '" target="_blank" rel="noopener"><i class="fa-solid fa-paperclip" aria-hidden="true"></i><span>' . $name . '</span></a>';
        }
    }
    $html .= '</div>';

    return $html;
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

function forum_admin_create_node(array $data, array $files = [])
{
    forum_ensure_schema();
    forum_ensure_extended_schema();

    $nodeType = (string)($data['node_type'] ?? 'forum');
    $title = trim((string)($data['title'] ?? ''));
    $description = forum_prepare_body($data['description'] ?? '', 5000);
    $slugInput = trim((string)($data['slug'] ?? ''));
    $sortOrder = (int)($data['sort_order'] ?? 0);
    $keywords = $data['keywords'] ?? '';
    $rulesContent = forum_prepare_body($data['rules_content'] ?? '', 5000);
    $iconClass = trim((string)($data['icon_class'] ?? ''));
    $imageSource = forum_normalize_image_source($data['image_source'] ?? 'local');
    $imagePath = trim((string)($data['image_path'] ?? ''));
    $copySettingsFrom = (int)($data['copy_settings_from'] ?? 0);

    if (mb_strlen($title) < 2 || mb_strlen($title) > 190) {
        return [false, 'Pavadinimas turi būti nuo 2 iki 190 simbolių.', null];
    }

    if ($slugInput !== '' && validate_slug_value($slugInput, 'Forumo alias', false, 2, 190) !== null) {
        return [false, 'Forumo alias turi būti sudarytas tik iš mažųjų raidžių, skaičių ir brūkšnelių.', null];
    }

    [$imageOk, $storedImagePath] = forum_store_image_upload($files['forum_image'] ?? []);
    if (!$imageOk) {
        return [false, $storedImagePath, null];
    }
    if ($storedImagePath !== '') {
        $imageSource = 'local';
        $imagePath = $storedImagePath;
    }

    if ($nodeType === 'category') {
        $slug = forum_unique_slug(forum_table_categories(), $slugInput !== '' ? $slugInput : $title, 'kategorija');
        $stmt = $GLOBALS['pdo']->prepare('
            INSERT INTO ' . forum_table_categories() . ' (title, slug, description, sort_order, is_active, created_at)
            VALUES (:title, :slug, :description, :sort_order, 1, NOW())
        ');
        $stmt->execute([
            ':title' => $title,
            ':slug' => $slug,
            ':description' => $description !== '' ? $description : null,
            ':sort_order' => $sortOrder,
        ]);

        $categoryId = (int)$GLOBALS['pdo']->lastInsertId();
        forum_save_category_meta($categoryId, [
            'keywords' => $keywords,
            'rules_content' => $rulesContent,
            'icon_class' => $iconClass !== '' ? $iconClass : forum_default_icon_for_type('category'),
            'image_source' => $imageSource,
            'image_path' => $imagePath,
        ]);

        audit_log(current_user()['id'] ?? null, 'forum_category_create', forum_table_categories(), $categoryId, ['title' => $title]);
        return [true, 'Forumo kategorija sukurta.', $categoryId];
    }

    $categoryId = (int)($data['category_id'] ?? 0);
    $parentId = (int)($data['parent_id'] ?? 0);

    if ($copySettingsFrom > 0) {
        forum_apply_forum_copy_settings($data, $copySettingsFrom);
        $keywords = $data['keywords'] ?? $keywords;
        $rulesContent = forum_prepare_body($data['rules_content'] ?? $rulesContent, 5000);
        $iconClass = trim((string)($data['icon_class'] ?? $iconClass));
        $imageSource = forum_normalize_image_source($data['image_source'] ?? $imageSource);
        $imagePath = trim((string)($data['image_path'] ?? $imagePath));
    }

    if ($categoryId < 1) {
        return [false, 'Pasirinkite forumo kategoriją.', null];
    }

    if ($parentId > 0) {
        $parent = forum_get_forum($parentId);
        if (!$parent) {
            return [false, 'Pasirinktas tėvinis forumas nerastas.', null];
        }
        $categoryId = (int)$parent['category_id'];
    }

    $slug = forum_unique_slug(forum_table_forums(), $slugInput !== '' ? $slugInput : $title, 'forumas');
    $stmt = $GLOBALS['pdo']->prepare('
        INSERT INTO ' . forum_table_forums() . ' (category_id, parent_id, title, slug, description, sort_order, is_active, created_at)
        VALUES (:category_id, :parent_id, :title, :slug, :description, :sort_order, 1, NOW())
    ');
    $stmt->execute([
        ':category_id' => $categoryId,
        ':parent_id' => $parentId > 0 ? $parentId : null,
        ':title' => $title,
        ':slug' => $slug,
        ':description' => $description !== '' ? $description : null,
        ':sort_order' => $sortOrder,
    ]);

    $forumId = (int)$GLOBALS['pdo']->lastInsertId();
    forum_save_forum_meta($forumId, [
        'keywords' => $keywords,
        'rules_content' => $rulesContent,
        'icon_class' => $iconClass !== '' ? $iconClass : forum_default_icon_for_type($data['forum_type'] ?? 'forum'),
        'image_source' => $imageSource,
        'image_path' => $imagePath,
        'forum_type' => $data['forum_type'] ?? 'forum',
        'is_locked' => !empty($data['is_locked']),
        'show_participants' => !empty($data['show_participants']),
        'enable_quick_reply' => !empty($data['enable_quick_reply']),
        'enable_post_merge' => !empty($data['enable_post_merge']),
        'allow_attachments' => !empty($data['allow_attachments']),
        'enable_polls' => !empty($data['enable_polls']),
        'copy_settings_from' => $copySettingsFrom > 0 ? $copySettingsFrom : null,
    ]);

    audit_log(current_user()['id'] ?? null, 'forum_forum_create', forum_table_forums(), $forumId, ['title' => $title]);
    return [true, 'Forumas sukurtas.', $forumId];
}

function forum_ensure_extended_schema()
{
    static $ensured = false;
    if ($ensured) {
        return;
    }

    $ensured = true;

    $GLOBALS['pdo']->exec("
        CREATE TABLE IF NOT EXISTS " . forum_table_category_meta() . " (
            category_id INT UNSIGNED NOT NULL PRIMARY KEY,
            keywords TEXT NULL,
            rules_content MEDIUMTEXT NULL,
            icon_class VARCHAR(120) NULL,
            image_source ENUM('local','url') NOT NULL DEFAULT 'local',
            image_path VARCHAR(255) NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $GLOBALS['pdo']->exec("
        CREATE TABLE IF NOT EXISTS " . forum_table_meta() . " (
            forum_id INT UNSIGNED NOT NULL PRIMARY KEY,
            keywords TEXT NULL,
            rules_content MEDIUMTEXT NULL,
            icon_class VARCHAR(120) NULL,
            image_source ENUM('local','url') NOT NULL DEFAULT 'local',
            image_path VARCHAR(255) NULL,
            forum_type ENUM('forum','help') NOT NULL DEFAULT 'forum',
            is_locked TINYINT(1) NOT NULL DEFAULT 0,
            show_participants TINYINT(1) NOT NULL DEFAULT 1,
            enable_quick_reply TINYINT(1) NOT NULL DEFAULT 1,
            enable_post_merge TINYINT(1) NOT NULL DEFAULT 0,
            allow_attachments TINYINT(1) NOT NULL DEFAULT 0,
            enable_polls TINYINT(1) NOT NULL DEFAULT 0,
            copy_settings_from INT UNSIGNED NULL DEFAULT NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $GLOBALS['pdo']->exec("
        CREATE TABLE IF NOT EXISTS " . forum_table_ranks() . " (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(100) NOT NULL,
            slug VARCHAR(120) NOT NULL,
            min_posts INT UNSIGNED NOT NULL DEFAULT 0,
            icon_class VARCHAR(120) NULL,
            image_path VARCHAR(255) NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_forum_rank_slug (slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $GLOBALS['pdo']->exec("
        CREATE TABLE IF NOT EXISTS " . forum_table_moods() . " (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(100) NOT NULL,
            slug VARCHAR(120) NOT NULL,
            icon_class VARCHAR(120) NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_forum_mood_slug (slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $GLOBALS['pdo']->exec("
        CREATE TABLE IF NOT EXISTS " . forum_table_attachments() . " (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            forum_id INT UNSIGNED NOT NULL,
            topic_id INT UNSIGNED NOT NULL,
            post_id INT UNSIGNED NULL DEFAULT NULL,
            user_id INT UNSIGNED NULL DEFAULT NULL,
            original_name VARCHAR(255) NOT NULL,
            stored_name VARCHAR(255) NOT NULL,
            mime_type VARCHAR(150) NULL,
            file_ext VARCHAR(20) NULL,
            file_size INT UNSIGNED NOT NULL DEFAULT 0,
            is_image TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_forum_attachment_topic (topic_id, post_id, id),
            KEY idx_forum_attachment_forum (forum_id, id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    forum_ensure_column(forum_table_topics(), 'mood_id', 'mood_id INT UNSIGNED NULL DEFAULT NULL AFTER is_pinned');
    forum_ensure_column(forum_table_topics(), 'ip_address', 'ip_address VARCHAR(45) NULL AFTER last_post_user_id');
    forum_ensure_column(forum_table_posts(), 'ip_address', 'ip_address VARCHAR(45) NULL AFTER updated_at');

    forum_ensure_setting_defaults();

    $rankCount = (int)$GLOBALS['pdo']->query('SELECT COUNT(*) FROM ' . forum_table_ranks())->fetchColumn();
    if ($rankCount === 0) {
        $seed = $GLOBALS['pdo']->prepare('
            INSERT INTO ' . forum_table_ranks() . ' (title, slug, min_posts, icon_class, sort_order, is_active, created_at, updated_at)
            VALUES (:title, :slug, :min_posts, :icon_class, :sort_order, 1, NOW(), NOW())
        ');
        foreach ([
            ['title' => 'Naujokas', 'slug' => 'naujokas', 'min_posts' => 0, 'icon_class' => 'fa-solid fa-seedling', 'sort_order' => 10],
            ['title' => 'Aktyvus narys', 'slug' => 'aktyvus-narys', 'min_posts' => 25, 'icon_class' => 'fa-solid fa-fire', 'sort_order' => 20],
            ['title' => 'Veteranas', 'slug' => 'veteranas', 'min_posts' => 100, 'icon_class' => 'fa-solid fa-medal', 'sort_order' => 30]
        ] as $rank) {
            $seed->execute([
                ':title' => $rank['title'],
                ':slug' => $rank['slug'],
                ':min_posts' => $rank['min_posts'],
                ':icon_class' => $rank['icon_class'],
                ':sort_order' => $rank['sort_order'],
            ]);
        }
    }

    $moodCount = (int)$GLOBALS['pdo']->query('SELECT COUNT(*) FROM ' . forum_table_moods())->fetchColumn();
    if ($moodCount === 0) {
        $seed = $GLOBALS['pdo']->prepare('
            INSERT INTO ' . forum_table_moods() . ' (title, slug, icon_class, sort_order, is_active, created_at, updated_at)
            VALUES (:title, :slug, :icon_class, :sort_order, 1, NOW(), NOW())
        ');
        foreach ([
            ['title' => 'Neutrali', 'slug' => 'neutrali', 'icon_class' => 'fa-regular fa-face-meh', 'sort_order' => 10],
            ['title' => 'Klausimas', 'slug' => 'klausimas', 'icon_class' => 'fa-solid fa-circle-question', 'sort_order' => 20],
            ['title' => 'Svarbu', 'slug' => 'svarbu', 'icon_class' => 'fa-solid fa-triangle-exclamation', 'sort_order' => 30],
            ['title' => 'Džiaugsminga', 'slug' => 'dziaugsminga', 'icon_class' => 'fa-regular fa-face-smile', 'sort_order' => 40]
        ] as $mood) {
            $seed->execute([
                ':title' => $mood['title'],
                ':slug' => $mood['slug'],
                ':icon_class' => $mood['icon_class'],
                ':sort_order' => $mood['sort_order'],
            ]);
        }
    }
}
