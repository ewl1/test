<?php

namespace App\MiniCMS\Installer;

class DatabaseSchema
{
    public function statements(): array
    {
        return array_merge(
            $this->tableStatements(),
            $this->seedStatements()
        );
    }

    private function tableStatements(): array
    {
        return [
            <<<'SQL'
CREATE TABLE IF NOT EXISTS roles (
    id INT UNSIGNED PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE,
    level INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL,
            <<<'SQL'
CREATE TABLE IF NOT EXISTS permissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL,
            <<<'SQL'
CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT UNSIGNED NOT NULL,
    permission_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL,
            <<<'SQL'
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    admin_password VARCHAR(255) DEFAULT NULL,
    role_id INT UNSIGNED NOT NULL DEFAULT 4,
    is_active TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('active','inactive','blocked','deleted') NOT NULL DEFAULT 'inactive',
    avatar VARCHAR(255) DEFAULT NULL,
    signature TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL,
            <<<'SQL'
CREATE TABLE IF NOT EXISTS site_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(120) NOT NULL UNIQUE,
    setting_value TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL,
            <<<'SQL'
CREATE TABLE IF NOT EXISTS navigation_links (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(120) NOT NULL,
    url VARCHAR(255) NOT NULL DEFAULT '#',
    parent_id INT UNSIGNED DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL,
            <<<'SQL'
CREATE TABLE IF NOT EXISTS infusions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    folder VARCHAR(120) NOT NULL UNIQUE,
    is_installed TINYINT(1) NOT NULL DEFAULT 0,
    is_enabled TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL,
            <<<'SQL'
CREATE TABLE IF NOT EXISTS infusion_panels (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    infusion_id INT UNSIGNED DEFAULT NULL,
    panel_name VARCHAR(120) NOT NULL,
    position ENUM('left','u_center','l_center','right','au_center','bl_center') NOT NULL DEFAULT 'left',
    sort_order INT NOT NULL DEFAULT 0,
    is_enabled TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL,
            <<<'SQL'
CREATE TABLE IF NOT EXISTS posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED DEFAULT NULL,
    title VARCHAR(190) NOT NULL,
    content MEDIUMTEXT NOT NULL,
    status ENUM('draft','published') NOT NULL DEFAULT 'draft',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL,
            <<<'SQL'
CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100) DEFAULT NULL,
    entity_id BIGINT UNSIGNED DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    method VARCHAR(10) DEFAULT NULL,
    url VARCHAR(255) DEFAULT NULL,
    details TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL,
            <<<'SQL'
CREATE TABLE IF NOT EXISTS infusion_versions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    infusion_id INT UNSIGNED NOT NULL,
    version VARCHAR(50) NOT NULL,
    installed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_infusion_version (infusion_id, version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL,
            <<<'SQL'
CREATE TABLE IF NOT EXISTS infusion_admin_menu (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    infusion_id INT UNSIGNED NOT NULL,
    title VARCHAR(120) NOT NULL,
    slug VARCHAR(120) NOT NULL,
    permission_slug VARCHAR(120) DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uniq_infusion_slug (infusion_id, slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL,
            <<<'SQL'
CREATE TABLE IF NOT EXISTS infusion_migration_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    infusion_id INT UNSIGNED NOT NULL,
    step_version VARCHAR(50) NOT NULL,
    direction ENUM('up','down') NOT NULL DEFAULT 'up',
    status ENUM('started','done','failed','skipped') NOT NULL DEFAULT 'started',
    started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    finished_at DATETIME DEFAULT NULL,
    message TEXT DEFAULT NULL,
    UNIQUE KEY uniq_infusion_step_direction (infusion_id, step_version, direction)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL,
            <<<'SQL'
CREATE TABLE IF NOT EXISTS infusion_rollback_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    infusion_id INT UNSIGNED NOT NULL,
    failed_step VARCHAR(50) NOT NULL,
    rollback_step VARCHAR(50) DEFAULT NULL,
    status ENUM('started','done','failed','skipped') NOT NULL DEFAULT 'started',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    message TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL,
            <<<'SQL'
CREATE TABLE IF NOT EXISTS auth_rate_limits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    scope VARCHAR(32) NOT NULL,
    identifier VARCHAR(191) NOT NULL,
    attempts INT NOT NULL DEFAULT 0,
    first_attempt_at DATETIME NOT NULL,
    last_attempt_at DATETIME NOT NULL,
    locked_until DATETIME NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_auth_rate_limits_scope_identifier (scope, identifier),
    KEY idx_auth_rate_limits_scope_locked (scope, locked_until),
    KEY idx_auth_rate_limits_last_attempt (last_attempt_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL,
            <<<'SQL'
CREATE TABLE IF NOT EXISTS security_rate_limits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    scope VARCHAR(64) NOT NULL,
    identifier VARCHAR(191) NOT NULL,
    attempts INT NOT NULL DEFAULT 0,
    first_attempt_at DATETIME NOT NULL,
    last_attempt_at DATETIME NOT NULL,
    locked_until DATETIME NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_security_rate_limits_scope_identifier (scope, identifier),
    KEY idx_security_rate_limits_scope_locked (scope, locked_until),
    KEY idx_security_rate_limits_last_attempt (last_attempt_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL,
            <<<'SQL'
CREATE TABLE IF NOT EXISTS password_resets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    email VARCHAR(190) NOT NULL,
    token_hash CHAR(64) NOT NULL,
    requested_ip VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_password_resets_token_hash (token_hash),
    KEY idx_password_resets_user_id (user_id),
    KEY idx_password_resets_email (email),
    KEY idx_password_resets_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL,
            <<<'SQL'
CREATE TABLE IF NOT EXISTS user_profile_ratings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profile_user_id INT UNSIGNED NOT NULL,
    author_user_id INT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_profile_rating (profile_user_id, author_user_id),
    KEY idx_profile_rating_profile (profile_user_id, updated_at, id),
    KEY idx_profile_rating_author (author_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL,
            <<<'SQL'
CREATE TABLE IF NOT EXISTS user_profile_comments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profile_user_id INT UNSIGNED NOT NULL,
    author_user_id INT UNSIGNED NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_profile_comment_profile (profile_user_id, created_at, id),
    KEY idx_profile_comment_author (author_user_id, created_at),
    KEY idx_profile_comment_recent (created_at, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL,
            <<<'SQL'
CREATE TABLE IF NOT EXISTS ip_bans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip_address VARBINARY(16) NOT NULL,
    reason VARCHAR(255) DEFAULT NULL,
    banned_until DATETIME DEFAULT NULL,
    is_permanent TINYINT(1) NOT NULL DEFAULT 0,
    created_by INT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_ip_bans_address (ip_address),
    KEY idx_ip_bans_until (banned_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL,
        ];
    }

    private function seedStatements(): array
    {
        $statements = [];

        $statements[] = $this->buildInsertSql('roles', ['id', 'name', 'slug', 'level'], [
            ['id' => 1, 'name' => 'Vyr. administratorius', 'slug' => 'super_admin', 'level' => 100],
            ['id' => 2, 'name' => 'Administratorius', 'slug' => 'admin', 'level' => 80],
            ['id' => 3, 'name' => 'Moderatorius', 'slug' => 'moderator', 'level' => 60],
            ['id' => 4, 'name' => 'Narys', 'slug' => 'member', 'level' => 20],
            ['id' => 5, 'name' => 'Svecias', 'slug' => 'guest', 'level' => 0],
        ], 'name = VALUES(name), level = VALUES(level)');

        $statements[] = $this->buildInsertSql('permissions', ['name', 'slug', 'description'], [
            ['name' => 'Admin prieiga', 'slug' => 'admin.access', 'description' => 'Patekti i administracijos skydeli'],
            ['name' => 'Irasu kurimas', 'slug' => 'posts.create', 'description' => 'Kurti irasus'],
            ['name' => 'Irasu redagavimas', 'slug' => 'posts.edit', 'description' => 'Redaguoti irasus'],
            ['name' => 'Irasu trynimas', 'slug' => 'posts.delete', 'description' => 'Trinti irasus'],
            ['name' => 'Valdyti narius', 'slug' => 'users.manage', 'description' => 'Aktyvuoti, blokuoti, trinti, redaguoti'],
            ['name' => 'Leidimu valdymas', 'slug' => 'permissions.manage', 'description' => 'Valdyti roliu leidimus'],
            ['name' => 'Audit zurnalo perziura', 'slug' => 'audit.view', 'description' => 'Perziureti audit zurnala'],
            ['name' => 'Klaidu zurnalu perziura', 'slug' => 'logs.view', 'description' => 'Perziureti klaidu zurnalus'],
            ['name' => 'IP draudimu valdymas', 'slug' => 'ipban.manage', 'description' => 'Valdyti IP draudimu sarasa'],
            ['name' => 'Nustatymu valdymas', 'slug' => 'settings.manage', 'description' => 'Svetaines nustatymu valdymas'],
            ['name' => 'Temu valdymas', 'slug' => 'themes.manage', 'description' => 'Temu valdymas'],
            ['name' => 'Navigacijos valdymas', 'slug' => 'navigation.manage', 'description' => 'Navigacijos valdymas'],
            ['name' => 'Infusion moduliu valdymas', 'slug' => 'infusions.manage', 'description' => 'Valdyti infusion modulius'],
            ['name' => 'Paneliu valdymas', 'slug' => 'panels.manage', 'description' => 'Paneliu isdestymo valdymas'],
            ['name' => 'Roliu valdymas', 'slug' => 'roles.manage', 'description' => 'Roliu valdymas'],
            ['name' => 'Vartotoju perziura', 'slug' => 'users.view', 'description' => 'Vartotoju perziura'],
            ['name' => 'Vartotoju kurimas', 'slug' => 'users.create', 'description' => 'Vartotoju kurimas'],
            ['name' => 'Vartotoju redagavimas', 'slug' => 'users.edit', 'description' => 'Vartotoju redagavimas'],
            ['name' => 'Vartotoju busenos valdymas', 'slug' => 'users.status', 'description' => 'Vartotoju aktyvavimas ir blokavimas'],
            ['name' => 'Vartotoju trynimas', 'slug' => 'users.delete', 'description' => 'Vartotoju trynimas'],
        ], 'name = VALUES(name), description = VALUES(description)');

        $statements[] = "INSERT IGNORE INTO role_permissions (role_id, permission_id) SELECT 1, id FROM permissions";
        $statements[] = "INSERT IGNORE INTO role_permissions (role_id, permission_id) SELECT 2, id FROM permissions WHERE slug IN ('admin.access','posts.create','posts.edit','posts.delete','users.manage','permissions.manage','audit.view','logs.view','ipban.manage','settings.manage','themes.manage','navigation.manage','infusions.manage','panels.manage','roles.manage','users.view','users.create','users.edit','users.status','users.delete')";
        $statements[] = "INSERT IGNORE INTO role_permissions (role_id, permission_id) SELECT 3, id FROM permissions WHERE slug IN ('users.view','audit.view')";

        $statements[] = $this->buildInsertSql('site_settings', ['setting_key', 'setting_value'], [
            ['setting_key' => 'site_name', 'setting_value' => 'Mini CMS Pro'],
            ['setting_key' => 'site_description', 'setting_value' => 'Mini CMS Pro svetaine'],
            ['setting_key' => 'site_keywords', 'setting_value' => 'cms, php, mysql'],
            ['setting_key' => 'site_maintenance', 'setting_value' => '0'],
            ['setting_key' => 'site_locale', 'setting_value' => 'lt'],
            ['setting_key' => 'show_memory_usage', 'setting_value' => '0'],
            ['setting_key' => 'show_memory_usage_visibility', 'setting_value' => 'admin'],
            ['setting_key' => 'show_counter', 'setting_value' => '0'],
            ['setting_key' => 'show_counter_visibility', 'setting_value' => 'all'],
            ['setting_key' => 'counter_value', 'setting_value' => '0'],
            ['setting_key' => 'show_banners', 'setting_value' => '0'],
            ['setting_key' => 'show_banners_visibility', 'setting_value' => 'all'],
            ['setting_key' => 'show_sublinks', 'setting_value' => '1'],
            ['setting_key' => 'copyright_text', 'setting_value' => '© Mini CMS Pro'],
            ['setting_key' => 'current_theme', 'setting_value' => 'default'],
            ['setting_key' => 'admin_theme', 'setting_value' => 'default'],
        ], 'setting_value = VALUES(setting_value)');

        return $statements;
    }

    private function buildInsertSql(string $table, array $columns, array $rows, string $onDuplicate): string
    {
        $columnSql = implode(', ', array_map(static fn ($column) => '`' . $column . '`', $columns));
        $valueSql = [];

        foreach ($rows as $row) {
            $values = [];
            foreach ($columns as $column) {
                $values[] = $this->quoteValue($row[$column] ?? null);
            }
            $valueSql[] = '(' . implode(', ', $values) . ')';
        }

        return 'INSERT INTO ' . $table . ' (' . $columnSql . ') VALUES ' . implode(",\n", $valueSql) . "\nON DUPLICATE KEY UPDATE " . $onDuplicate;
    }

    private function quoteValue($value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if ($value === 'NOW()') {
            return 'NOW()';
        }

        if (is_int($value) || is_float($value)) {
            return (string)$value;
        }

        return "'" . str_replace(
            ["\\", "\0", "\n", "\r", "\x1a", "'"],
            ["\\\\", "\\0", "\\n", "\\r", "\\Z", "\\'"],
            (string)$value
        ) . "'";
    }
}
