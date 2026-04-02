<?php
// Migration 1.0.1 — add download_thumbnail column
$GLOBALS['pdo']->exec("
    ALTER TABLE " . DB_DOWNLOADS . "
    ADD COLUMN download_thumbnail VARCHAR(191) NOT NULL DEFAULT ''
    AFTER download_url
");
