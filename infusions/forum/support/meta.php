<?php
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

