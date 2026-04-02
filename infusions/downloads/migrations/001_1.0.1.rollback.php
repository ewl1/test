<?php
// Rollback 1.0.1 — remove download_thumbnail column
$GLOBALS['pdo']->exec("
    ALTER TABLE " . DB_DOWNLOADS . "
    DROP COLUMN download_thumbnail
");
