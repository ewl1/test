<?php $me = current_user(); ?>
<!doctype html>
<html lang="lt">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(setting('site_name', APP_NAME)) ?></title>
<meta name="description" content="<?= e(setting('site_description', 'Mini CMS Pro')) ?>">
<meta name="keywords" content="<?= e(setting('site_keywords', 'cms, php, mysql')) ?>">
<link rel="icon" type="image/x-icon" href="<?= public_path('images/favicons/favicon.ico') ?>">
<link rel="icon" type="image/png" sizes="32x32" href="<?= public_path('images/favicons/favicon-32x32.png') ?>">
<link rel="apple-touch-icon" href="<?= public_path('images/favicons/apple-touch-icon.png') ?>">
<link rel="stylesheet" href="<?= asset_path('themes/default/bootstrap.min.css') ?>">
<?php $themeCss = defined('IN_ADMIN') ? 'themes/default/admin.css' : 'themes/default/style.css'; ?>
<link rel="stylesheet" href="<?= asset_path($themeCss) ?>">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
<div class="container">
<a class="navbar-brand" href="<?= public_path('index.php') ?>"><?= e(setting('site_name', APP_NAME)) ?></a>
<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"><span class="navbar-toggler-icon"></span></button>
<div class="collapse navbar-collapse" id="mainNav">
<ul class="navbar-nav me-auto">
<li class="nav-item"><a class="nav-link" href="<?= public_path('index.php') ?>">Pradžia</a></li>
<li class="nav-item"><a class="nav-link" href="<?= public_path('shoutbox.php') ?>">Šaukykla</a></li>
<li class="nav-item"><a class="nav-link" href="<?= public_path('search.php') ?>">Paieška</a></li>
<?php
try {
    $stmt = $GLOBALS['pdo']->query("SELECT * FROM navigation_links WHERE is_active = 1 AND parent_id IS NULL ORDER BY sort_order ASC, id ASC");
    foreach ($stmt->fetchAll() as $link):
        $childrenStmt = $GLOBALS['pdo']->prepare("SELECT * FROM navigation_links WHERE parent_id = :pid AND is_active = 1 ORDER BY sort_order ASC, id ASC");
        $childrenStmt->execute([':pid' => $link['id']]);
        $children = $childrenStmt->fetchAll();
?>
<?php if ($children && setting('show_sublinks', '1') === '1'): ?>
<li class="nav-item dropdown">
<a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><?= e($link['title']) ?></a>
<ul class="dropdown-menu"><?php foreach ($children as $child): ?><li><a class="dropdown-item" href="<?= escape_url($child['url']) ?>"><?= e($child['title']) ?></a></li><?php endforeach; ?></ul>
</li>
<?php else: ?>
<li class="nav-item"><a class="nav-link" href="<?= escape_url($link['url']) ?>"><?= e($link['title']) ?></a></li>
<?php endif; endforeach; } catch (Throwable $e) {} ?>
<?php if ($me && has_permission($GLOBALS['pdo'], $me['id'], 'admin.access')): ?>
<li class="nav-item dropdown">
<a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Administracija</a>
<ul class="dropdown-menu">
<li><a class="dropdown-item" href="<?= public_path('administration/index.php') ?>">Dashboard</a></li>
<?php if (has_permission($GLOBALS['pdo'], $me['id'], 'settings.manage')): ?><li><a class="dropdown-item" href="<?= public_path('administration/settings.php') ?>">Svetainės nustatymai</a></li><?php endif; ?>
<?php if (has_permission($GLOBALS['pdo'], $me['id'], 'themes.manage')): ?><li><a class="dropdown-item" href="<?= public_path('administration/themes.php') ?>">Temos</a></li><?php endif; ?>
<?php if (has_permission($GLOBALS['pdo'], $me['id'], 'navigation.manage')): ?><li><a class="dropdown-item" href="<?= public_path('administration/navigation.php') ?>">Navigacija</a></li><?php endif; ?>
<?php if (has_permission($GLOBALS['pdo'], $me['id'], 'infusions.manage')): ?><li><a class="dropdown-item" href="<?= public_path('administration/infusions.php') ?>">Infusions</a></li><?php endif; ?>
<?php if (has_permission($GLOBALS['pdo'], $me['id'], 'panels.manage')): ?><li><a class="dropdown-item" href="<?= public_path('administration/panels.php') ?>">Panelės</a></li><?php endif; ?>
<?php if (has_permission($GLOBALS['pdo'], $me['id'], 'roles.manage')): ?><li><a class="dropdown-item" href="<?= public_path('administration/roles.php') ?>">Rolės</a></li><?php endif; ?>
<?php if (has_permission($GLOBALS['pdo'], $me['id'], 'permissions.manage')): ?><li><a class="dropdown-item" href="<?= public_path('administration/permissions.php') ?>">Leidimai</a></li><?php endif; ?>
<?php if (has_permission($GLOBALS['pdo'], $me['id'], 'users.view') || has_permission($GLOBALS['pdo'], $me['id'], 'users.manage')): ?><li><a class="dropdown-item" href="<?= public_path('administration/users.php') ?>">Nariai</a></li><?php endif; ?>
<?php if (has_permission($GLOBALS['pdo'], $me['id'], 'settings.manage') || has_permission($GLOBALS['pdo'], $me['id'], 'logs.view')): ?><li><a class="dropdown-item" href="<?= public_path('administration/diagnostics.php') ?>">Diagnostika</a></li><?php endif; ?>
<?php foreach (get_infusion_admin_menu_items() as $adminItem): ?>
<?php
$allowed = empty($adminItem['permission_slug']) || has_permission($GLOBALS['pdo'], $me['id'], $adminItem['permission_slug']);
if (!$allowed) {
    continue;
}
?>
<li>
<a class="dropdown-item" href="<?= public_path('administration/infusion-admin.php?folder=' . urlencode($adminItem['folder'])) ?>">
<?= e($adminItem['title']) ?>
</a>
</li>
<?php endforeach; ?>
</ul>
</li>
<?php endif; ?>
</ul>

