<?php
openside('Naujausi atsisiuntimai');

require_once __DIR__.'/bootstrap.php';

// Fetch 5 latest downloads
$stmt = $GLOBALS['pdo']->query("
    SELECT *
    FROM ".DB_DOWNLOADS."
    ORDER BY download_datestamp DESC
    LIMIT 5
");
$latest_downloads = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($latest_downloads) > 0) {
    echo '<ul class="list-group list-group-flush">';
    foreach ($latest_downloads as $download) {
        $download_link = BASEDIR.'downloads.php?action=download&id='.$download['download_id'];
        echo '<li class="list-group-item">';
        echo '<a href="'.$download_link.'" title="'.htmlspecialchars($download['download_description']).'">'.htmlspecialchars($download['download_title']).'</a>';
        echo '</li>';
    }
    echo '</ul>';
} else {
    echo '<div class="p-2">Atsisiuntimų nėra.</div>';
}

echo '<div class="text-center mt-2">';
echo '<a href="'.BASEDIR.'downloads.php" class="btn btn-sm btn-outline-secondary">Visi atsisiuntimai</a>';
echo '</div>';

closeside();