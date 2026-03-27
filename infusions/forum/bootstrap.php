<?php
function forum_table_categories()
{
    return 'infusion_forum_categories';
}

function forum_table_forums()
{
    return 'infusion_forum_forums';
}

function forum_table_topics()
{
    return 'infusion_forum_topics';
}

function forum_table_posts()
{
    return 'infusion_forum_posts';
}

function forum_allowed_tags()
{
    return ['b', 'i', 'u', 'quote', 'code', 'url'];
}

function forum_smileys()
{
    return [
        ':)' => '&#128578;',
        ';)' => '&#128521;',
        ':D' => '&#128516;',
        ':(' => '&#128577;',
        ':P' => '&#128539;',
        '<3' => '&#10084;&#65039;',
    ];
}

function forum_bbcode_buttons()
{
    return [
        ['label' => 'B', 'insert' => '[b][/b]'],
        ['label' => 'I', 'insert' => '[i][/i]'],
        ['label' => 'U', 'insert' => '[u][/u]'],
        ['label' => 'Code', 'insert' => '[code][/code]'],
        ['label' => 'Quote', 'insert' => '[quote][/quote]'],
        ['label' => 'Link', 'insert' => '[url=https://][/url]'],
    ];
}

function forum_topics_per_page()
{
    return 12;
}

function forum_posts_per_page()
{
    return 10;
}

function forum_panel_topics_limit()
{
    return 5;
}

function forum_index_url()
{
    return public_path('forum.php');
}

function forum_forum_url($forumId)
{
    return public_path('forum-view.php?id=' . (int)$forumId);
}

function forum_topic_url($topicId, $page = null)
{
    $url = public_path('forum-topic.php?id=' . (int)$topicId);
    if ($page !== null && (int)$page > 1) {
        $url .= '&page=' . (int)$page;
    }

    return $url;
}

function forum_can_admin()
{
    $user = current_user();
    return $user && has_permission($GLOBALS['pdo'], (int)$user['id'], 'forum.admin');
}

function forum_escape_for_like($value)
{
    return strtr((string)$value, [
        '!' => '!!',
        '%' => '!%',
        '_' => '!_',
    ]);
}

function forum_make_slug($value, $fallback = 'tema')
{
    $slug = normalize_slug((string)$value);
    return $slug !== '' ? $slug : $fallback;
}

function forum_unique_slug($table, $slug, $fallback, $excludeId = 0, $extraWhere = '', array $extraParams = [])
{
    $base = forum_make_slug($slug, $fallback);
    $candidate = $base;
    $suffix = 2;

    while (true) {
        $sql = 'SELECT COUNT(*) FROM ' . $table . ' WHERE slug = :slug';
        $params = [':slug' => $candidate];

        if ($excludeId > 0) {
            $sql .= ' AND id <> :exclude_id';
            $params[':exclude_id'] = (int)$excludeId;
        }

        if ($extraWhere !== '') {
            $sql .= ' AND ' . $extraWhere;
            $params = array_merge($params, $extraParams);
        }

        $stmt = $GLOBALS['pdo']->prepare($sql);
        $stmt->execute($params);
        if ((int)$stmt->fetchColumn() === 0) {
            return $candidate;
        }

        $candidate = $base . '-' . $suffix;
        $suffix++;
    }
}

function forum_prepare_body($body, $maxLength = 15000)
{
    $body = sanitize_bbcode_input((string)$body, forum_allowed_tags(), (int)$maxLength);
    $body = trim(preg_replace("/\r\n?/", "\n", $body));

    return $body;
}

function forum_format_body($body)
{
    $body = bbcode_to_html((string)$body, [
        'allowed_tags' => forum_allowed_tags(),
        'max_length' => 15000,
    ]);

    foreach (forum_smileys() as $code => $emoji) {
        $body = str_replace(escape_html($code), '<span class="forum-smiley">' . $emoji . '</span>', $body);
    }

    return $body;
}

