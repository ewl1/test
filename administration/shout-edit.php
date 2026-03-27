<?php define('IN_ADMIN', true); ?>
<?php
require_once __DIR__ . '/_guard.php';
require_permission($pdo, 'shoutbox.moderate');

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM shouts WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$shout = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    [$ok, $message] = update_shout($pdo, $id, $_POST['message'] ?? '');
    flash($ok ? 'success' : 'error', $message);
    redirect('shouts.php');
}
include dirname(__DIR__) . '/themes/default/header.php';
?>
<h1>Redaguoti šaukyklos žinutę</h1>
<form method="post" class="card card-body">
    <?= csrf_input() ?>
    <textarea class="form-control mb-3" rows="6" name="message"><?= e($shout['message'] ?? '') ?></textarea>
    <button class="btn btn-primary">Išsaugoti</button>
</form>
<?php include dirname(__DIR__) . '/themes/default/footer.php'; ?>
