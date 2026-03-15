<?php
require_once __DIR__ . '/_guard.php';
require_permission($pdo, 'settings.manage');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $value = max(1, (int)($_POST['posts_per_page'] ?? 10));
    $stmt = $pdo->prepare("
        INSERT INTO settings (setting_key, setting_value)
        VALUES ('posts_per_page', :value)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
    ");
    $stmt->execute([':value' => $value]);
    audit_log($pdo, $_SESSION['user']['id'], 'settings_update', 'settings', null, ['posts_per_page' => $value]);
    flash('success', 'Nustatymai išsaugoti.');
    redirect('settings.php');
}

$value = post_limit_setting($pdo);
include dirname(__DIR__) . '/theme/header.php';
?>
<h1>Nustatymai</h1>
<form method="post" class="card card-body">
    <?= csrf_input() ?>
    <div class="mb-3">
        <label class="form-label">Kiek postų rodyti</label>
        <input class="form-control" type="number" min="1" max="100" name="posts_per_page" value="<?= (int)$value ?>">
    </div>
    <button class="btn btn-primary">Išsaugoti</button>
</form>
<?php include dirname(__DIR__) . '/theme/footer.php'; ?>
