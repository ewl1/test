<?php
$panelMessages = shoutbox_get_messages(shoutbox_panel_messages_limit());
$redirectPath = normalize_local_path($_SERVER['REQUEST_URI'] ?? 'index.php', 'index.php');
?>

<div class="shoutbox-panel">
<?php shoutbox_render_editor('panel', 'shoutbox-panel-message', $redirectPath, true); ?>

<?php if ($panelMessages): ?>
    <hr>
    <div class="shoutbox-panel-list">
    <?php foreach ($panelMessages as $message): ?>
        <article class="shoutbox-panel-item mb-3">
            <div class="fw-semibold">
                <?php if (!empty($message['user_id'])): ?>
                    <a class="text-decoration-none" href="<?= user_profile_url((int)$message['user_id']) ?>"><?= e($message['username'] ?? __('member.none')) ?></a>
                <?php else: ?>
                    <?= e($message['username'] ?? __('member.guest')) ?>
                <?php endif; ?>
            </div>
            <div class="small text-secondary mb-1"><?= e(format_dt($message['created_at'])) ?></div>
            <div class="small shoutbox-panel-excerpt"><?= e(shoutbox_plain_excerpt($message['message'], 120)) ?></div>
        </article>
    <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="text-secondary small"><?= e(__('shoutbox.empty')) ?></div>
<?php endif; ?>

<a class="btn btn-sm btn-outline-primary" href="<?= public_path('shoutbox.php') ?>"><?= e(__('shoutbox.open')) ?></a>
</div>
