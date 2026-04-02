<?php
function forum_register_assets()
{
    register_page_style('infusions/forum/assets/css/forum.css');
    register_page_script('infusions/forum/assets/js/forum.js');
}

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
    return ['b', 'i', 'u', 'quote', 'code', 'url', 'img', 'youtube'];
}

function forum_smileys()
{
    return site_smileys(true);
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
        ['label' => 'Img', 'insert' => '[img]https://[/img]'],
        ['label' => 'YouTube', 'insert' => '[youtube]https://youtu.be/VIDEO_ID[/youtube]'],
    ];
}

function forum_topics_per_page()
{
    return forum_topics_per_page_setting();
}

function forum_posts_per_page()
{
    return forum_posts_per_page_setting();
}

function forum_panel_topics_limit()
{
    return forum_recent_threads_limit_setting();
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

    return apply_site_smileys($body, 'forum-smiley');
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

    $categoryTitle = __('forum.seed.category_title');
    $categorySlug = 'bendros-diskusijos';
    try {
        $stmt = $GLOBALS['pdo']->prepare('
            INSERT IGNORE INTO ' . forum_table_categories() . ' (title, slug, description, sort_order, is_active, created_at)
            VALUES (:title, :slug, :description, :sort_order, 1, NOW())
        ');
        $stmt->execute([
            ':title' => $categoryTitle,
            ':slug' => $categorySlug,
            ':description' => __('forum.seed.category_description'),
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

    $forumTitle = __('forum.seed.forum_title');
    $forumSlug = 'pristatymai';
    $stmt = $GLOBALS['pdo']->prepare('
        INSERT IGNORE INTO ' . forum_table_forums() . ' (category_id, parent_id, title, slug, description, sort_order, is_active, created_at)
        VALUES (:category_id, NULL, :title, :slug, :description, :sort_order, 1, NOW())
    ');
    $stmt->execute([
        ':category_id' => $categoryId,
        ':title' => $forumTitle,
        ':slug' => $forumSlug,
            ':description' => __('forum.seed.forum_description'),
        ':sort_order' => 1,
    ]);
    $forumIdStmt = $GLOBALS['pdo']->prepare('SELECT id FROM ' . forum_table_forums() . ' WHERE slug = :slug LIMIT 1');
    $forumIdStmt->execute([':slug' => $forumSlug]);
    $forumId = (int)$forumIdStmt->fetchColumn();
    if ($forumId < 1) {
        return;
    }

    $subforumTitle = __('forum.seed.subforum_title');
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
        ':description' => __('forum.seed.subforum_description'),
        ':sort_order' => 1,
    ]);
    $subforumIdStmt = $GLOBALS['pdo']->prepare('SELECT id FROM ' . forum_table_forums() . ' WHERE slug = :slug LIMIT 1');
    $subforumIdStmt->execute([':slug' => $subforumSlug]);
    $subforumId = (int)$subforumIdStmt->fetchColumn();
    if ($subforumId < 1) {
        return;
    }

    $topicTitle = __('forum.seed.topic_title');
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
        ':content' => __('forum.seed.topic_content'),
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
               ) AS last_post_username,
               (
                   SELECT u2.avatar
                   FROM ' . forum_table_topics() . ' t8
                   LEFT JOIN users u2 ON u2.id = t8.last_post_user_id
                   WHERE t8.forum_id = f.id
                   ORDER BY t8.last_post_at DESC, t8.id DESC
                   LIMIT 1
               ) AS last_post_avatar
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

    $categoryMetaMap = forum_get_category_meta_map(array_map(static function ($category) {
        return (int)$category['id'];
    }, $categories));
    $forumMetaMap = forum_get_forum_meta_map(array_map(static function ($row) {
        return (int)$row['id'];
    }, $rows));

    $result = [];
    foreach ($categories as $category) {
        $category = forum_apply_category_meta($category, $categoryMetaMap[(int)$category['id']] ?? []);
        $category['forums'] = [];
        $result[(int)$category['id']] = $category;
    }

    $orphans = [];
    foreach ($rows as $row) {
        $row = forum_apply_forum_meta($row, $forumMetaMap[(int)$row['id']] ?? []);
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

    $forum = $stmt->fetch() ?: null;
    if (!$forum) {
        return null;
    }

    return forum_apply_forum_meta($forum, forum_get_forum_meta((int)$forum['id']));
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

function forum_get_reply($replyId)
{
    forum_ensure_schema();

    $stmt = $GLOBALS['pdo']->prepare('
        SELECT p.*,
               t.title AS topic_title,
               t.slug AS topic_slug,
               t.is_locked AS topic_is_locked,
               u.username,
               u.avatar,
               u.email
        FROM ' . forum_table_posts() . ' p
        INNER JOIN ' . forum_table_topics() . ' t ON t.id = p.topic_id
        LEFT JOIN users u ON u.id = p.user_id
        WHERE p.id = :id
        LIMIT 1
    ');
    $stmt->execute([':id' => (int)$replyId]);

    return $stmt->fetch() ?: null;
}

function forum_sync_topic_activity($topicId)
{
    $topic = forum_get_topic($topicId);
    if (!$topic) {
        return;
    }

    $lastReplyStmt = $GLOBALS['pdo']->prepare('
        SELECT created_at, user_id
        FROM ' . forum_table_posts() . '
        WHERE topic_id = :topic_id
        ORDER BY created_at DESC, id DESC
        LIMIT 1
    ');
    $lastReplyStmt->execute([':topic_id' => (int)$topicId]);
    $lastReply = $lastReplyStmt->fetch();

    $lastPostAt = $lastReply['created_at'] ?? $topic['created_at'];
    $lastPostUserId = isset($lastReply['user_id']) ? (int)$lastReply['user_id'] : (int)$topic['user_id'];

    $updateTopic = $GLOBALS['pdo']->prepare('
        UPDATE ' . forum_table_topics() . '
        SET last_post_at = :last_post_at,
            last_post_user_id = :last_post_user_id,
            updated_at = NOW()
        WHERE id = :id
    ');
    $updateTopic->execute([
        ':last_post_at' => $lastPostAt,
        ':last_post_user_id' => $lastPostUserId > 0 ? $lastPostUserId : null,
        ':id' => (int)$topicId,
    ]);
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
        return [false, __('forum.validation.category_title')];
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

    return [true, __('forum.message.category_created')];
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
        return [false, __('forum.validation.forum_title')];
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
            return [false, __('forum.validation.single_sublevel')];
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

    return [true, $parentId > 0 ? __('forum.message.subforum_created') : __('forum.message.forum_created')];
}

function forum_create_topic($forumId, $title, $content, $moodId = 0, array $files = [])
{
    forum_ensure_schema();
    forum_ensure_extended_schema();

    $user = current_user();
    if (!$user) {
        return [false, 'Temą gali kurti tik prisijungę nariai.', null];
    }

    $forum = forum_get_forum($forumId);
    if (!$forum) {
        return [false, 'Forumas nerastas.', null];
    }
    if (!empty($forum['is_locked'])) {
        return [false, 'Šis forumas užrakintas. Naujų temų kurti negalima.', null];
    }

    $title = trim((string)$title);
    $content = forum_prepare_body($content, 15000);
    $moodId = max(0, (int)$moodId);

    if (mb_strlen($title) < 3 || mb_strlen($title) > 190) {
        return [false, __('forum.validation.topic_title'), null];
    }
    if ($content === '') {
        return [false, __('forum.validation.topic_content'), null];
    }
    [$titleOk, $titleMessage] = badwords_validate($title, 'Temos pavadinime');
    if (!$titleOk) {
        return [false, $titleMessage, null];
    }
    [$contentOk, $contentMessage] = badwords_validate($content, 'Temos tekste');
    if (!$contentOk) {
        return [false, $contentMessage, null];
    }
    if ($moodId > 0 && !forum_get_mood($moodId)) {
        $moodId = 0;
    }

    $slug = forum_unique_slug(forum_table_topics(), $title, 'tema', 0, 'forum_id = :forum_id', [
        ':forum_id' => (int)$forum['id'],
    ]);

    $stmt = $GLOBALS['pdo']->prepare('
        INSERT INTO ' . forum_table_topics() . ' (forum_id, user_id, title, slug, content, views, is_locked, is_pinned, mood_id, created_at, updated_at, last_post_at, last_post_user_id, ip_address)
        VALUES (:forum_id, :user_id, :title, :slug, :content, 0, 0, 0, :mood_id, NOW(), NOW(), NOW(), :last_post_user_id, :ip_address)
    ');
    $stmt->execute([
        ':forum_id' => (int)$forum['id'],
        ':user_id' => (int)$user['id'],
        ':title' => $title,
        ':slug' => $slug,
        ':content' => $content,
        ':mood_id' => $moodId > 0 ? $moodId : null,
        ':last_post_user_id' => (int)$user['id'],
        ':ip_address' => client_ip(),
    ]);

    $topicId = (int)$GLOBALS['pdo']->lastInsertId();
    if (!empty($forum['allow_attachments'])) {
        [$attachmentsOk, $attachmentsMessage] = forum_store_attachment_files((int)$forum['id'], $topicId, null, (int)$user['id'], $files);
        if (!$attachmentsOk) {
            $deleteTopic = $GLOBALS['pdo']->prepare('DELETE FROM ' . forum_table_topics() . ' WHERE id = :id');
            $deleteTopic->execute([':id' => $topicId]);
            return [false, $attachmentsMessage, null];
        }
    }
    audit_log((int)$user['id'], 'forum_topic_create', 'infusion_forum_topics', $topicId, [
        'forum_id' => (int)$forum['id'],
        'title' => $title,
        'mood_id' => $moodId > 0 ? $moodId : null,
    ]);

    return [true, __('forum.message.topic_created'), $topicId];
}

function forum_create_reply($topicId, $content, array $files = [])
{
    forum_ensure_schema();
    forum_ensure_extended_schema();

    $user = current_user();
    if (!$user) {
        return [false, __('forum.message.reply_login_required'), null];
    }

    $topic = forum_get_topic($topicId);
    if (!$topic) {
        return [false, __('forum.topic.not_found'), null];
    }
    $forum = forum_get_forum((int)$topic['forum_id']);
    if ($forum && !empty($forum['is_locked'])) {
        return [false, 'Šis forumas užrakintas. Nauji atsakymai negalimi.', null];
    }
    if ((int)$topic['is_locked'] === 1) {
        return [false, __('forum.message.locked'), null];
    }

    $content = forum_prepare_body($content, 15000);
    if ($content === '') {
        return [false, __('forum.validation.reply_content'), null];
    }
    [$contentOk, $contentMessage] = badwords_validate($content, 'Atsakyme');
    if (!$contentOk) {
        return [false, $contentMessage, null];
    }

    $postId = 0;
    $mergedReply = false;
    if ($forum && !empty($forum['enable_post_merge'])) {
        $lastReplyStmt = $GLOBALS['pdo']->prepare('
            SELECT id, user_id, content
            FROM ' . forum_table_posts() . '
            WHERE topic_id = :topic_id
            ORDER BY created_at DESC, id DESC
            LIMIT 1
        ');
        $lastReplyStmt->execute([':topic_id' => (int)$topic['id']]);
        $lastReply = $lastReplyStmt->fetch();
        if ($lastReply && (int)$lastReply['user_id'] === (int)$user['id']) {
            $mergedContent = trim((string)$lastReply['content']) . "\n\n---\n" . $content;
            $mergeStmt = $GLOBALS['pdo']->prepare('
                UPDATE ' . forum_table_posts() . '
                SET content = :content,
                    updated_at = NOW()
                WHERE id = :id
            ');
            $mergeStmt->execute([
                ':content' => $mergedContent,
                ':id' => (int)$lastReply['id'],
            ]);
            $postId = (int)$lastReply['id'];
            $mergedReply = true;
        }
    }

    if ($postId === 0) {
        $stmt = $GLOBALS['pdo']->prepare('
            INSERT INTO ' . forum_table_posts() . ' (topic_id, forum_id, user_id, content, created_at, updated_at, ip_address)
            VALUES (:topic_id, :forum_id, :user_id, :content, NOW(), NOW(), :ip_address)
        ');
        $stmt->execute([
            ':topic_id' => (int)$topic['id'],
            ':forum_id' => (int)$topic['forum_id'],
            ':user_id' => (int)$user['id'],
            ':content' => $content,
            ':ip_address' => client_ip(),
        ]);

        $postId = (int)$GLOBALS['pdo']->lastInsertId();
    }

    if ($forum && !empty($forum['allow_attachments'])) {
        [$attachmentsOk, $attachmentsMessage] = forum_store_attachment_files((int)$topic['forum_id'], (int)$topic['id'], $postId, (int)$user['id'], $files);
        if (!$attachmentsOk) {
            if ($mergedReply && !empty($lastReply['content'])) {
                $restorePost = $GLOBALS['pdo']->prepare('UPDATE ' . forum_table_posts() . ' SET content = :content WHERE id = :id');
                $restorePost->execute([
                    ':content' => (string)$lastReply['content'],
                    ':id' => $postId,
                ]);
            } elseif (!$mergedReply && $postId > 0) {
                $deletePost = $GLOBALS['pdo']->prepare('DELETE FROM ' . forum_table_posts() . ' WHERE id = :id');
                $deletePost->execute([':id' => $postId]);
            }
            return [false, $attachmentsMessage, null];
        }
    }

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
        'merged' => $mergedReply ? 1 : 0,
    ]);

    return [true, __('forum.message.reply_created'), $postId];
}

function forum_can_moderate_topic(array $topic)
{
    return forum_can_admin();
}

function forum_can_moderate_reply(array $reply)
{
    return forum_can_admin();
}

function forum_update_topic($topicId, $title, $content, $moodId = 0)
{
    forum_ensure_schema();
    forum_ensure_extended_schema();

    $topic = forum_get_topic($topicId);
    if (!$topic) {
        return [false, __('forum.topic.not_found'), null];
    }
    if (!forum_can_moderate_topic($topic) && !forum_can_edit_own_topic($topic)) {
        return [false, __('forum.message.edit_topic_denied'), null];
    }

    $title = trim((string)$title);
    $content = forum_prepare_body($content, 15000);
    $moodId = max(0, (int)$moodId);

    if (mb_strlen($title) < 3 || mb_strlen($title) > 190) {
        return [false, __('forum.validation.topic_title'), null];
    }
    if ($content === '') {
        return [false, __('forum.validation.topic_content'), null];
    }
    if ($moodId > 0 && !forum_get_mood($moodId)) {
        $moodId = 0;
    }

    $slug = forum_unique_slug(forum_table_topics(), $title, 'tema', (int)$topic['id'], 'forum_id = :forum_id', [
        ':forum_id' => (int)$topic['forum_id'],
    ]);

    $stmt = $GLOBALS['pdo']->prepare('
        UPDATE ' . forum_table_topics() . '
        SET title = :title,
            slug = :slug,
            content = :content,
            mood_id = :mood_id,
            updated_at = ' . (forum_update_time_on_edit_enabled() ? 'NOW()' : 'updated_at') . '
        WHERE id = :id
    ');
    $stmt->execute([
        ':title' => $title,
        ':slug' => $slug,
        ':content' => $content,
        ':mood_id' => $moodId > 0 ? $moodId : null,
        ':id' => (int)$topic['id'],
    ]);

    audit_log(current_user()['id'] ?? null, 'forum_topic_update', 'infusion_forum_topics', (int)$topic['id'], [
        'title' => $title,
    ]);
    moderation_log(current_user()['id'] ?? null, 'forum_topic_updated', 'forum_topic', (int)$topic['id'], [
        'target_label' => $title,
        'context_type' => 'forum',
        'context_id' => (int)$topic['forum_id'],
        'details' => [
            'forum_id' => (int)$topic['forum_id'],
            'title' => $title,
        ],
    ]);

    return [true, __('forum.message.topic_updated'), (int)$topic['id']];
}

function forum_set_topic_flag($topicId, $flag, $value)
{
    forum_ensure_schema();

    $allowed = ['is_pinned', 'is_locked'];
    if (!in_array($flag, $allowed, true)) {
        return [false, __('forum.message.unknown_action'), null];
    }

    $topic = forum_get_topic($topicId);
    if (!$topic) {
        return [false, __('forum.topic.not_found'), null];
    }
    if (!forum_can_moderate_topic($topic)) {
        return [false, __('forum.message.moderate_denied'), null];
    }

    $stmt = $GLOBALS['pdo']->prepare('UPDATE ' . forum_table_topics() . ' SET ' . $flag . ' = :value WHERE id = :id');
    $stmt->execute([
        ':value' => $value ? 1 : 0,
        ':id' => (int)$topic['id'],
    ]);

    audit_log(current_user()['id'] ?? null, $flag === 'is_pinned' ? 'forum_topic_pin_toggle' : 'forum_topic_lock_toggle', 'infusion_forum_topics', (int)$topic['id'], [
        'value' => $value ? 1 : 0,
    ]);
    moderation_log(current_user()['id'] ?? null, $flag === 'is_pinned'
        ? ($value ? 'forum_topic_pinned' : 'forum_topic_unpinned')
        : ($value ? 'forum_topic_locked' : 'forum_topic_unlocked'), 'forum_topic', (int)$topic['id'], [
        'target_label' => (string)$topic['title'],
        'context_type' => 'forum',
        'context_id' => (int)$topic['forum_id'],
        'details' => [
            'flag' => $flag,
            'value' => $value ? 1 : 0,
            'forum_id' => (int)$topic['forum_id'],
        ],
    ]);

    return [true, $flag === 'is_pinned'
        ? ($value ? __('forum.message.topic_pinned') : __('forum.message.topic_unpinned'))
        : ($value ? __('forum.message.topic_locked') : __('forum.message.topic_unlocked')),
        (int)$topic['forum_id']];
}

function forum_delete_topic($topicId)
{
    forum_ensure_schema();

    $topic = forum_get_topic($topicId);
    if (!$topic) {
        return [false, __('forum.topic.not_found'), null];
    }
    if (!forum_can_moderate_topic($topic)) {
        return [false, __('forum.message.delete_topic_denied'), null];
    }

    $GLOBALS['pdo']->beginTransaction();
    try {
        forum_delete_attachments_for_topic((int)$topic['id']);

        $deletePosts = $GLOBALS['pdo']->prepare('DELETE FROM ' . forum_table_posts() . ' WHERE topic_id = :topic_id');
        $deletePosts->execute([':topic_id' => (int)$topic['id']]);

        $deleteTopic = $GLOBALS['pdo']->prepare('DELETE FROM ' . forum_table_topics() . ' WHERE id = :id');
        $deleteTopic->execute([':id' => (int)$topic['id']]);

        $GLOBALS['pdo']->commit();
    } catch (Throwable $e) {
        if ($GLOBALS['pdo']->inTransaction()) {
            $GLOBALS['pdo']->rollBack();
        }
        return [false, __('forum.message.topic_delete_failed'), null];
    }

    audit_log(current_user()['id'] ?? null, 'forum_topic_delete', 'infusion_forum_topics', (int)$topic['id'], [
        'title' => $topic['title'],
    ]);
    moderation_log(current_user()['id'] ?? null, 'forum_topic_deleted', 'forum_topic', (int)$topic['id'], [
        'target_label' => (string)$topic['title'],
        'context_type' => 'forum',
        'context_id' => (int)$topic['forum_id'],
        'details' => [
            'forum_id' => (int)$topic['forum_id'],
            'title' => (string)$topic['title'],
        ],
    ]);

    return [true, __('forum.message.topic_deleted'), (int)$topic['forum_id']];
}

function forum_update_reply($replyId, $content)
{
    forum_ensure_schema();
    forum_ensure_extended_schema();

    $reply = forum_get_reply($replyId);
    if (!$reply) {
        return [false, __('forum.reply.not_found'), null];
    }
    if (!forum_can_moderate_reply($reply) && !forum_can_edit_own_reply($reply)) {
        return [false, __('forum.message.reply_edit_denied'), null];
    }

    $content = forum_prepare_body($content, 15000);
    if ($content === '') {
        return [false, __('forum.validation.reply_content'), null];
    }

    $stmt = $GLOBALS['pdo']->prepare('
        UPDATE ' . forum_table_posts() . '
        SET content = :content,
            updated_at = ' . (forum_update_time_on_edit_enabled() ? 'NOW()' : 'updated_at') . '
        WHERE id = :id
    ');
    $stmt->execute([
        ':content' => $content,
        ':id' => (int)$reply['id'],
    ]);

    $topicUpdate = $GLOBALS['pdo']->prepare('UPDATE ' . forum_table_topics() . ' SET updated_at = NOW() WHERE id = :id');
    $topicUpdate->execute([':id' => (int)$reply['topic_id']]);

    audit_log(current_user()['id'] ?? null, 'forum_reply_update', 'infusion_forum_posts', (int)$reply['id'], [
        'topic_id' => (int)$reply['topic_id'],
    ]);
    moderation_log(current_user()['id'] ?? null, 'forum_reply_updated', 'forum_reply', (int)$reply['id'], [
        'target_label' => moderation_log_excerpt($content),
        'context_type' => 'forum_topic',
        'context_id' => (int)$reply['topic_id'],
        'details' => [
            'topic_id' => (int)$reply['topic_id'],
            'forum_id' => (int)$reply['forum_id'],
        ],
    ]);

    return [true, __('forum.message.reply_updated'), (int)$reply['topic_id']];
}

function forum_delete_reply($replyId)
{
    forum_ensure_schema();

    $reply = forum_get_reply($replyId);
    if (!$reply) {
        return [false, __('forum.reply.not_found'), null];
    }
    if (!forum_can_moderate_reply($reply)) {
        return [false, __('forum.message.reply_delete_denied'), null];
    }

    forum_delete_attachments_for_post((int)$reply['id']);
    $stmt = $GLOBALS['pdo']->prepare('DELETE FROM ' . forum_table_posts() . ' WHERE id = :id');
    $stmt->execute([':id' => (int)$reply['id']]);
    forum_sync_topic_activity((int)$reply['topic_id']);

    audit_log(current_user()['id'] ?? null, 'forum_reply_delete', 'infusion_forum_posts', (int)$reply['id'], [
        'topic_id' => (int)$reply['topic_id'],
    ]);
    moderation_log(current_user()['id'] ?? null, 'forum_reply_deleted', 'forum_reply', (int)$reply['id'], [
        'target_label' => moderation_log_excerpt((string)$reply['content']),
        'context_type' => 'forum_topic',
        'context_id' => (int)$reply['topic_id'],
        'details' => [
            'topic_id' => (int)$reply['topic_id'],
            'forum_id' => (int)$reply['forum_id'],
        ],
    ]);

    return [true, __('forum.message.reply_deleted'), (int)$reply['topic_id']];
}

function forum_render_editor_toolbar($textareaId)
{
    foreach (forum_bbcode_buttons() as $button) {
        echo '<button type="button" class="btn btn-sm btn-outline-secondary" data-forum-editor-target="' . e($textareaId) . '" data-forum-insert-text="' . e($button['insert']) . '">' . e($button['label']) . '</button>';
    }
}

function forum_render_smileys($textareaId)
{
    foreach (forum_smileys() as $smiley) {
        $code = (string)($smiley['code'] ?? '');
        if ($code === '') {
            continue;
        }

        echo '<button type="button" class="btn btn-sm btn-outline-warning" data-forum-editor-target="' . e($textareaId) . '" data-forum-smiley-code="' . e($code) . '" title="' . e($smiley['title'] ?? $code) . '">' . site_smiley_button_html($smiley, 'forum-smiley') . '</button>';
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

require_once __DIR__ . '/support/load.php';

forum_register_assets();
forum_ensure_schema();
forum_ensure_extended_schema();
