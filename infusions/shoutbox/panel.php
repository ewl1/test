<?php
$panelMessages = shoutbox_get_messages(5);
$redirectPath = normalize_local_path($_SERVER['REQUEST_URI'] ?? 'index.php', 'index.php');
?>

<?php shoutbox_render_editor('panel', 'shoutbox-panel-message', $redirectPath, true); ?>

<?php if ($panelMessages): ?>
    <hr>
    <?php foreach ($panelMessages as $message): ?>
        <div class="mb-3">
            <div class="fw-semibold"><?= e($message['username'] ?? 'Svečias') ?></div>
            <div class="small text-secondary mb-1"><?= e(format_dt($message['created_at'])) ?></div>
            <div class="small"><?= shoutbox_escape_and_format(mb_substr($message['message'], 0, 120)) ?></div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="text-secondary small">Kol kas žinučių nėra.</div>
<?php endif; ?>

<a class="btn btn-sm btn-outline-primary" href="<?= public_path('shoutbox.php') ?>">Atidaryti šaukyklą</a>
