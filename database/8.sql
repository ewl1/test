CREATE TABLE IF NOT EXISTS roles (
    id INT UNSIGNED PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE,
    level INT NOT NULL DEFAULT 0
);

INSERT INTO roles (id, name, slug, level) VALUES
(1,'Vyr. administratorius','super_admin',100),
(2,'Administratorius','admin',80),
(3,'Moderatorius','moderator',60),
(4,'Narys','member',20),
(5,'Svečias','guest',0)
ON DUPLICATE KEY UPDATE name=VALUES(name);

CREATE TABLE IF NOT EXISTS permissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT NULL
);

INSERT INTO permissions (name, slug, description) VALUES
('Admin prieiga','admin.access','Prieiga prie administracijos'),
('Nustatymų valdymas','settings.manage','Svetainės nustatymų valdymas'),
('Temų valdymas','themes.manage','Temų valdymas'),
('Navigacijos valdymas','navigation.manage','Navigacijos valdymas'),
('Infusions valdymas','infusions.manage','Infusions valdymas'),
('Panelių valdymas','panels.manage','Panelių išdėstymo valdymas'),
('Rolių valdymas','roles.manage','Rolių valdymas'),
('Leidimų valdymas','permissions.manage','Leidimų valdymas'),
('Vartotojų peržiūra','users.view','Vartotojų peržiūra'),
('Vartotojų kūrimas','users.create','Vartotojų kūrimas'),
('Vartotojų redagavimas','users.edit','Vartotojų redagavimas'),
('Vartotojų būsenos valdymas','users.status','Vartotojų aktyvavimas, blokavimas, deaktivacija'),
('Vartotojų trynimas','users.delete','Vartotojų trynimas')
ON DUPLICATE KEY UPDATE name=VALUES(name);

CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT UNSIGNED NOT NULL,
    permission_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id)
);

INSERT IGNORE INTO role_permissions (role_id, permission_id) SELECT 1, id FROM permissions;
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions WHERE slug IN ('admin.access','settings.manage','themes.manage','navigation.manage','infusions.manage','panels.manage','roles.manage','permissions.manage','users.view','users.create','users.edit','users.status','users.delete');

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role_id INT UNSIGNED NOT NULL DEFAULT 4,
    is_active TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('active','inactive','blocked','deleted') NOT NULL DEFAULT 'inactive',
    avatar VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS site_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(120) NOT NULL UNIQUE,
    setting_value TEXT DEFAULT NULL
);

INSERT INTO site_settings (setting_key, setting_value) VALUES
('site_name','Mini CMS Pro'),
('site_description','Mini CMS Pro svetainė'),
('site_keywords','cms, php, mysql'),
('site_maintenance','0'),
('show_memory_usage','0'),
('show_memory_usage_visibility','admin'),
('show_counter','0'),
('show_counter_visibility','all'),
('counter_value','0'),
('show_banners','0'),
('show_banners_visibility','all'),
('show_sublinks','1'),
('copyright_text','© Mini CMS Pro'),
('current_theme','default'),
('admin_theme','default')
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);

CREATE TABLE IF NOT EXISTS navigation_links (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(120) NOT NULL,
    url VARCHAR(255) NOT NULL DEFAULT '#',
    parent_id INT UNSIGNED DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS infusions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    folder VARCHAR(120) NOT NULL UNIQUE,
    is_installed TINYINT(1) NOT NULL DEFAULT 0,
    is_enabled TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS infusion_panels (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    infusion_id INT UNSIGNED DEFAULT NULL,
    panel_name VARCHAR(120) NOT NULL,
    position ENUM('left','u_center','l_center','right','au_center','bl_center') NOT NULL DEFAULT 'left',
    sort_order INT NOT NULL DEFAULT 0,
    is_enabled TINYINT(1) NOT NULL DEFAULT 1
);

INSERT IGNORE INTO infusion_panels (id, infusion_id, panel_name, position, sort_order, is_enabled) VALUES
(1,NULL,'News Panel','left',1,1),
(2,NULL,'Forum Panel','right',1,1);

CREATE TABLE IF NOT EXISTS posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(190) NOT NULL,
    content MEDIUMTEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS shouts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    message TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

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
);


CREATE TABLE IF NOT EXISTS infusion_versions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    infusion_id INT UNSIGNED NOT NULL,
    version VARCHAR(50) NOT NULL,
    installed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_infusion_version (infusion_id, version)
);


CREATE TABLE IF NOT EXISTS infusion_admin_menu (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    infusion_id INT UNSIGNED NOT NULL,
    title VARCHAR(120) NOT NULL,
    slug VARCHAR(120) NOT NULL,
    permission_slug VARCHAR(120) DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uniq_infusion_slug (infusion_id, slug)
);
