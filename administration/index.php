<?php
require_once __DIR__ . '/_guard.php';
include THEMES . 'default/admin_header.php';
?>
<div class="<?= e(admin_layout_preset_class('dashboard')) ?>">
<?php
admin_render_page_header([
    'variant' => 'dashboard',
    'title' => __('admin.dashboard'),
    'subtitle' => 'Mini CMS v' . app_version() . ' | PHP ' . PHP_VERSION,
    'actions' => [
        [
            'label' => __('admin.site'),
            'href' => public_path('index.php'),
            'class' => 'btn btn-outline-secondary admin-action-button',
            'icon' => 'fa-solid fa-globe',
        ],
    ],
]);

admin_render_stat_strip([
    [
        'label' => 'Versija',
        'value' => 'v' . app_version(),
        'tone' => 'info',
        'icon' => 'fa-solid fa-code-branch',
    ],
    [
        'label' => 'PHP',
        'value' => PHP_VERSION,
        'tone' => 'info',
        'icon' => 'fa-brands fa-php',
    ],
    [
        'label' => 'OPcache',
        'value' => is_opcache_enabled() ? 'Ijungta' : 'Isjungta',
        'tone' => is_opcache_enabled() ? 'success' : 'warning',
        'icon' => 'fa-solid fa-gauge-high',
    ],
]);
?>

<div class="row g-3 admin-dashboard-grid admin-layout-dashboard-grid">
    <div class="col-lg-4">
        <div class="card admin-dashboard-card h-100">
            <div class="card-header">Branduolys</div>
            <div class="list-group list-group-flush">
                <a class="list-group-item list-group-item-action" href="settings.php"><?= e(__('nav.admin.settings')) ?></a>
                <a class="list-group-item list-group-item-action" href="themes.php"><?= e(__('nav.admin.themes')) ?></a>
                <a class="list-group-item list-group-item-action" href="navigation.php"><?= e(__('nav.admin.navigation')) ?></a>
                <a class="list-group-item list-group-item-action" href="diagnostics.php"><?= e(__('nav.admin.diagnostics')) ?></a>
                <a class="list-group-item list-group-item-action" href="smileys.php">&#352;ypsen&#279;l&#279;s</a>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card admin-dashboard-card h-100">
            <div class="card-header">Moduliai</div>
            <div class="list-group list-group-flush">
                <a class="list-group-item list-group-item-action" href="infusions.php"><?= e(__('nav.admin.infusions')) ?></a>
                <a class="list-group-item list-group-item-action" href="panels.php">Paneli&#371; i&#353;d&#279;stymas</a>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card admin-dashboard-card h-100">
            <div class="card-header">Administravimas</div>
            <div class="list-group list-group-flush">
                <a class="list-group-item list-group-item-action" href="roles.php"><?= e(__('nav.admin.roles')) ?></a>
                <a class="list-group-item list-group-item-action" href="permissions.php"><?= e(__('nav.admin.permissions')) ?></a>
                <a class="list-group-item list-group-item-action" href="users.php"><?= e(__('nav.admin.members')) ?></a>
                <a class="list-group-item list-group-item-action" href="audit-logs.php"><?= e(__('admin.audit')) ?></a>
                <a class="list-group-item list-group-item-action" href="security-logs.php">Saugumo &#382;urnalas</a>
                <a class="list-group-item list-group-item-action" href="moderation-logs.php">Moderavimo &#382;urnalas</a>
                <a class="list-group-item list-group-item-action" href="error-logs.php"><?= e(__('admin.error_log')) ?></a>
            </div>
        </div>
    </div>
</div>
</div>
<?php include THEMES . 'default/admin_footer.php'; ?>
