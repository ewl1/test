<?php
$columns = $GLOBALS['pdo']->query("SHOW COLUMNS FROM infusion_news LIKE 'slug'")->fetchAll();
if ($columns) {
    $GLOBALS['pdo']->exec("ALTER TABLE infusion_news DROP COLUMN slug");
}
