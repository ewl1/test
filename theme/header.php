<?php $me = current_user(); ?>
<!doctype html>
<html lang="lt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="index.php">Mini CMS</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Pradžia</a></li>
                <li class="nav-item"><a class="nav-link" href="shoutbox.php">Šaukykla</a></li>
                <?php if ($me): ?>
                    <li class="nav-item"><a class="nav-link" href="profile.php">Profilis</a></li>
                <?php endif; ?>
                <?php if ($me && has_permission($pdo, 'admin.access')): ?>
                    <li class="nav-item"><a class="nav-link" href="admin/index.php">Administracija</a></li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <?php if ($me): ?>
                    <li class="nav-item text-light d-flex align-items-center me-3">
                        <small><?= e($me['username']) ?> (<?= e($me['role_name'] ?? 'narys') ?>)</small>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Atsijungti</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Prisijungti</a></li>
                    <li class="nav-item"><a class="nav-link" href="register.php">Registracija</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<div class="container">
<?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($msg = flash('error')): ?><div class="alert alert-danger"><?= e($msg) ?></div><?php endif; ?>
