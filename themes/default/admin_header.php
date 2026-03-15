<?php
if (!defined('IN_ADMIN')) define('IN_ADMIN', true);
$siteTitle = setting('site_name', 'Mini CMS');
$me = current_user();
?>
<!DOCTYPE html>
<html lang="lt">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($siteTitle) ?> – Administracija</title>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="<?= public_path('themes/default/admin.css') ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

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

<li class="nav-item">
<a class="nav-link" href="<?= public_path('administration/users.php') ?>">
<i class="fa-solid fa-users"></i> Nariai
</a>
</li>

<li class="nav-item">
<a class="nav-link" href="<?= public_path('administration/roles.php') ?>">
<i class="fa-solid fa-user-shield"></i> Rolės
</a>
</li>

<li class="nav-item">
<a class="nav-link" href="<?= public_path('administration/permissions.php') ?>">
<i class="fa-solid fa-key"></i> Leidimai
</a>
</li>

<li class="nav-item">
<a class="nav-link" href="<?= public_path('administration/panels.php') ?>">
<i class="fa-solid fa-table-columns"></i> Panelės
</a>
</li>

<li class="nav-item">
<a class="nav-link" href="<?= public_path('administration/infusions.php') ?>">
<i class="fa-solid fa-puzzle-piece"></i> Infusions
</a>
</li>

</ul>

<ul class="navbar-nav">

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
<a class="nav-link" href="<?= public_path('logout.php') ?>">
<i class="fa-solid fa-right-from-bracket"></i>
</a>
</li>

</ul>

</div>
</div>
</nav>

<div class="container-fluid mt-4">
