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
                $message = __('profile.updated_with_avatar');
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
                        <div class="small text-secondary"><?= e($user['role_name'] ?? __('member.none')) ?></div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <a class="btn btn-outline-secondary" href="<?= user_profile_url((int)$user['id']) ?>"><?= e(__('profile.edit.self')) ?></a>
                    <?php if (has_permission($GLOBALS['pdo'], (int)$user['id'], 'admin.access')): ?>
                        <a class="btn btn-outline-primary" href="<?= public_path('administration/index.php') ?>"><?= e(__('nav.admin.dashboard')) ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
        <?php if ($msg = flash('error')): ?><div class="alert alert-danger"><?= e($msg) ?></div><?php endif; ?>

        <div class="card mb-4">
            <div class="card-header"><?= e(__('profile.edit.title')) ?></div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data" class="row g-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="profile_action" value="profile">
                    <div class="col-md-6">
                        <label class="form-label"><?= e(__('profile.edit.username')) ?></label>
                        <input class="form-control" name="username" value="<?= e($user['username']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?= e(__('profile.edit.email')) ?></label>
                        <input class="form-control" type="email" name="email" value="<?= e($user['email']) ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label"><?= e(__('profile.edit.signature')) ?></label>
                        <textarea class="form-control" name="signature" rows="4" maxlength="500" placeholder="<?= e(__('profile.edit.signature.placeholder')) ?>"><?= e($user['signature'] ?? '') ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label"><?= e(__('profile.edit.avatar')) ?></label>
                        <input class="form-control" type="file" name="avatar" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp">
                        <div class="form-text"><?= e(__('profile.edit.avatar.help')) ?></div>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary"><?= e(__('profile.edit.save')) ?></button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><?= e(__('profile.password')) ?></div>
            <div class="card-body">
                <form method="post" class="row g-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="profile_action" value="password">
                    <div class="col-md-6">
                        <label class="form-label"><?= e(__('profile.password.current')) ?></label>
                        <input class="form-control js-toggle-password" type="password" name="current_password" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?= e(__('profile.password.new')) ?></label>
                        <input class="form-control js-toggle-password" type="password" name="new_password" required>
                        <div class="form-check mt-2">
                            <input class="form-check-input" id="profile-show-password" type="checkbox" data-password-toggle data-password-target=".js-toggle-password">
                            <label class="form-check-label" for="profile-show-password"><?= e(__('auth.password.show')) ?></label>
                        </div>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary"><?= e(__('profile.password.save')) ?></button>
                    </div>
                </form>
            </div>
        </div>

        <?php if (has_permission($GLOBALS['pdo'], (int)$user['id'], 'admin.access')): ?>
            <div class="card">
                <div class="card-header"><?= e(__('profile.admin_password')) ?></div>
                <div class="card-body">
                    <form method="post" class="row g-3">
                        <?= csrf_field() ?>
                        <input type="hidden" name="profile_action" value="admin_password">
                        <div class="col-md-4">
                            <label class="form-label"><?= e(__('profile.admin_password.account')) ?></label>
                            <input class="form-control js-toggle-password" type="password" name="account_password" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><?= e(__('profile.admin_password.new')) ?></label>
                            <input class="form-control js-toggle-password" type="password" name="new_admin_password" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><?= e(__('profile.admin_password.repeat')) ?></label>
                            <input class="form-control js-toggle-password" type="password" name="confirm_admin_password" required>
                        </div>
                        <div class="col-12">
                            <div class="form-text"><?= e(__('profile.admin_password.help')) ?></div>
                            <div class="form-check mt-2 mb-3">
                                <input class="form-check-input" id="profile-show-admin-password" type="checkbox" data-password-toggle data-password-target=".js-toggle-password">
                                <label class="form-check-label" for="profile-show-admin-password"><?= e(__('auth.password.show')) ?></label>
                            </div>
                            <button class="btn btn-primary"><?= e(__('profile.admin_password.save')) ?></button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/themes/default/footer.php'; ?>
