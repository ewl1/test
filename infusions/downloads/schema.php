<?php
require_once __DIR__ . '/bootstrap.php';

$GLOBALS['pdo']->exec("
CREATE TABLE IF NOT EXISTS " . DB_DOWNLOAD_CATS . " (
    download_cat_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    download_cat_name VARCHAR(191) NOT NULL,
    download_cat_description TEXT,
    download_cat_parent INT(10) UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (download_cat_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$GLOBALS['pdo']->exec("
CREATE TABLE IF NOT EXISTS " . DB_DOWNLOADS . " (
    download_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    download_cat_id INT(10) UNSIGNED NOT NULL DEFAULT 0,
    download_title VARCHAR(191) NOT NULL,
    download_description TEXT,
    download_file VARCHAR(191) NOT NULL DEFAULT '',
    download_url VARCHAR(191) NOT NULL DEFAULT '',
    download_thumbnail VARCHAR(191) NOT NULL DEFAULT '',
    download_size BIGINT(20) NOT NULL DEFAULT 0,
    download_count INT(10) UNSIGNED NOT NULL DEFAULT 0,
    download_datestamp INT(10) UNSIGNED NOT NULL DEFAULT 0,
    download_user INT(10) UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (download_id),
    KEY idx_downloads_cat (download_cat_id),
    KEY idx_downloads_user (download_user),
    KEY idx_downloads_date (download_datestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
