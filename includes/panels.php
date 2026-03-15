<?php
function fetch_panels_by_position($position)
{
    $stmt = $GLOBALS['pdo']->prepare("
        SELECT p.*, i.folder
        FROM infusion_panels p
        LEFT JOIN infusions i ON i.id = p.infusion_id
        WHERE p.position = :position AND p.is_enabled = 1
        ORDER BY p.sort_order ASC, p.id ASC
    ");
    $stmt->execute([':position' => $position]);
    return $stmt->fetchAll();
}

function render_panel_item(array $panel)
{
    $html = '<div class="card mb-3"><div class="card-header">' . e($panel['panel_name']) . '</div><div class="card-body">';
    $rendered = false;

    if (!empty($panel['folder'])) {
        $panelFile = INFUSIONS . $panel['folder'] . '/panel.php';
        if (file_exists($panelFile)) {
            ob_start();
            $panelData = $panel;
            include $panelFile;
            $html .= ob_get_clean();
            $rendered = true;
        }
    }

    if (!$rendered) {
        $html .= '<div class="text-secondary small">Panel placeholder: ' . e($panel['panel_name']) . '</div>';
    }

    $html .= '</div></div>';
    return $html;
}

function render_panels($position)
{
    $out = '';
    foreach (fetch_panels_by_position($position) as $panel) {
        $out .= render_panel_item($panel);
    }
    return $out;
}
