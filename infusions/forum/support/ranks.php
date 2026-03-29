<?php
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

