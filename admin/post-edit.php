<?php
require_once __DIR__ . '/_guard.php';
require_permission($pdo, 'posts.edit');

$id = (int)($_GET['id'] ?? 0);
$post = $id ? get_post($pdo, $id) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    [$ok, $message] = save_post($pdo, $_POST, $id ?: null);
    flash($ok ? 'success' : 'error', $message);
    if ($ok) {
        redirect('posts.php');
    }
}
include dirname(__DIR__) . '/theme/header.php';
?>
<h1><?= $id ? 'Redaguoti postą' : 'Naujas postas' ?></h1>
<form method="post" class="card card-body">
    <?= csrf_input() ?>
    <div class="mb-3"><label class="form-label">Pavadinimas</label><input class="form-control" name="title" value="<?= e($post['title'] ?? '') ?>"></div>
    <div class="mb-3"><label class="form-label">Turinys</label><textarea class="form-control" name="content" rows="10"><?= e($post['content'] ?? '') ?></textarea></div>
    <div class="mb-3">
        <label class="form-label">Statusas</label>
        <select class="form-select" name="status">
            <option value="draft" <?= (($post['status'] ?? '') === 'draft') ? 'selected' : '' ?>>Juodraštis</option>
            <option value="published" <?= (($post['status'] ?? '') === 'published') ? 'selected' : '' ?>>Publikuotas</option>
        </select>
    </div>
    <button class="btn btn-primary">Išsaugoti</button>
</form>
<?php include dirname(__DIR__) . '/theme/footer.php'; ?>
