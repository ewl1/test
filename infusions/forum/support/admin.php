<?php
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