<div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 ms-lg-3">
<form method="get" action="<?= public_path('search.php') ?>" class="site-search-form d-flex align-items-center gap-2">
<input class="form-control form-control-sm site-search-input" type="search" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Ieškoti..." maxlength="100">
<button class="btn btn-sm btn-light site-search-button" type="submit">Rasti</button>
</form>

<?php if ($me): ?>
<div class="dropdown">
<button class="btn btn-sm btn-outline-light dropdown-toggle member-menu-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
<img src="<?= escape_url(user_avatar_url($me)) ?>" alt="" class="member-menu-avatar">
<span class="member-menu-name"><?= e($me['username']) ?></span>
</button>
<div class="dropdown-menu dropdown-menu-end member-menu-dropdown">
<div class="member-menu-header px-3 py-2 border-bottom">
<div class="fw-semibold"><a class="text-decoration-none" href="<?= user_profile_url((int)$me['id']) ?>"><?= e($me['username']) ?></a></div>
<div class="small text-secondary"><?= e($me['email']) ?></div>
</div>
<a class="dropdown-item" href="<?= public_path('profile.php') ?>">Profilio redagavimas</a>
<a class="dropdown-item" href="<?= user_profile_url((int)$me['id']) ?>">Viešas profilis</a>
<?php if (has_permission($GLOBALS['pdo'], $me['id'], 'admin.access')): ?>
<a class="dropdown-item" href="<?= public_path('administration/index.php') ?>">Admin Dashboard</a>
<?php endif; ?>
<div class="dropdown-divider"></div>
<form method="post" action="<?= public_path('logout.php') ?>" class="px-3 py-2 member-menu-logout">
<?= csrf_field() ?>
<button class="btn btn-sm btn-outline-secondary w-100" type="submit">Atsijungti</button>
</form>
</div>
</div>
<?php else: ?>
<div class="d-flex gap-2 align-items-center">
<a class="btn btn-sm btn-outline-light" href="<?= public_path('login.php') ?>">Prisijungti</a>
<a class="btn btn-sm btn-light" href="<?= public_path('register.php') ?>">Registracija</a>
<a class="btn btn-sm btn-outline-warning" href="<?= public_path('administration/login.php') ?>">Admin</a>
</div>
<?php endif; ?>
</div>
</div></div></nav>
<?php if (setting('show_banners', '0') === '1'): ?><div class="container mb-3"><?= showbanners() ?></div><?php endif; ?>
<div class="container">
