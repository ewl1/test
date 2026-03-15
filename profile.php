<?php
require_once __DIR__ . '/include/bootstrap.php';
require_login_page();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    [$ok, $message] = update_user_profile($pdo, $_SESSION['user']['id'], $_POST);

    if (!empty($_FILES['avatar']['name'])) {
        [$uploaded, $result] = upload_avatar($_FILES['avatar']);
        if ($uploaded) {
            $stmt = $pdo->prepare("UPDATE users SET avatar = :avatar WHERE id = :id");
            $stmt->execute([':avatar' => $result, ':id' => $_SESSION['user']['id']]);
        } else {
            $ok = false;
            $message = $result;
        }
    }

    sync_session_user($pdo, $_SESSION['user']['id']);
    flash($ok ? 'success' : 'error', $message);
    redirect('profile.php');
}

$user = current_user();
include __DIR__ . '/theme/header.php';
?>
<h1>Profilis</h1>
<div class="row">
    <div class="col-md-4">
        <div class="card card-body">
            <img src="<?= e(user_avatar_url($user)) ?>" alt="" class="img-fluid rounded mb-3">
            <div><strong><?= e($user['username']) ?></strong></div>
            <div class="text-muted"><?= e($user['email']) ?></div>
            <div class="text-muted small"><?= e($user['role_name']) ?></div>
            <a class="btn btn-outline-secondary mt-3" href="change-password.php">Keisti slaptažodį</a>
        </div>
    </div>
    <div class="col-md-8">
        <form method="post" enctype="multipart/form-data" class="card card-body">
            <?= csrf_input() ?>
            <div class="mb-3"><label class="form-label">Vartotojo vardas</label><input class="form-control" name="username" value="<?= e($user['username']) ?>"></div>
            <div class="mb-3"><label class="form-label">El. paštas</label><input class="form-control" name="email" value="<?= e($user['email']) ?>"></div>
            <div class="mb-3"><label class="form-label">Avataras</label><input class="form-control" type="file" name="avatar"></div>
            <button class="btn btn-primary">Išsaugoti</button>
        </form>
    </div>
</div>
<?php include __DIR__ . '/theme/footer.php'; ?>
