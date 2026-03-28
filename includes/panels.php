<?php
function render_member_panel()
{
    $user = current_user();
    if (!$user) {
        return '';
    }

    $html = '<div class="card mb-3 member-panel">';
    $html .= '<div class="card-header">' . e(__('member.panel')) . '</div>';
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
        $html .= '<a class="list-group-item list-group-item-action" href="' . e(public_path('administration/index.php')) . '">' . e(__('nav.admin.dashboard')) . '</a>';
    }
    $html .= '<a class="list-group-item list-group-item-action" href="' . e(public_path('profile.php')) . '">' . e(__('member.profile.edit')) . '</a>';
    $html .= '<a class="list-group-item list-group-item-action" href="' . e(user_profile_url((int)$user['id'])) . '">' . e(__('member.profile.public')) . '</a>';
    $html .= '<form method="post" action="' . e(public_path('logout.php')) . '" class="mt-3">';
    $html .= csrf_field();
    $html .= '<button class="btn btn-outline-secondary w-100" type="submit">' . e(__('member.logout')) . '</button>';
    $html .= '</form>';
    $html .= '</div></div></div>';

    return $html;
}

function render_latest_comments_panel()
{
    $comments = fetch_latest_profile_comments(6);

    $html = '<div class="card mb-3 latest-comments-panel">';
    $html .= '<div class="card-header">' . e(__('member.comments.latest')) . '</div>';
    $html .= '<div class="card-body">';

    if (!$comments) {
        $html .= '<div class="text-secondary small">Kol kas komentarų dar nėra.</div>';
        $html .= '</div></div>';
        return $html;
    }

    foreach ($comments as $comment) {
        $html .= '<article class="latest-comment-item">';
        $html .= '<div class="d-flex align-items-start gap-3">';
        $html .= '<img src="' . escape_url(user_avatar_url([
            'avatar' => $comment['author_avatar'] ?? null,
            'email' => $comment['author_email'] ?? null,
        ])) . '" alt="" class="member-panel-avatar">';
        $html .= '<div class="min-w-0 flex-grow-1">';
        $html .= '<div class="small fw-semibold">';
        $html .= '<a class="text-decoration-none" href="' . e(user_profile_url((int)$comment['author_user_id'])) . '">' . e($comment['author_username'] ?? __('member.none')) . '</a>';
        $html .= ' <span class="text-secondary fw-normal">apie</span> ';
        $html .= '<a class="text-decoration-none" href="' . e(profile_comment_url((int)$comment['profile_user_id'], (int)$comment['id'])) . '">' . e($comment['profile_username'] ?? 'profilį') . '</a>';
        $html .= '</div>';
        $html .= '<div class="small text-secondary mb-1">' . e(format_dt($comment['created_at'])) . '</div>';
        $html .= '<a class="latest-comment-excerpt text-decoration-none" href="' . e(profile_comment_url((int)$comment['profile_user_id'], (int)$comment['id'])) . '">' . profile_render_comment_body($comment['content']) . '</a>';
        $html .= '</div></div></article>';
    }

    $html .= '</div></div>';
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
    $title = trim((string)infusion_apply_filters('infusion.panel.title', (string)$panel['panel_name'], ['panel' => $panel]));
    if (!empty($panel['folder'])) {
        $title = trim((string)infusion_apply_filters('infusion.panel.title.' . $panel['folder'], $title, ['panel' => $panel]));
    }

    $html = '<div class="card mb-3"><div class="card-header">' . e($title) . '</div><div class="card-body">';
    $rendered = false;

    if (!empty($panel['folder'])) {
        $renderedPanel = render_infusion_panel($panel['folder'], $panel);
        $renderedHtml = (string)($renderedPanel['html'] ?? '');
        $customShell = !empty($renderedPanel['custom_shell']);

        if ($renderedHtml !== '') {
            if ($customShell) {
                return $renderedHtml;
            }

            $html .= $renderedHtml;
            $rendered = true;
        }
    }

    if (!$rendered) {
        $html .= '<div class="text-secondary small">Panelės vieta: ' . e($title) . '</div>';
    }

    $html .= '</div></div>';
    return $html;
}

function render_panels($position)
{
    $out = '';
    if ($position === 'right') {
        $out .= render_member_panel();
        $out .= render_latest_comments_panel();
    }
    foreach (fetch_panels_by_position($position) as $panel) {
        $out .= render_panel_item($panel);
    }
    return $out;
}