function forum_excerpt($body, $length = 180)
{
    $body = preg_replace('/\[(\/?)[a-z]+(?:=[^\]]*)?\]/i', '', (string)$body);
    $body = trim(preg_replace('/\s+/u', ' ', strip_tags($body)));

    if ($body === '' || mb_strlen($body) <= $length) {
        return $body;
    }

    return rtrim(mb_substr($body, 0, $length - 1)) . '...';
}

function forum_ensure_schema()
{
    static $ensured = false;
    if ($ensured) {
        return;
    }

    $ensured = true;

    $GLOBALS['pdo']->exec("
        CREATE TABLE IF NOT EXISTS " . forum_table_categories() . " (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(190) NOT NULL,
            slug VARCHAR(190) NOT NULL,
            description TEXT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_forum_category_slug (slug),
            KEY idx_forum_category_sort (sort_order, id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $GLOBALS['pdo']->exec("
        CREATE TABLE IF NOT EXISTS " . forum_table_forums() . " (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            category_id INT UNSIGNED NOT NULL,
            parent_id INT UNSIGNED NULL DEFAULT NULL,
            title VARCHAR(190) NOT NULL,
            slug VARCHAR(190) NOT NULL,
            description TEXT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_forum_slug (slug),
            KEY idx_forum_category_parent (category_id, parent_id, sort_order, id),
            KEY idx_forum_parent (parent_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $GLOBALS['pdo']->exec("
        CREATE TABLE IF NOT EXISTS " . forum_table_topics() . " (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            forum_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NULL DEFAULT NULL,
            title VARCHAR(190) NOT NULL,
            slug VARCHAR(190) NOT NULL,
            content MEDIUMTEXT NOT NULL,
            views INT UNSIGNED NOT NULL DEFAULT 0,
            is_locked TINYINT(1) NOT NULL DEFAULT 0,
            is_pinned TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL DEFAULT NULL,
            last_post_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_post_user_id INT UNSIGNED NULL DEFAULT NULL,
            UNIQUE KEY uniq_forum_topic_forum_slug (forum_id, slug),
            KEY idx_forum_topics_forum_last_post (forum_id, is_pinned, last_post_at, id),
            KEY idx_forum_topics_last_post_user (last_post_user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $GLOBALS['pdo']->exec("
        CREATE TABLE IF NOT EXISTS " . forum_table_posts() . " (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            topic_id INT UNSIGNED NOT NULL,
            forum_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NULL DEFAULT NULL,
            content MEDIUMTEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL DEFAULT NULL,
            KEY idx_forum_posts_topic_created (topic_id, created_at, id),
            KEY idx_forum_posts_forum (forum_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    forum_seed_defaults();
}

function forum_seed_defaults()
{
    $count = (int)$GLOBALS['pdo']->query('SELECT COUNT(*) FROM ' . forum_table_categories())->fetchColumn();
    if ($count > 0) {
        return;
    }

    $defaultUserId = (int)$GLOBALS['pdo']->query("SELECT id FROM users ORDER BY id ASC LIMIT 1")->fetchColumn();
    if ($defaultUserId < 1) {
        $defaultUserId = null;
    }

    $categoryTitle = 'Bendros diskusijos';
    $categorySlug = 'bendros-diskusijos';
    try {
        $stmt = $GLOBALS['pdo']->prepare('
            INSERT IGNORE INTO ' . forum_table_categories() . ' (title, slug, description, sort_order, is_active, created_at)
            VALUES (:title, :slug, :description, :sort_order, 1, NOW())
        ');
        $stmt->execute([
            ':title' => $categoryTitle,
            ':slug' => $categorySlug,
            ':description' => 'Bendruomenei skirtos diskusijos, klausimai ir naujienos.',
            ':sort_order' => 1,
        ]);
    } catch (Throwable $e) {
        return;
    }

    $categoryIdStmt = $GLOBALS['pdo']->prepare('SELECT id FROM ' . forum_table_categories() . ' WHERE slug = :slug LIMIT 1');
    $categoryIdStmt->execute([':slug' => $categorySlug]);
    $categoryId = (int)$categoryIdStmt->fetchColumn();
    if ($categoryId < 1) {
        return;
    }

    $forumTitle = 'Pristatymai';
    $forumSlug = 'pristatymai';
    $stmt = $GLOBALS['pdo']->prepare('
        INSERT IGNORE INTO ' . forum_table_forums() . ' (category_id, parent_id, title, slug, description, sort_order, is_active, created_at)
        VALUES (:category_id, NULL, :title, :slug, :description, :sort_order, 1, NOW())
    ');
    $stmt->execute([
        ':category_id' => $categoryId,
        ':title' => $forumTitle,
        ':slug' => $forumSlug,
        ':description' => 'Prisistatykite bendruomenei ir pradekite pokalbi.',
        ':sort_order' => 1,
    ]);
    $forumIdStmt = $GLOBALS['pdo']->prepare('SELECT id FROM ' . forum_table_forums() . ' WHERE slug = :slug LIMIT 1');
    $forumIdStmt->execute([':slug' => $forumSlug]);
    $forumId = (int)$forumIdStmt->fetchColumn();
    if ($forumId < 1) {
        return;
    }

    $subforumTitle = 'Naujoku zona';
    $subforumSlug = 'naujoku-zona';
    $stmt = $GLOBALS['pdo']->prepare('
        INSERT IGNORE INTO ' . forum_table_forums() . ' (category_id, parent_id, title, slug, description, sort_order, is_active, created_at)
        VALUES (:category_id, :parent_id, :title, :slug, :description, :sort_order, 1, NOW())
    ');
    $stmt->execute([
        ':category_id' => $categoryId,
        ':parent_id' => $forumId,
        ':title' => $subforumTitle,
        ':slug' => $subforumSlug,
        ':description' => 'Vieta pirmai temai, klausimams ir pagalbai naujiems nariams.',
        ':sort_order' => 1,
    ]);
    $subforumIdStmt = $GLOBALS['pdo']->prepare('SELECT id FROM ' . forum_table_forums() . ' WHERE slug = :slug LIMIT 1');
    $subforumIdStmt->execute([':slug' => $subforumSlug]);
    $subforumId = (int)$subforumIdStmt->fetchColumn();
    if ($subforumId < 1) {
        return;
    }

    $topicTitle = 'Sveiki atvyke i foruma';
    $topicSlug = 'sveiki-atvyke-i-foruma';
    $stmt = $GLOBALS['pdo']->prepare('
        INSERT IGNORE INTO ' . forum_table_topics() . ' (forum_id, user_id, title, slug, content, views, is_locked, is_pinned, created_at, updated_at, last_post_at, last_post_user_id)
        VALUES (:forum_id, :user_id, :title, :slug, :content, 0, 0, 1, NOW(), NOW(), NOW(), :last_post_user_id)
    ');
    $stmt->execute([
        ':forum_id' => $subforumId,
        ':user_id' => $defaultUserId,
        ':title' => $topicTitle,
        ':slug' => $topicSlug,
        ':content' => "Sveiki atvyke i nauja foruma.\n\nNaudokite [b]BBCode[/b], smailus :) ir drasiai kurkite savo temas.",
        ':last_post_user_id' => $defaultUserId,
    ]);
}

function forum_fetch_categories()
{
    forum_ensure_schema();
    $stmt = $GLOBALS['pdo']->query('
        SELECT *
        FROM ' . forum_table_categories() . '
        WHERE is_active = 1
        ORDER BY sort_order ASC, id ASC
    ');

    return $stmt->fetchAll();
}

function forum_fetch_forum_rows()
{
    forum_ensure_schema();
    $stmt = $GLOBALS['pdo']->query('
        SELECT f.*,
               c.title AS category_title,
               c.slug AS category_slug,
               (SELECT COUNT(*) FROM ' . forum_table_topics() . ' t WHERE t.forum_id = f.id) AS topics_count,
               (
                   (SELECT COUNT(*) FROM ' . forum_table_topics() . ' t2 WHERE t2.forum_id = f.id)
                   +
                   (SELECT COUNT(*) FROM ' . forum_table_posts() . ' p WHERE p.forum_id = f.id)
               ) AS posts_count,
               (
                   SELECT t3.id
                   FROM ' . forum_table_topics() . ' t3
                   WHERE t3.forum_id = f.id
                   ORDER BY t3.last_post_at DESC, t3.id DESC
                   LIMIT 1
               ) AS last_topic_id,
               (
                   SELECT t4.title
                   FROM ' . forum_table_topics() . ' t4
                   WHERE t4.forum_id = f.id
                   ORDER BY t4.last_post_at DESC, t4.id DESC
                   LIMIT 1
               ) AS last_topic_title,
               (
                   SELECT t5.last_post_at
                   FROM ' . forum_table_topics() . ' t5
                   WHERE t5.forum_id = f.id
                   ORDER BY t5.last_post_at DESC, t5.id DESC
                   LIMIT 1
               ) AS last_post_at,
               (
                   SELECT t6.last_post_user_id
                   FROM ' . forum_table_topics() . ' t6
                   WHERE t6.forum_id = f.id
                   ORDER BY t6.last_post_at DESC, t6.id DESC
                   LIMIT 1
               ) AS last_post_user_id,
               (
                   SELECT u.username
                   FROM ' . forum_table_topics() . ' t7
                   LEFT JOIN users u ON u.id = t7.last_post_user_id
                   WHERE t7.forum_id = f.id
                   ORDER BY t7.last_post_at DESC, t7.id DESC
                   LIMIT 1
               ) AS last_post_username
        FROM ' . forum_table_forums() . ' f
        INNER JOIN ' . forum_table_categories() . ' c ON c.id = f.category_id
        WHERE c.is_active = 1 AND f.is_active = 1
        ORDER BY f.category_id ASC, CASE WHEN f.parent_id IS NULL THEN 0 ELSE 1 END ASC, COALESCE(f.parent_id, f.id) ASC, f.sort_order ASC, f.id ASC
    ');

    return $stmt->fetchAll();
}

function forum_get_index_data()
{
    $categories = forum_fetch_categories();
    $rows = forum_fetch_forum_rows();

    $result = [];
    foreach ($categories as $category) {
        $category['forums'] = [];
        $result[(int)$category['id']] = $category;
    }

    $orphans = [];
    foreach ($rows as $row) {
        if ((int)$row['parent_id'] === 0) {
            $row['subforums'] = [];
            if (isset($result[(int)$row['category_id']])) {
                $result[(int)$row['category_id']]['forums'][(int)$row['id']] = $row;
            }
            continue;
        }

        $orphans[] = $row;
    }

    foreach ($orphans as $row) {
        $categoryId = (int)$row['category_id'];
        $parentId = (int)$row['parent_id'];
        if (isset($result[$categoryId]['forums'][$parentId])) {
            $result[$categoryId]['forums'][$parentId]['subforums'][] = $row;
            continue;
        }

        $row['subforums'] = [];
        $result[$categoryId]['forums'][(int)$row['id']] = $row;
    }

    return array_values($result);
}

function forum_get_forum($forumId)
{
    forum_ensure_schema();
    $stmt = $GLOBALS['pdo']->prepare('
        SELECT f.*,
               c.title AS category_title,
               c.slug AS category_slug,
               p.title AS parent_title,
               p.slug AS parent_slug
        FROM ' . forum_table_forums() . ' f
        INNER JOIN ' . forum_table_categories() . ' c ON c.id = f.category_id
        LEFT JOIN ' . forum_table_forums() . ' p ON p.id = f.parent_id
        WHERE f.id = :id AND f.is_active = 1
        LIMIT 1
    ');
    $stmt->execute([':id' => (int)$forumId]);

    return $stmt->fetch() ?: null;
}

function forum_get_forum_options()
{
    forum_ensure_schema();
    $stmt = $GLOBALS['pdo']->query('
        SELECT f.id, f.category_id, f.parent_id, f.title, c.title AS category_title
        FROM ' . forum_table_forums() . ' f
        INNER JOIN ' . forum_table_categories() . ' c ON c.id = f.category_id
        WHERE f.is_active = 1
        ORDER BY c.sort_order ASC, c.id ASC, CASE WHEN f.parent_id IS NULL THEN 0 ELSE 1 END ASC, COALESCE(f.parent_id, f.id) ASC, f.sort_order ASC, f.id ASC
    ');

    return $stmt->fetchAll();
}

function forum_count_topics($forumId)
{
    $stmt = $GLOBALS['pdo']->prepare('SELECT COUNT(*) FROM ' . forum_table_topics() . ' WHERE forum_id = :forum_id');
    $stmt->execute([':forum_id' => (int)$forumId]);

    return (int)$stmt->fetchColumn();
}

function forum_get_topics($forumId, $limit, $offset)
{
    $limit = max(1, (int)$limit);
    $offset = max(0, (int)$offset);

    $stmt = $GLOBALS['pdo']->prepare('
        SELECT t.*,
               u.username,
               u.avatar,
               u.email,
               lu.username AS last_post_username,
               lu.avatar AS last_post_avatar,
               (SELECT COUNT(*) FROM ' . forum_table_posts() . ' p WHERE p.topic_id = t.id) AS reply_count
        FROM ' . forum_table_topics() . ' t
        LEFT JOIN users u ON u.id = t.user_id
        LEFT JOIN users lu ON lu.id = t.last_post_user_id
        WHERE t.forum_id = :forum_id
        ORDER BY t.is_pinned DESC, t.last_post_at DESC, t.id DESC
        LIMIT ' . $limit . ' OFFSET ' . $offset
    );
    $stmt->execute([':forum_id' => (int)$forumId]);

    return $stmt->fetchAll();
}

function forum_get_topic($topicId)
{
    forum_ensure_schema();
    $stmt = $GLOBALS['pdo']->prepare('
        SELECT t.*,
               f.title AS forum_title,
               f.slug AS forum_slug,
               f.parent_id AS forum_parent_id,
               c.id AS category_id,
               c.title AS category_title,
               c.slug AS category_slug,
               u.username,
               u.avatar,
               u.email,
               (SELECT COUNT(*) FROM ' . forum_table_posts() . ' p WHERE p.topic_id = t.id) AS reply_count
        FROM ' . forum_table_topics() . ' t
        INNER JOIN ' . forum_table_forums() . ' f ON f.id = t.forum_id
        INNER JOIN ' . forum_table_categories() . ' c ON c.id = f.category_id
        LEFT JOIN users u ON u.id = t.user_id
        WHERE t.id = :id
        LIMIT 1
    ');
    $stmt->execute([':id' => (int)$topicId]);

    return $stmt->fetch() ?: null;
}

function forum_count_replies($topicId)
{
    $stmt = $GLOBALS['pdo']->prepare('SELECT COUNT(*) FROM ' . forum_table_posts() . ' WHERE topic_id = :topic_id');
    $stmt->execute([':topic_id' => (int)$topicId]);

    return (int)$stmt->fetchColumn();
}

function forum_get_replies($topicId, $limit, $offset)
{
    $limit = max(1, (int)$limit);
    $offset = max(0, (int)$offset);

    $stmt = $GLOBALS['pdo']->prepare('
        SELECT p.*,
               u.username,
               u.avatar,
               u.email
        FROM ' . forum_table_posts() . ' p
        LEFT JOIN users u ON u.id = p.user_id
        WHERE p.topic_id = :topic_id
        ORDER BY p.created_at ASC, p.id ASC
        LIMIT ' . $limit . ' OFFSET ' . $offset
    );
    $stmt->execute([':topic_id' => (int)$topicId]);

    return $stmt->fetchAll();
}

function forum_topic_last_page($topicId)
{
    $replies = forum_count_replies($topicId);
    return max(1, (int)ceil($replies / forum_posts_per_page()));
}

function forum_increment_topic_views($topicId)
{
    $stmt = $GLOBALS['pdo']->prepare('UPDATE ' . forum_table_topics() . ' SET views = views + 1 WHERE id = :id');
    $stmt->execute([':id' => (int)$topicId]);
}

function forum_recent_topics($limit = 5)
{
    $limit = max(1, (int)$limit);
    $stmt = $GLOBALS['pdo']->query('
        SELECT t.id, t.forum_id, t.user_id, t.title, t.views, t.last_post_at, t.created_at,
               f.title AS forum_title,
               u.username,
               u.avatar,
               u.email
        FROM ' . forum_table_topics() . ' t
        INNER JOIN ' . forum_table_forums() . ' f ON f.id = t.forum_id
        LEFT JOIN users u ON u.id = t.user_id
        ORDER BY t.last_post_at DESC, t.id DESC
        LIMIT ' . $limit
    );

    return $stmt->fetchAll();
}

function forum_create_category($title, $description, $sortOrder = 0)
{
    forum_ensure_schema();

    $title = trim((string)$title);
    $description = trim((string)$description);
    $sortOrder = (int)$sortOrder;

    if (mb_strlen($title) < 2 || mb_strlen($title) > 190) {
        return [false, 'Kategorijos pavadinimas turi buti nuo 2 iki 190 simboliu.'];
    }

    $slug = forum_unique_slug(forum_table_categories(), $title, 'kategorija');
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

    audit_log(current_user()['id'] ?? null, 'forum_category_create', 'infusion_forum_categories', (int)$GLOBALS['pdo']->lastInsertId(), [
        'title' => $title,
    ]);

    return [true, 'Kategorija sukurta.'];
}

function forum_create_forum($categoryId, $parentId, $title, $description, $sortOrder = 0)
{
    forum_ensure_schema();

    $categoryId = (int)$categoryId;
    $parentId = (int)$parentId;
    $title = trim((string)$title);
    $description = trim((string)$description);
    $sortOrder = (int)$sortOrder;

    if (mb_strlen($title) < 2 || mb_strlen($title) > 190) {
        return [false, 'Forumo pavadinimas turi buti nuo 2 iki 190 simboliu.'];
    }

    $categoryStmt = $GLOBALS['pdo']->prepare('SELECT id FROM ' . forum_table_categories() . ' WHERE id = :id AND is_active = 1 LIMIT 1');
    $categoryStmt->execute([':id' => $categoryId]);
    if (!(int)$categoryStmt->fetchColumn()) {
        return [false, 'Pasirinkta kategorija nerasta.'];
    }

    if ($parentId > 0) {
        $parentStmt = $GLOBALS['pdo']->prepare('
            SELECT id, category_id, parent_id
            FROM ' . forum_table_forums() . '
            WHERE id = :id AND is_active = 1
            LIMIT 1
        ');
        $parentStmt->execute([':id' => $parentId]);
        $parent = $parentStmt->fetch();
        if (!$parent) {
            return [false, 'Pasirinktas parent forumas nerastas.'];
        }

        if ((int)$parent['parent_id'] > 0) {
            return [false, 'Leidziamas tik vienas subforumu lygis.'];
        }

        $categoryId = (int)$parent['category_id'];
    }

    $slug = forum_unique_slug(forum_table_forums(), $title, 'forumas');
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

    audit_log(current_user()['id'] ?? null, 'forum_forum_create', 'infusion_forum_forums', (int)$GLOBALS['pdo']->lastInsertId(), [
        'title' => $title,
        'parent_id' => $parentId > 0 ? $parentId : null,
    ]);

    return [true, $parentId > 0 ? 'Subforumas sukurtas.' : 'Forumas sukurtas.'];
}

function forum_create_topic($forumId, $title, $content)
{
    forum_ensure_schema();

    $user = current_user();
    if (!$user) {
        return [false, 'Tema gali kurti tik prisijunge nariai.', null];
    }

    $forum = forum_get_forum($forumId);
    if (!$forum) {
        return [false, 'Forumas nerastas.', null];
    }

    $title = trim((string)$title);
    $content = forum_prepare_body($content, 15000);

    if (mb_strlen($title) < 3 || mb_strlen($title) > 190) {
        return [false, 'Temos pavadinimas turi buti nuo 3 iki 190 simboliu.', null];
    }
    if ($content === '') {
        return [false, 'Temos turinys negali buti tuscias.', null];
    }

    $slug = forum_unique_slug(forum_table_topics(), $title, 'tema', 0, 'forum_id = :forum_id', [
        ':forum_id' => (int)$forum['id'],
    ]);

    $stmt = $GLOBALS['pdo']->prepare('
        INSERT INTO ' . forum_table_topics() . ' (forum_id, user_id, title, slug, content, views, is_locked, is_pinned, created_at, updated_at, last_post_at, last_post_user_id)
        VALUES (:forum_id, :user_id, :title, :slug, :content, 0, 0, 0, NOW(), NOW(), NOW(), :last_post_user_id)
    ');
    $stmt->execute([
        ':forum_id' => (int)$forum['id'],
        ':user_id' => (int)$user['id'],
        ':title' => $title,
        ':slug' => $slug,
        ':content' => $content,
        ':last_post_user_id' => (int)$user['id'],
    ]);

    $topicId = (int)$GLOBALS['pdo']->lastInsertId();
    audit_log((int)$user['id'], 'forum_topic_create', 'infusion_forum_topics', $topicId, [
        'forum_id' => (int)$forum['id'],
        'title' => $title,
    ]);

    return [true, 'Tema sukurta.', $topicId];
}

function forum_create_reply($topicId, $content)
{
    forum_ensure_schema();

    $user = current_user();
    if (!$user) {
        return [false, 'Atsakyti gali tik prisijunge nariai.', null];
    }

    $topic = forum_get_topic($topicId);
    if (!$topic) {
        return [false, 'Tema nerasta.', null];
    }
    if ((int)$topic['is_locked'] === 1) {
        return [false, 'Tema uzrakinta.', null];
    }

    $content = forum_prepare_body($content, 15000);
    if ($content === '') {
        return [false, 'Atsakymas negali buti tuscias.', null];
    }

    $stmt = $GLOBALS['pdo']->prepare('
        INSERT INTO ' . forum_table_posts() . ' (topic_id, forum_id, user_id, content, created_at, updated_at)
        VALUES (:topic_id, :forum_id, :user_id, :content, NOW(), NOW())
    ');
    $stmt->execute([
        ':topic_id' => (int)$topic['id'],
        ':forum_id' => (int)$topic['forum_id'],
        ':user_id' => (int)$user['id'],
        ':content' => $content,
    ]);

    $postId = (int)$GLOBALS['pdo']->lastInsertId();
    $updateTopic = $GLOBALS['pdo']->prepare('
        UPDATE ' . forum_table_topics() . '
        SET updated_at = NOW(),
            last_post_at = NOW(),
            last_post_user_id = :last_post_user_id
        WHERE id = :id
    ');
    $updateTopic->execute([
        ':last_post_user_id' => (int)$user['id'],
        ':id' => (int)$topic['id'],
    ]);

    audit_log((int)$user['id'], 'forum_reply_create', 'infusion_forum_posts', $postId, [
        'topic_id' => (int)$topic['id'],
        'forum_id' => (int)$topic['forum_id'],
    ]);

    return [true, 'Atsakymas paskelbtas.', $postId];
}

function forum_render_editor_toolbar($textareaId)
{
    foreach (forum_bbcode_buttons() as $button) {
        echo '<button type="button" class="btn btn-sm btn-outline-secondary" data-editor-target="' . e($textareaId) . '" data-insert-text="' . e($button['insert']) . '">' . e($button['label']) . '</button>';
    }
}

function forum_render_smileys($textareaId)
{
    foreach (forum_smileys() as $code => $emoji) {
        echo '<button type="button" class="btn btn-sm btn-outline-warning" data-editor-target="' . e($textareaId) . '" data-smiley-code="' . e($code) . '">' . $emoji . '</button>';
    }
}

function forum_render_breadcrumb(array $items)
{
    echo '<nav aria-label="breadcrumb" class="mb-3"><ol class="breadcrumb forum-breadcrumb">';
    foreach ($items as $item) {
        $title = e($item['title'] ?? '');
        $url = trim((string)($item['url'] ?? ''));
        if ($url === '') {
            echo '<li class="breadcrumb-item active" aria-current="page">' . $title . '</li>';
        } else {
            echo '<li class="breadcrumb-item"><a href="' . escape_url($url) . '">' . $title . '</a></li>';
        }
    }
    echo '</ol></nav>';
}

forum_ensure_schema();
