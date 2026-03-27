<?php
require_once __DIR__ . '/_guard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $title = trim((string)($_POST['title'] ?? ''));
    $url = normalize_url_value($_POST['url'] ?? '#');
    $parent = trim((string)($_POST['parent_id'] ?? ''));
    $errors = [];

    if ($title === '' || mb_strlen($title) > 100) {
        $errors[] = 'Pavadinimas privalomas ir negali viršyti 100 simbolių.';
    }
    if ($msg = validate_url_value($url === '' ? '#' : $url, false, 'URL')) {
        $errors[] = $msg;
    }

    if ($errors) {
        flash('error', implode(' ', $errors));
        redirect('navigation.php');
    }

    $stmt = $GLOBALS['pdo']->prepare("INSERT INTO navigation_links (title, url, parent_id, sort_order, is_active) VALUES (:t,:u,:p,:s,:a)");
    $stmt->execute([
        ':t' => $title,
        ':u' => $url === '' ? '#' : $url,
        ':p' => $parent !== '' ? (int)$parent : null,
        ':s' => (int)($_POST['sort_order'] ?? 0),
        ':a' => (int)($_POST['is_active'] ?? 1),
    ]);
    flash('success', 'Nuoroda pridėta.');
    redirect('navigation.php');
}

$links = $GLOBALS['pdo']->query("SELECT * FROM navigation_links ORDER BY parent_id IS NOT NULL, sort_order ASC, id ASC")->fetchAll();
include THEMES . 'default/admin_header.php';
?>
<h1 class="h3 mb-3">Navigacija</h1>
<?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($msg = flash('error')): ?><div class="alert alert-danger"><?= e($msg) ?></div><?php endif; ?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">Nauja nuoroda</div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <div class="mb-3"><label class="form-label">Pavadinimas</label><input class="form-control" name="title"></div>
                    <div class="mb-3"><label class="form-label">URL</label><input class="form-control" name="url" value="#"></div>
                    <div class="mb-3">
                        <label class="form-label">Tėvinė nuoroda</label>
                        <select class="form-select" name="parent_id">
                            <option value="">-- nėra --</option>
                            <?php foreach ($links as $link): ?>
                                <option value="<?= (int)$link['id'] ?>"><?= e($link['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Rikiavimas</label><input class="form-control" type="number" name="sort_order" value="0"></div>
                    <div class="mb-3">
                        <label class="form-label">Būsena</label>
                        <select class="form-select" name="is_active">
                            <option value="1">Aktyvi</option>
                            <option value="0">Išjungta</option>
                        </select>
                    </div>
                    <button class="btn btn-primary">Pridėti</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">Nuorodos</div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>ID</th><th>Pavadinimas</th><th>URL</th><th>Parent</th><th>Sort</th></tr></thead>
                    <tbody>
                    <?php foreach ($links as $link): ?>
                        <tr>
                            <td><?= (int)$link['id'] ?></td>
                            <td><?= e($link['title']) ?></td>
                            <td><code><?= e($link['url']) ?></code></td>
                            <td><?= $link['parent_id'] ? (int)$link['parent_id'] : '-' ?></td>
                            <td><?= (int)$link['sort_order'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include THEMES . 'default/admin_footer.php'; ?>
