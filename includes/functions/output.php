<?php
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
    return '<span class="badge text-bg-secondary">Memory: ' . number_format(memory_get_peak_usage(true) / 1048576, 2) . ' MB</span>';
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
    return '<div class="alert alert-info mb-0">Banner placeholder</div>';
}
function showcopyright()
{
    return '<div class="small text-secondary">' . e(setting('copyright_text', '© ' . date('Y') . ' ' . APP_NAME)) . '</div>';
}
