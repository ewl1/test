<?php
$columns = $GLOBALS['pdo']->query("SHOW COLUMNS FROM infusion_news LIKE 'slug'")->fetchAll();
if (!$columns) {
    $GLOBALS['pdo']->exec("ALTER TABLE infusion_news ADD COLUMN slug VARCHAR(190) NULL AFTER title");
    $GLOBALS['pdo']->exec("UPDATE infusion_news SET slug = LOWER(REPLACE(title, ' ', '-')) WHERE slug IS NULL");
}
