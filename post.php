<?php
require_once __DIR__ . '/includes/bootstrap.php';

$id = (int)($_GET['id'] ?? 0);
$post = get_post($pdo, $id);
if (!$post) {
    abort_http(404, 'Įrašas nerastas.');
}

include __DIR__ . '/themes/default/header.php';
?>
<h1><?= e($post['title']) ?></h1>
<div class="text-muted small mb-3">Autorius: <?= e($post['username'] ?? '—') ?> · <?= e(format_dt($post['created_at'])) ?></div>
<div><?= nl2br(e($post['content'])) ?></div>
<?php include __DIR__ . '/themes/default/footer.php'; ?>
