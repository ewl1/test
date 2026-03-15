<?php
require_once __DIR__ . '/_guard.php';
require_permission('infusions.manage');

$folder = trim($_GET['folder'] ?? '');
if ($folder === '') {
    http_response_code(404);
    die('Nenurodyta infusion.');
}

$installed = get_installed_infusion_by_folder($folder);
if (!$installed) {
    http_response_code(404);
    die('Infusion neįdiegta.');
}

include THEMES . 'default/admin_header.php';
echo '<div class="d-flex justify-content-between align-items-center mb-3">';
echo '<h1 class="h3 mb-0">Infusion admin: ' . e($installed['name']) . '</h1>';
echo '<a class="btn btn-outline-secondary" href="infusions.php">Atgal</a>';
echo '</div>';

try {
    render_infusion_admin($folder);
} catch (Throwable $e) {
    echo '<div class="alert alert-danger">' . e($e->getMessage()) . '</div>';
}

include THEMES . 'default/admin_footer.php';
