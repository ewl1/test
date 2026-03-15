<?php
require_once __DIR__ . '/_guard.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    save_setting('current_theme', $_POST['current_theme'] ?? 'default');
    save_setting('admin_theme', $_POST['admin_theme'] ?? 'default');
    flash('success', 'Temos išsaugotos.');
    redirect('themes.php');
}
$themes = available_themes();
include THEMES . 'default/admin_header.php';
?>
<h1 class="h3 mb-3">Temos</h1>
<?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
<div class="alert alert-info">Taip — čia atskiras pasirinkimas svetainei ir atskiras administracijos panelei.</div>
<form method="post" class="row g-3"><?= csrf_field() ?>
<div class="col-md-6"><label class="form-label">Svetainės tema</label><select class="form-select" name="current_theme"><?php foreach ($themes as $theme): ?><option value="<?= e($theme) ?>" <?= setting('current_theme', CURRENT_THEME) === $theme ? 'selected' : '' ?>><?= e($theme) ?></option><?php endforeach; ?></select></div>
<div class="col-md-6"><label class="form-label">Admin tema</label><select class="form-select" name="admin_theme"><?php foreach ($themes as $theme): ?><option value="<?= e($theme) ?>" <?= setting('admin_theme', ADMIN_THEME) === $theme ? 'selected' : '' ?>><?= e($theme) ?></option><?php endforeach; ?></select></div>
<div class="col-12"><button class="btn btn-primary">Išsaugoti</button></div>
</form>
<?php include THEMES . 'default/admin_footer.php'; ?>
