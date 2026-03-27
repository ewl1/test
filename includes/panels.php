<?php
function render_member_panel()
{
    $user = current_user();
    if (!$user) {
        return '';
    }

    $html = '<div class="card mb-3 member-panel">';
    $html .= '<div class="card-header">Nario panelė</div>';
    $html .= '<div class="card-body">';
    $html .= '<div class="d-flex align-items-center gap-3 mb-3">';
    $html .= '<a class="member-panel-avatar-link" href="' . e(user_profile_url((int)$user['id'])) . '">';
    $html .= '<img src="' . escape_url(user_avatar_url($user)) . '" alt="" class="member-panel-avatar">';
    $html .= '</a>';
    $html .= '<div class="member-panel-meta">';
    $html .= '<a class="member-panel-name text-decoration-none" href="' . e(user_profile_url((int)$user['id'])) . '">' . e($user['username']) . '</a>';
    $html .= '<div class="text-secondary small">' . e($user['email']) . '</div>';
    $html .= '</div></div>';
    $html .= '<div class="list-group list-group-flush member-panel-links">';
    if (has_permission($GLOBALS['pdo'], (int)$user['id'], 'admin.access')) {
        $html .= '<a class="list-group-item list-group-item-action" href="' . e(public_path('administration/index.php')) . '">Admin Dashboard</a>';
    }
    $html .= '<a class="list-group-item list-group-item-action" href="' . e(public_path('profile.php')) . '">Profilio redagavimas</a>';
    $html .= '<a class="list-group-item list-group-item-action" href="' . e(user_profile_url((int)$user['id'])) . '">Viešas profilis</a>';
    $html .= '<form method="post" action="' . e(public_path('logout.php')) . '" class="mt-3">';
    $html .= csrf_field();
    $html .= '<button class="btn btn-outline-secondary w-100" type="submit">Atsijungti</button>';
    $html .= '</form>';
    $html .= '</div></div></div>';

    return $html;
}

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
    $out = $position === 'right' ? render_member_panel() : '';
    foreach (fetch_panels_by_position($position) as $panel) {
        $out .= render_panel_item($panel);
    }
    return $out;
}
