SET NAMES utf8mb4;

CREATE TABLE roles (
    id INT UNSIGNED PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE,
    level INT NOT NULL DEFAULT 0
);

CREATE TABLE permissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT NULL
);

CREATE TABLE role_permissions (
    role_id INT UNSIGNED NOT NULL,
    permission_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id)
);

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(60) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role_id INT UNSIGNED NOT NULL DEFAULT 4,
    avatar VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 0,
    activation_token VARCHAR(255) DEFAULT NULL,
    activation_expires DATETIME DEFAULT NULL,
    reset_token VARCHAR(255) DEFAULT NULL,
    reset_expires DATETIME DEFAULT NULL,
    status ENUM('active','inactive','blocked','deleted') NOT NULL DEFAULT 'inactive',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED DEFAULT NULL,
    title VARCHAR(255) NOT NULL,
    content MEDIUMTEXT NOT NULL,
    status ENUM('draft','published') NOT NULL DEFAULT 'draft',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL
);

CREATE TABLE shouts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED DEFAULT NULL,
    message TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL
);

CREATE TABLE settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL
);

CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100) DEFAULT NULL,
    entity_id BIGINT UNSIGNED DEFAULT NULL,
    ip_address VARBINARY(16) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    method VARCHAR(10) DEFAULT NULL,
    url VARCHAR(255) DEFAULT NULL,
    details TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE ip_bans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip_address VARBINARY(16) NOT NULL,
    reason VARCHAR(255) DEFAULT NULL,
    banned_until DATETIME DEFAULT NULL,
    is_permanent TINYINT(1) NOT NULL DEFAULT 0,
    created_by INT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_ip (ip_address)
);

CREATE TABLE rate_limits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip_address VARBINARY(16) NOT NULL,
    action_key VARCHAR(100) NOT NULL,
    attempts INT NOT NULL DEFAULT 1,
    window_start DATETIME NOT NULL,
    last_attempt DATETIME NOT NULL,
    INDEX idx_ip_action (ip_address, action_key)
);

INSERT INTO roles (id, name, slug, level) VALUES
(1, 'Vyr. administratorius', 'super_admin', 100),
(2, 'Administratorius', 'admin', 80),
(3, 'Moderatorius', 'moderator', 60),
(4, 'Narys', 'member', 20),
(5, 'Svečias', 'guest', 0);

INSERT INTO permissions (name, slug, description) VALUES
('Admin prieiga', 'admin.access', 'Patekti į admin panelę'),
('Kurti postus', 'posts.create', 'Kurti postus'),
('Redaguoti postus', 'posts.edit', 'Redaguoti postus'),
('Trinti postus', 'posts.delete', 'Trinti postus'),
('Valdyti narius', 'users.manage', 'Aktyvuoti, blokuoti, trinti, redaguoti'),
('Valdyti leidimus', 'permissions.manage', 'Keisti role_permissions'),
('Moderuoti šaukyklą', 'shoutbox.moderate', 'Redaguoti / trinti šaukyklos žinutes'),
('Žiūrėti audit log', 'audit.view', 'Peržiūrėti audit log'),
('Valdyti IP ban', 'ipban.manage', 'IP ban sąrašas'),
('Valdyti nustatymus', 'settings.manage', 'Globalūs nustatymai');

INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions;

INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions WHERE slug IN ('admin.access','posts.create','posts.edit','posts.delete','users.manage','shoutbox.moderate','audit.view','settings.manage');

INSERT INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions WHERE slug IN ('admin.access','posts.edit','shoutbox.moderate','audit.view');

INSERT INTO settings (setting_key, setting_value) VALUES ('posts_per_page', '10');

INSERT INTO users (username, email, password, role_id, is_active, status, created_at)
VALUES ('admin', 'admin@example.com', '$2y$10$N9qo8uLOickgx2ZMRZo4i.U4FQm4y2k0x5E2osFVJeFt3uTJS3BMK', 1, 1, 'active', NOW());
-- password123

INSERT INTO posts (user_id, title, content, status, created_at, updated_at) VALUES
(1, 'Sveiki atvykę', 'Pirmasis demo postas iš Mini CMS Pro.', 'published', NOW(), NOW());

INSERT INTO shouts (user_id, message, created_at, updated_at) VALUES
(1, '[b]Labas![/b] Čia demo šaukyklos žinutė.', NOW(), NOW());
