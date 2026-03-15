<?php
$GLOBALS['pdo']->exec("
CREATE TABLE IF NOT EXISTS infusion_news (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(190) NOT NULL,
    summary TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
)");
$count = (int)$GLOBALS['pdo']->query("SELECT COUNT(*) FROM infusion_news")->fetchColumn();
if ($count === 0) {
    $stmt = $GLOBALS['pdo']->prepare("INSERT INTO infusion_news (title, summary) VALUES (:t,:s)");
    $stmt->execute([':t' => 'Pirma naujiena', ':s' => 'Čia pavyzdinė naujienų infusion žinutė.']);
}
