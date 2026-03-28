<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!function_exists('render_shoutbox_page')) {
    include THEMES . setting('current_theme', CURRENT_THEME) . '/header.php';
    echo '<div class="alert alert-warning">' . e(__('shoutbox.unavailable')) . '</div>';
    include THEMES . setting('current_theme', CURRENT_THEME) . '/footer.php';
    return;
}

render_shoutbox_page();
