<?php
function panel_render_begin(array $panel = [])
{
    if (!isset($GLOBALS['_panel_render_stack']) || !is_array($GLOBALS['_panel_render_stack'])) {
        $GLOBALS['_panel_render_stack'] = [];
    }

    $GLOBALS['_panel_render_stack'][] = [
        'panel' => $panel,
        'custom_shell' => false,
        'shell_depth' => 0,
    ];
}

function panel_render_state()
{
    if (empty($GLOBALS['_panel_render_stack']) || !is_array($GLOBALS['_panel_render_stack'])) {
        return null;
    }

    return $GLOBALS['_panel_render_stack'][count($GLOBALS['_panel_render_stack']) - 1] ?? null;
}

function panel_render_mark_custom_shell()
{
    if (empty($GLOBALS['_panel_render_stack']) || !is_array($GLOBALS['_panel_render_stack'])) {
        return;
    }

    $index = count($GLOBALS['_panel_render_stack']) - 1;
    $GLOBALS['_panel_render_stack'][$index]['custom_shell'] = true;
}

function panel_render_push_shell()
{
    if (empty($GLOBALS['_panel_render_stack']) || !is_array($GLOBALS['_panel_render_stack'])) {
        return;
    }

    $index = count($GLOBALS['_panel_render_stack']) - 1;
    $GLOBALS['_panel_render_stack'][$index]['custom_shell'] = true;
    $GLOBALS['_panel_render_stack'][$index]['shell_depth']++;
}

function panel_render_pop_shell()
{
    if (empty($GLOBALS['_panel_render_stack']) || !is_array($GLOBALS['_panel_render_stack'])) {
        return;
    }

    $index = count($GLOBALS['_panel_render_stack']) - 1;
    $GLOBALS['_panel_render_stack'][$index]['shell_depth'] = max(0, (int)$GLOBALS['_panel_render_stack'][$index]['shell_depth'] - 1);
}

function panel_render_uses_custom_shell()
{
    $state = panel_render_state();
    return !empty($state['custom_shell']);
}

function panel_render_current_panel()
{
    $state = panel_render_state();
    return (array)($state['panel'] ?? []);
}

function panel_render_current_title($default = 'Panelė')
{
    $panel = panel_render_current_panel();
    $title = trim((string)($panel['panel_name'] ?? ''));
    return $title !== '' ? $title : (string)$default;
}

function panel_render_end()
{
    if (empty($GLOBALS['_panel_render_stack']) || !is_array($GLOBALS['_panel_render_stack'])) {
        return [
            'panel' => [],
            'custom_shell' => false,
            'shell_depth' => 0,
        ];
    }

    return array_pop($GLOBALS['_panel_render_stack']);
}

function render_side_panel_start($title = '', array $options = [])
{
    panel_render_push_shell();

    $title = trim((string)$title);
    if ($title === '') {
        $title = panel_render_current_title();
    }

    $panelClass = trim((string)($options['panel_class'] ?? 'card mb-3'));
    $headerClass = trim((string)($options['header_class'] ?? 'card-header'));
    $bodyClass = trim((string)($options['body_class'] ?? 'card-body'));

    $html = '<div class="' . e($panelClass) . '">';
    if ($title !== '') {
        $html .= '<div class="' . e($headerClass) . '">' . e($title) . '</div>';
    }
    $html .= '<div class="' . e($bodyClass) . '">';

    return $html;
}

function render_side_panel_end()
{
    panel_render_pop_shell();
    return '</div></div>';
}

function render_side_panel($title, $body, array $options = [])
{
    return render_side_panel_start($title, $options) . $body . render_side_panel_end();
}

function openside($title = '', array $options = [])
{
    echo render_side_panel_start($title, $options);
}

function closeside()
{
    echo render_side_panel_end();
}

function opentable($title = '', array $options = [])
{
    openside($title, $options);
}

function closetable()
{
    closeside();
}

function may_show_public_widget($settingKey)
{
    if (setting($settingKey, '0') !== '1') return false;
    $visibility = setting($settingKey . '_visibility', 'all');
    if ($visibility === 'all') return true;
    $user = current_user();
    return $user && has_permission($GLOBALS['pdo'], $user['id'], 'admin.access');
}

function showMemoryUsage()
{
    if (!may_show_public_widget('show_memory_usage')) return '';
    return '<span class="badge text-bg-secondary">Atmintis: ' . number_format(memory_get_peak_usage(true) / 1048576, 2) . ' MB</span>';
}

function showcounter()
{
    if (!may_show_public_widget('show_counter')) return '';
    $count = (int)setting('counter_value', '0') + 1;
    save_setting('counter_value', (string)$count);
    return '<span class="badge text-bg-primary">Lankytojas #' . $count . '</span>';
}

function showbanners()
{
    if (setting('show_banners', '0') !== '1') return '';
    return '<div class="alert alert-info mb-0">Banerio vieta</div>';
}

function showcopyright()
{
    return '<div class="small text-secondary">' . e(setting('copyright_text', '© ' . date('Y') . ' ' . APP_NAME)) . '</div>';
}
