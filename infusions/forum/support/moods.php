<?php
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

