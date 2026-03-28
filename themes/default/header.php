<?php
$me = current_user();
$themeCss = defined('IN_ADMIN') ? 'themes/default/admin.css' : 'themes/default/style.css';
$registeredStyles = get_registered_page_styles();
?>
<!doctype html>
<html lang="<?= e(site_locale()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(setting('site_name', __('site.title'))) ?></title>
<meta name="description" content="<?= e(setting('site_description', __('site.title'))) ?>">
<meta name="keywords" content="<?= e(setting('site_keywords', 'cms, php, mysql')) ?>">
<link rel="icon" type="image/x-icon" href="<?= public_path('images/favicons/favicon.ico') ?>">
<link rel="icon" type="image/png" sizes="32x32" href="<?= public_path('images/favicons/favicon-32x32.png') ?>">
<link rel="apple-touch-icon" href="<?= public_path('images/favicons/apple-touch-icon.png') ?>">
<link rel="stylesheet" href="<?= asset_path('themes/default/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= asset_path($themeCss) ?>">
<?php foreach ($registeredStyles as $stylePath): ?>
<link rel="stylesheet" href="<?= asset_path($stylePath) ?>">
<?php endforeach; ?>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
<div class="container">
<a class="navbar-brand" href="<?= public_path('index.php') ?>"><?= e(setting('site_name', __('site.title'))) ?></a>
<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-label="<?= e(__('nav.home')) ?>">
<span class="navbar-toggler-icon"></span>
</button>
<div class="collapse navbar-collapse" id="mainNav">
<ul class="navbar-nav me-auto">
<li class="nav-item"><a class="nav-link" href="<?= public_path('index.php') ?>"><?= e(__('nav.home')) ?></a></li>
<li class="nav-item"><a class="nav-link" href="<?= public_path('shoutbox.php') ?>"><?= e(__('nav.shoutbox')) ?></a></li>
<li class="nav-item"><a class="nav-link" href="<?= public_path('search.php') ?>"><?= e(__('nav.search')) ?></a></li>
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
<ul class="dropdown-menu">
<?php foreach ($children as $child): ?>
<li><a class="dropdown-item" href="<?= escape_url($child['url']) ?>"><?= e($child['title']) ?></a></li>
<?php endforeach; ?>
</ul>
</li>
<?php else: ?>
<li class="nav-item"><a class="nav-link" href="<?= escape_url($link['url']) ?>"><?= e($link['title']) ?></a></li>
<?php endif; endforeach; } catch (Throwable $e) {} ?>
<?php if ($me && has_permission($GLOBALS['pdo'], $me['id'], 'admin.access')): ?>
<li class="nav-item dropdown">
<a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><?= e(__('nav.admin')) ?></a>
<ul class="dropdown-menu">
<li><a class="dropdown-item" href="<?= public_path('administration/index.php') ?>"><?= e(__('nav.admin.dashboard')) ?></a></li>
<?php if (has_permission($GLOBALS['pdo'], $me['id'], 'settings.manage')): ?><li><a class="dropdown-item" href="<?= public_path('administration/settings.php') ?>"><?= e(__('nav.admin.settings')) ?></a></li><?php endif; ?>
<?php if (has_permission($GLOBALS['pdo'], $me['id'], 'themes.manage')): ?><li><a class="dropdown-item" href="<?= public_path('administration/themes.php') ?>"><?= e(__('nav.admin.themes')) ?></a></li><?php endif; ?>
<?php if (has_permission($GLOBALS['pdo'], $me['id'], 'navigation.manage')): ?><li><a class="dropdown-item" href="<?= public_path('administration/navigation.php') ?>"><?= e(__('nav.admin.navigation')) ?></a></li><?php endif; ?>
<?php if (has_permission($GLOBALS['pdo'], $me['id'], 'infusions.manage')): ?><li><a class="dropdown-item" href="<?= public_path('administration/infusions.php') ?>"><?= e(__('nav.admin.infusions')) ?></a></li><?php endif; ?>
<?php if (has_permission($GLOBALS['pdo'], $me['id'], 'panels.manage')): ?><li><a class="dropdown-item" href="<?= public_path('administration/panels.php') ?>"><?= e(__('nav.admin.panels')) ?></a></li><?php endif; ?>
<?php if (has_permission($GLOBALS['pdo'], $me['id'], 'roles.manage')): ?><li><a class="dropdown-item" href="<?= public_path('administration/roles.php') ?>"><?= e(__('nav.admin.roles')) ?></a></li><?php endif; ?>
<?php if (has_permission($GLOBALS['pdo'], $me['id'], 'permissions.manage')): ?><li><a class="dropdown-item" href="<?= public_path('administration/permissions.php') ?>"><?= e(__('nav.admin.permissions')) ?></a></li><?php endif; ?>
<?php if (has_permission($GLOBALS['pdo'], $me['id'], 'users.view') || has_permission($GLOBALS['pdo'], $me['id'], 'users.manage')): ?><li><a class="dropdown-item" href="<?= public_path('administration/users.php') ?>"><?= e(__('nav.admin.members')) ?></a></li><?php endif; ?>
<?php if (has_permission($GLOBALS['pdo'], $me['id'], 'settings.manage') || has_permission($GLOBALS['pdo'], $me['id'], 'logs.view')): ?><li><a class="dropdown-item" href="<?= public_path('administration/diagnostics.php') ?>"><?= e(__('nav.admin.diagnostics')) ?></a></li><?php endif; ?>
<?php foreach (get_infusion_admin_menu_items() as $adminItem): ?>
<?php
$allowed = empty($adminItem['permission_slug']) || has_permission($GLOBALS['pdo'], $me['id'], $adminItem['permission_slug']);
if (!$allowed) {
    continue;
}
?>
<li><a class="dropdown-item" href="<?= public_path('administration/infusion-admin.php?folder=' . urlencode($adminItem['folder'])) ?>"><?= e($adminItem['title']) ?></a></li>
<?php endforeach; ?>
</ul>
</li>
<?php endif; ?>
</ul>

