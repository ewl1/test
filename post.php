<?php
require_once __DIR__ . '/include/bootstrap.php';
$id = (int)($_GET['id'] ?? 0);
$post = get_post($pdo, $id);
if (!$post) {
    http_response_code(404);
    exit('Postas nerastas.');
}
include __DIR__ . '/theme/header.php';
?>
<h1><?= e($post['title']) ?></h1>
<div class="text-muted small mb-3">Autorius: <?= e($post['username'] ?? '—') ?> · <?= e(format_dt($post['created_at'])) ?></div>
<div><?= nl2br($post['content']) ?></div>
<?php include __DIR__ . '/theme/footer.php'; ?>
