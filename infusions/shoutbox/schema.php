<?php
$GLOBALS['pdo']->exec("
    CREATE TABLE IF NOT EXISTS infusion_shoutbox_messages (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED DEFAULT NULL,
        message TEXT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT NULL
    )
");

$count = (int)$GLOBALS['pdo']->query("SELECT COUNT(*) FROM infusion_shoutbox_messages")->fetchColumn();
if ($count === 0) {
    $hasLegacy = $GLOBALS['pdo']->query("SHOW TABLES LIKE 'shouts'")->fetchColumn();
    if ($hasLegacy) {
        $GLOBALS['pdo']->exec("
            INSERT INTO infusion_shoutbox_messages (user_id, message, created_at, updated_at)
            SELECT user_id, message, created_at, updated_at
            FROM shouts
            ORDER BY id ASC
        ");
    }
}
