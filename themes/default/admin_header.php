<?php
if (!defined('IN_ADMIN')) {
    define('IN_ADMIN', true);
}

$siteTitle = setting('site_name', __('site.title'));
$me = current_user();
$adminNavVisible = $me && is_admin_session_verified();
$can = function ($permission) use ($me, $adminNavVisible) {
    return $adminNavVisible && $me && has_permission($GLOBALS['pdo'], $me['id'], $permission);
};
$canUsers = $can('users.view') || $can('users.manage');
$canDiagnostics = $can('settings.manage') || $can('logs.view');
?>
<!DOCTYPE html>
<html lang="<?= e(site_locale()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($siteTitle) ?> - <?= e(__('admin.title')) ?></title>

<link rel="stylesheet" href="<?= asset_path('themes/default/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= asset_path('themes/default/admin.css') ?>">
<link rel="stylesheet" href="<?= asset_path('themes/default/css/all.min.css') ?>">
<?php foreach (get_registered_page_styles() as $stylePath): ?>
<link rel="stylesheet" href="<?= asset_path($stylePath) ?>">
<?php endforeach; ?>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark">
<div class="container-fluid">

<a class="navbar-brand" href="<?= public_path($adminNavVisible ? 'administration/index.php' : 'administration/login.php') ?>">
<i class="fa-solid fa-screwdriver-wrench"></i> <?= e(__('admin.title')) ?>
</a>

<?php if ($adminNavVisible): ?>
<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav" aria-label="<?= e(__('admin.title')) ?>">
<span class="navbar-toggler-icon"></span>
</button>

<div class="collapse navbar-collapse" id="adminNav">

<ul class="navbar-nav me-auto mb-2 mb-lg-0">
<li class="nav-item">
<a class="nav-link" href="<?= public_path('administration/index.php') ?>">
<i class="fa-solid fa-gauge"></i> <?= e(__('admin.dashboard')) ?>
</a>
</li>

<?php if ($canUsers): ?>
<li class="nav-item">
<a class="nav-link" href="<?= public_path('administration/users.php') ?>">
<i class="fa-solid fa-users"></i> <?= e(__('nav.admin.members')) ?>
</a>
</li>
<?php endif; ?>

<?php if ($can('roles.manage')): ?>
<li class="nav-item">
<a class="nav-link" href="<?= public_path('administration/roles.php') ?>">
<i class="fa-solid fa-user-shield"></i> <?= e(__('nav.admin.roles')) ?>
</a>
</li>
<?php endif; ?>

<?php if ($can('permissions.manage')): ?>
<li class="nav-item">
<a class="nav-link" href="<?= public_path('administration/permissions.php') ?>">
<i class="fa-solid fa-key"></i> <?= e(__('nav.admin.permissions')) ?>
</a>
</li>
<?php endif; ?>

<?php if ($can('audit.view')): ?>
<li class="nav-item">
<a class="nav-link" href="<?= public_path('administration/audit-logs.php') ?>">
<i class="fa-solid fa-clipboard-list"></i> <?= e(__('admin.audit')) ?>
</a>
</li>
<?php endif; ?>

<?php if ($can('logs.view')): ?>
<li class="nav-item">
<a class="nav-link" href="<?= public_path('administration/error-logs.php') ?>">
<i class="fa-solid fa-triangle-exclamation"></i> <?= e(__('admin.error_log')) ?>
</a>
</li>
<?php endif; ?>

<?php if ($canDiagnostics): ?>
<li class="nav-item">
<a class="nav-link" href="<?= public_path('administration/diagnostics.php') ?>">
<i class="fa-solid fa-stethoscope"></i> <?= e(__('nav.admin.diagnostics')) ?>
</a>
</li>
<?php endif; ?>

<?php if ($can('settings.manage')): ?>
<li class="nav-item">
<a class="nav-link" href="<?= public_path('administration/smileys.php') ?>">
<i class="fa-solid fa-face-smile"></i> Šypsenėlės
</a>
</li>
<?php endif; ?>

<?php if ($can('panels.manage')): ?>
<li class="nav-item">
<a class="nav-link" href="<?= public_path('administration/panels.php') ?>">
<i class="fa-solid fa-table-columns"></i> <?= e(__('nav.admin.panels')) ?>
</a>
</li>
<?php endif; ?>

<?php if ($can('infusions.manage')): ?>
<li class="nav-item">
<a class="nav-link" href="<?= public_path('administration/infusions.php') ?>">
<i class="fa-solid fa-puzzle-piece"></i> <?= e(__('nav.admin.infusions')) ?>
</a>
</li>
<?php endif; ?>
</ul>

<ul class="navbar-nav align-items-lg-center">
<li class="nav-item">
<span class="navbar-text me-3">
<i class="fa-solid fa-user"></i> <?= e($me['username'] ?? 'Admin') ?>
</span>
</li>

<li class="nav-item">
<a class="nav-link" href="<?= public_path('index.php') ?>">
<i class="fa-solid fa-globe"></i> <?= e(__('admin.site')) ?>
</a>
</li>

<li class="nav-item">
<form method="post" action="<?= public_path('logout.php') ?>" class="mb-0">
<?= csrf_field() ?>
<button class="nav-link btn btn-link border-0 p-0" type="submit" title="<?= e(__('admin.logout')) ?>">
<i class="fa-solid fa-right-from-bracket"></i>
</button>
</form>
</li>
</ul>

</div>
<?php else: ?>
<div class="ms-auto">
<a class="nav-link text-white" href="<?= public_path('index.php') ?>">
<i class="fa-solid fa-globe"></i> <?= e(__('admin.site')) ?>
</a>
</div>
<?php endif; ?>
</div>
</nav>

<div class="container-fluid mt-4">
