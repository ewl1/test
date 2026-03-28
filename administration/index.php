<?php
require_once __DIR__ . '/_guard.php';
include THEMES . 'default/admin_header.php';
?>
<div class="admin-dashboard-hero d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h1 class="admin-dashboard-title mb-1"><?= e(__('admin.dashboard')) ?></h1>
        <div class="admin-dashboard-subtitle">Mini CMS v<?= e(app_version()) ?> · PHP <?= e(PHP_VERSION) ?></div>
    </div>
    <a class="btn btn-outline-secondary" href="<?= public_path('index.php') ?>"><?= e(__('admin.site')) ?></a>
</div>

<div class="row g-3 admin-dashboard-grid">
    <div class="col-lg-4">
        <div class="card admin-dashboard-card h-100">
            <div class="card-header">Branduolys</div>
            <div class="list-group list-group-flush">
                <a class="list-group-item list-group-item-action" href="settings.php"><?= e(__('nav.admin.settings')) ?></a>
                <a class="list-group-item list-group-item-action" href="themes.php"><?= e(__('nav.admin.themes')) ?></a>
                <a class="list-group-item list-group-item-action" href="navigation.php"><?= e(__('nav.admin.navigation')) ?></a>
                <a class="list-group-item list-group-item-action" href="diagnostics.php"><?= e(__('nav.admin.diagnostics')) ?></a>
                <a class="list-group-item list-group-item-action" href="smileys.php">Šypsenėlės</a>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card admin-dashboard-card h-100">
            <div class="card-header">Moduliai</div>
            <div class="list-group list-group-flush">
                <a class="list-group-item list-group-item-action" href="infusions.php"><?= e(__('nav.admin.infusions')) ?></a>
                <a class="list-group-item list-group-item-action" href="panels.php">Panelių išdėstymas</a>
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
                <a class="list-group-item list-group-item-action" href="error-logs.php"><?= e(__('admin.error_log')) ?></a>
            </div>
        </div>
    </div>
</div>
<?php include THEMES . 'default/admin_footer.php'; ?>
