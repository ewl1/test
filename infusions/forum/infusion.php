<?php
$GLOBALS['pdo']->exec("
CREATE TABLE IF NOT EXISTS infusion_forum_threads (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subject VARCHAR(190) NOT NULL,
    replies INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
)");
$count = (int)$GLOBALS['pdo']->query("SELECT COUNT(*) FROM infusion_forum_threads")->fetchColumn();
if ($count === 0) {
    $stmt = $GLOBALS['pdo']->prepare("INSERT INTO infusion_forum_threads (subject, replies) VALUES (:s,:r)");
    $stmt->execute([':s' => 'Sveiki atvykę į forumą', ':r' => 3]);
}
