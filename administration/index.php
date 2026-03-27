<?php
require_once __DIR__ . '/_guard.php';
include THEMES . 'default/admin_header.php';
?>
<div class="admin-dashboard-hero d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h1 class="admin-dashboard-title mb-1">Admin Dashboard</h1>
        <div class="admin-dashboard-subtitle">Mini CMS v<?= e(app_version()) ?> · PHP <?= e(PHP_VERSION) ?></div>
    </div>
    <a class="btn btn-outline-secondary" href="<?= public_path('index.php') ?>">Svetainė</a>
</div>

<div class="row g-3 admin-dashboard-grid">
    <div class="col-lg-4">
        <div class="card admin-dashboard-card h-100">
            <div class="card-header">Branduolys</div>
            <div class="list-group list-group-flush">
                <a class="list-group-item list-group-item-action" href="settings.php">Svetainės nustatymai</a>
                <a class="list-group-item list-group-item-action" href="themes.php">Temos</a>
                <a class="list-group-item list-group-item-action" href="navigation.php">Navigacija</a>
                <a class="list-group-item list-group-item-action" href="diagnostics.php">Diagnostika</a>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card admin-dashboard-card h-100">
            <div class="card-header">Moduliai</div>
            <div class="list-group list-group-flush">
                <a class="list-group-item list-group-item-action" href="infusions.php">Infusions</a>
                <a class="list-group-item list-group-item-action" href="panels.php">Panelių išdėstymas</a>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card admin-dashboard-card h-100">
            <div class="card-header">Administravimas</div>
            <div class="list-group list-group-flush">
                <a class="list-group-item list-group-item-action" href="roles.php">Rolės</a>
                <a class="list-group-item list-group-item-action" href="permissions.php">Leidimai</a>
                <a class="list-group-item list-group-item-action" href="users.php">Nariai</a>
                <a class="list-group-item list-group-item-action" href="audit-logs.php">Audit log</a>
                <a class="list-group-item list-group-item-action" href="error-logs.php">Error log</a>
            </div>
        </div>
    </div>
</div>
<?php include THEMES . 'default/admin_footer.php'; ?>
