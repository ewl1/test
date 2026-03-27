<?php
if (!defined('IN_ADMIN')) define('IN_ADMIN', true);
$siteTitle = setting('site_name', 'Mini CMS');
$me = current_user();
$can = function ($permission) use ($me) {
    return $me && has_permission($GLOBALS['pdo'], $me['id'], $permission);
};
$canUsers = $can('users.view') || $can('users.manage');
$canDiagnostics = $can('settings.manage') || $can('logs.view');
?>
<!DOCTYPE html>
<html lang="lt">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($siteTitle) ?> - Administracija</title>

<link rel="stylesheet" href="<?= public_path('themes/default/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= public_path('themes/default/admin.css') ?>">
<link rel="stylesheet" href="<?= public_path('themes/default/css/all.min.css') ?>">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark">
<div class="container-fluid">

<a class="navbar-brand" href="<?= public_path('administration/index.php') ?>">
<i class="fa-solid fa-screwdriver-wrench"></i> Admin
</a>

<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
<span class="navbar-toggler-icon"></span>
</button>

<div class="collapse navbar-collapse" id="adminNav">

<ul class="navbar-nav me-auto mb-2 mb-lg-0">
<li class="nav-item">
<a class="nav-link" href="<?= public_path('administration/index.php') ?>">
<i class="fa-solid fa-gauge"></i> Dashboard
</a>
</li>

<?php if ($canUsers): ?>
<li class="nav-item">
<a class="nav-link" href="<?= public_path('administration/users.php') ?>">
<i class="fa-solid fa-users"></i> Nariai
</a>
</li>
<?php endif; ?>

<?php if ($can('roles.manage')): ?>
<li class="nav-item">
<a class="nav-link" href="<?= public_path('administration/roles.php') ?>">
<i class="fa-solid fa-user-shield"></i> Rolės
</a>
</li>
<?php endif; ?>

<?php if ($can('permissions.manage')): ?>
<li class="nav-item">
<a class="nav-link" href="<?= public_path('administration/permissions.php') ?>">
<i class="fa-solid fa-key"></i> Leidimai
</a>
</li>
<?php endif; ?>

<?php if ($can('audit.view')): ?>
<li class="nav-item">
<a class="nav-link" href="<?= public_path('administration/audit-logs.php') ?>">
<i class="fa-solid fa-clipboard-list"></i> Audit
</a>
</li>
<?php endif; ?>

<?php if ($can('logs.view')): ?>
<li class="nav-item">
<a class="nav-link" href="<?= public_path('administration/error-logs.php') ?>">
<i class="fa-solid fa-triangle-exclamation"></i> Error Log
</a>
</li>
<?php endif; ?>

<?php if ($canDiagnostics): ?>
<li class="nav-item">
<a class="nav-link" href="<?= public_path('administration/diagnostics.php') ?>">
<i class="fa-solid fa-stethoscope"></i> Diagnostika
</a>
</li>
<?php endif; ?>

<?php if ($can('panels.manage')): ?>
<li class="nav-item">
<a class="nav-link" href="<?= public_path('administration/panels.php') ?>">
<i class="fa-solid fa-table-columns"></i> Panelės
</a>
</li>
<?php endif; ?>

<?php if ($can('infusions.manage')): ?>
<li class="nav-item">
<a class="nav-link" href="<?= public_path('administration/infusions.php') ?>">
<i class="fa-solid fa-puzzle-piece"></i> Infusions
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
<i class="fa-solid fa-globe"></i> Svetainė
</a>
</li>

<li class="nav-item">
<form method="post" action="<?= public_path('logout.php') ?>" class="mb-0">
<?= csrf_field() ?>
<button class="nav-link btn btn-link border-0 p-0" type="submit" title="Atsijungti">
<i class="fa-solid fa-right-from-bracket"></i>
</button>
</form>
</li>
</ul>

</div>
</div>
</nav>

<div class="container-fluid mt-4">
