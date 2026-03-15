<?php
require_once __DIR__ . '/_guard.php';
require_permission($pdo, 'shoutbox.moderate');
$shouts = get_shouts($pdo, 200);
include dirname(__DIR__) . '/theme/header.php';
?>
<h1>Šaukyklos moderavimas</h1>
<table class="table table-striped">
    <tr><th>ID</th><th>Vartotojas</th><th>Žinutė</th><th>Data</th><th></th></tr>
    <?php foreach ($shouts as $shout): ?>
    <tr>
        <td><?= (int)$shout['id'] ?></td>
        <td><?= e($shout['username'] ?? 'Svečias') ?></td>
        <td><?= e(mb_substr($shout['message'], 0, 80)) ?></td>
        <td><?= e(format_dt($shout['created_at'])) ?></td>
        <td>
            <a class="btn btn-sm btn-outline-primary" href="shout-edit.php?id=<?= (int)$shout['id'] ?>">Redaguoti</a>
            <a class="btn btn-sm btn-outline-danger confirm-delete" href="shout-delete.php?id=<?= (int)$shout['id'] ?>">Trinti</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php include dirname(__DIR__) . '/theme/footer.php'; ?>