<div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 ms-lg-3">
<form method="get" action="<?= public_path('search.php') ?>" class="site-search-form d-flex align-items-center gap-2">
<input class="form-control form-control-sm site-search-input" type="search" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="<?= e(__('nav.search.placeholder')) ?>" maxlength="100">
<button class="btn btn-sm btn-light site-search-button" type="submit"><?= e(__('nav.search.button')) ?></button>
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
<a class="dropdown-item" href="<?= public_path('profile.php') ?>"><?= e(__('member.profile.edit')) ?></a>
<a class="dropdown-item" href="<?= user_profile_url((int)$me['id']) ?>"><?= e(__('member.profile.public')) ?></a>
<?php if (has_permission($GLOBALS['pdo'], $me['id'], 'admin.access')): ?>
<a class="dropdown-item" href="<?= public_path('administration/index.php') ?>"><?= e(__('nav.admin.dashboard')) ?></a>
<?php endif; ?>
<div class="dropdown-divider"></div>
<form method="post" action="<?= public_path('logout.php') ?>" class="px-3 py-2 member-menu-logout">
<?= csrf_field() ?>
<button class="btn btn-sm btn-outline-secondary w-100" type="submit"><?= e(__('member.logout')) ?></button>
</form>
</div>
</div>
<?php else: ?>
<div class="d-flex gap-2 align-items-center">
<a class="btn btn-sm btn-outline-light" href="<?= public_path('login.php') ?>"><?= e(__('nav.login')) ?></a>
<a class="btn btn-sm btn-light" href="<?= public_path('register.php') ?>"><?= e(__('nav.register')) ?></a>
<a class="btn btn-sm btn-outline-warning" href="<?= public_path('administration/login.php') ?>"><?= e(__('nav.admin_login')) ?></a>
</div>
<?php endif; ?>
</div>
</div></div></nav>
<?php if (setting('show_banners', '0') === '1'): ?><div class="container mb-3"><?= showbanners() ?></div><?php endif; ?>
<div class="container">
