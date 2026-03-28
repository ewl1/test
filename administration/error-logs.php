<?php
require_once __DIR__ . '/_guard.php';
require_permission('logs.view');

$logPath = BASEDIR . 'logs/php-error.log';
if (!is_dir(dirname($logPath))) {
    @mkdir(dirname($logPath), 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (($_POST['action'] ?? '') === 'clear') {
        file_put_contents($logPath, '');
        flash('success', 'Klaidų žurnalas išvalytas.');
        redirect('error-logs.php');
    }
}

$search = trim((string)($_GET['q'] ?? ''));
$lines = file_exists($logPath) ? file($logPath, FILE_IGNORE_NEW_LINES) : [];
$lines = array_reverse(array_slice($lines, -200));
if ($search !== '') {
    $lines = array_values(array_filter($lines, static fn($line) => stripos($line, $search) !== false));
}

include THEMES . 'default/admin_header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= e(__('admin.error_log')) ?></h1>
    <div class="d-flex gap-2">
        <form method="get" class="d-flex gap-2">
            <input class="form-control" name="q" placeholder="Ieškoti žurnale" value="<?= e($search) ?>">
            <button class="btn btn-outline-secondary">Ieškoti</button>
        </form>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="clear">
            <button class="btn btn-outline-danger" data-confirm-message="Išvalyti klaidų žurnalą?">Išvalyti</button>
        </form>
    </div>
</div>

<?php if ($msg = flash('success')): ?>
    <div class="alert alert-success"><?= e($msg) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="small text-secondary mb-3">Failas: <?= e($logPath) ?></div>
        <?php if (!$lines): ?>
            <div class="text-secondary">Klaidų žurnalas tuščias.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0 small admin-log-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Įrašas</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($lines as $index => $line): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><pre class="mb-0 small pre-wrap-log admin-log-entry"><?= e($line) ?></pre></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include THEMES . 'default/admin_footer.php'; ?>
