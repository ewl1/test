<?php require_once __DIR__ . '/_guard.php'; include THEMES . 'default/admin_header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h3 mb-0">Admin Dashboard</h1><a class="btn btn-outline-secondary" href="<?= public_path('index.php') ?>">Svetainė</a></div>
<div class="row g-3 admin-dashboard-grid">
<div class="col-lg-4"><div class="card"><div class="card-header">Branduolys</div><div class="list-group list-group-flush">
<a class="list-group-item list-group-item-action" href="settings.php">Svetainės nustatymai</a>
<a class="list-group-item list-group-item-action" href="themes.php">Temos</a>
<a class="list-group-item list-group-item-action" href="navigation.php">Navigacija</a>
</div></div></div>
<div class="col-lg-4"><div class="card"><div class="card-header">Moduliai</div><div class="list-group list-group-flush">
<a class="list-group-item list-group-item-action" href="infusions.php">Infusions</a>
<a class="list-group-item list-group-item-action" href="panels.php">Panelių išdėstymas</a>
</div></div></div>
<div class="col-lg-4"><div class="card"><div class="card-header">Administravimas</div><div class="list-group list-group-flush">
<a class="list-group-item list-group-item-action" href="roles.php">Rolės</a>
<a class="list-group-item list-group-item-action" href="permissions.php">Leidimai</a>
<a class="list-group-item list-group-item-action" href="users.php">Nariai</a>
</div></div></div>
</div>
<?php include THEMES . 'default/admin_footer.php'; ?>
