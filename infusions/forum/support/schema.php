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
