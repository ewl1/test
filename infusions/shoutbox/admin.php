<?php
require_permission('shoutbox.moderate');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (($_POST['action'] ?? '') === 'delete') {
        shoutbox_delete_message((int)($_POST['id'] ?? 0));
        echo '<div class="alert alert-success">Žinutė ištrinta.</div>';
    }
}

$messages = shoutbox_get_messages(200);
?>
<div class="card">
    <div class="card-header">Šaukyklos moderavimas</div>
    <div class="card-body">
        <div class="alert alert-info">Čia galite peržiūrėti ir šalinti šaukyklos žinutes. BBCode ir smailai interpretuojami viešame puslapyje.</div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>ID</th><th>Vartotojas</th><th>Žinutė</th><th>Data</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($messages as $message): ?>
                    <tr>
                        <td><?= (int)$message['id'] ?></td>
                        <td><?= e($message['username'] ?? 'Svečias') ?></td>
                        <td class="min-width-320"><?= shoutbox_escape_and_format($message['message']) ?></td>
                        <td><?= e($message['created_at']) ?></td>
                        <td>
                            <form method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$message['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger" data-confirm-message="Ištrinti žinutę?">Trinti</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$messages): ?>
                    <tr><td colspan="5" class="text-secondary">Kol kas žinučių nėra.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
