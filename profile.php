<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login_page();

$viewer = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = (string)($_POST['profile_action'] ?? 'profile');
    if ($action === 'profile') {
        [$ok, $message] = update_user_profile($pdo, (int)$viewer['id'], $_POST);
        if ($ok) {
            [$avatarOk, $avatarMessage] = update_user_avatar($pdo, (int)$viewer['id'], $_FILES['avatar'] ?? []);
            if (!$avatarOk) {
                $ok = false;
                $message = $avatarMessage;
            } elseif (!empty($avatarMessage)) {
                $message = 'Profilis ir avataras atnaujinti.';
            }
        }

        sync_session_user((int)$viewer['id']);
        flash($ok ? 'success' : 'error', $message);
        redirect(public_path('profile.php'));
    }

    if ($action === 'password') {
        [$ok, $message] = change_password(
            $pdo,
            (int)$viewer['id'],
            $_POST['current_password'] ?? '',
            $_POST['new_password'] ?? ''
        );
        sync_session_user((int)$viewer['id']);
        flash($ok ? 'success' : 'error', $message);
        redirect(public_path('profile.php'));
    }

    if ($action === 'admin_password') {
        [$ok, $message] = update_admin_password(
            $pdo,
            (int)$viewer['id'],
            $_POST['account_password'] ?? '',
            $_POST['new_admin_password'] ?? '',
            $_POST['confirm_admin_password'] ?? ''
        );
        sync_session_user((int)$viewer['id']);
        flash($ok ? 'success' : 'error', $message);
        redirect(public_path('profile.php'));
    }
}

$user = current_user();
include __DIR__ . '/themes/default/header.php';
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <img src="<?= escape_url(user_avatar_url($user)) ?>" alt="" class="user-profile-avatar">
                    <div>
                        <h1 class="h4 mb-1"><?= e($user['username']) ?></h1>
                        <div class="text-secondary"><?= e($user['email']) ?></div>
                        <div class="small text-secondary"><?= e($user['role_name'] ?? 'Narys') ?></div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <a class="btn btn-outline-secondary" href="<?= user_profile_url((int)$user['id']) ?>">Viešas profilis</a>
                    <?php if (has_permission($GLOBALS['pdo'], (int)$user['id'], 'admin.access')): ?>
                        <a class="btn btn-outline-primary" href="<?= public_path('administration/index.php') ?>">Admin Dashboard</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
        <?php if ($msg = flash('error')): ?><div class="alert alert-danger"><?= e($msg) ?></div><?php endif; ?>

        <div class="card mb-4">
            <div class="card-header">Profilio redagavimas</div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data" class="row g-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="profile_action" value="profile">
                    <div class="col-md-6">
                        <label class="form-label">Vartotojo vardas</label>
                        <input class="form-control" name="username" value="<?= e($user['username']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">El. paštas</label>
                        <input class="form-control" type="email" name="email" value="<?= e($user['email']) ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Parašas</label>
                        <textarea class="form-control" name="signature" rows="4" maxlength="500" placeholder="Trumpas parašas prie profilio"><?= e($user['signature'] ?? '') ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Avataras</label>
                        <input class="form-control" type="file" name="avatar" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp">
                        <div class="form-text">Leidžiami JPG, PNG, GIF arba WEBP iki 2 MB.</div>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary">Išsaugoti profilį</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Slaptažodžio keitimas</div>
            <div class="card-body">
                <form method="post" class="row g-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="profile_action" value="password">
                    <div class="col-md-6">
                        <label class="form-label">Dabartinis slaptažodis</label>
                        <input class="form-control" type="password" name="current_password" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Naujas slaptažodis</label>
                        <input class="form-control" type="password" name="new_password" required>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary">Pakeisti slaptažodį</button>
                    </div>
                </form>
            </div>
        </div>

        <?php if (has_permission($GLOBALS['pdo'], (int)$user['id'], 'admin.access')): ?>
            <div class="card">
                <div class="card-header">Admin slaptažodis</div>
                <div class="card-body">
                    <form method="post" class="row g-3">
                        <?= csrf_field() ?>
                        <input type="hidden" name="profile_action" value="admin_password">
                        <div class="col-md-4">
                            <label class="form-label">Paskyros slaptažodis</label>
                            <input class="form-control" type="password" name="account_password" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Naujas admin slaptažodis</label>
                            <input class="form-control" type="password" name="new_admin_password" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Pakartoti admin slaptažodį</label>
                            <input class="form-control" type="password" name="confirm_admin_password" required>
                        </div>
                        <div class="col-12">
                            <div class="form-text">Jei admin slaptažodis nustatytas, prisijungimui į administraciją bus naudojamas būtent jis.</div>
                            <button class="btn btn-primary">Išsaugoti admin slaptažodį</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/themes/default/footer.php'; ?>
