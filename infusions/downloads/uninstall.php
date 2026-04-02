<?php
require_once __DIR__.'/bootstrap.php';

$GLOBALS['pdo']->exec("DROP TABLE IF EXISTS ".DB_DOWNLOADS);
$GLOBALS['pdo']->exec("DROP TABLE IF EXISTS ".DB_DOWNLOAD_CATS);
