<?php
require_once __DIR__ . '/_guard.php';
require_permission('settings.manage');

ensure_site_smiley_schema();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verify_csrf();

    $action = trim((string)($_POST['action'] ?? 'save'));
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'delete') {
        [$ok, $message] = delete_site_smiley($id);
        flash($ok ? 'success' : 'error', $message);
        redirect('smileys.php');
    }

    if ($action === 'toggle') {
        $enable = !empty($_POST['enable']);
        [$ok, $message] = toggle_site_smiley_status($id, $enable);
        flash($ok ? 'success' : 'error', $message);
        redirect('smileys.php');
    }

    [$ok, $message, $savedId] = save_site_smiley($_POST, $_FILES['image'] ?? [], $id);
    flash($ok ? 'success' : 'error', $message);
    if ($ok) {
        redirect('smileys.php?edit=' . (int)$savedId);
    }

    $targetId = $id > 0 ? '?edit=' . $id : '';
    redirect('smileys.php' . $targetId);
}

$editId = max(0, (int)($_GET['edit'] ?? 0));
$editing = $editId > 0 ? site_smiley_find($editId) : null;
$smileys = site_smileys(false);

$form = [
    'id' => (int)($editing['id'] ?? 0),
    'code' => (string)($editing['code'] ?? ''),
    'title' => (string)($editing['title'] ?? ''),
    'type' => (string)($editing['type'] ?? 'emoji'),
    'emoji_value' => $editing && ($editing['type'] ?? '') === 'emoji' ? (string)($editing['value'] ?? '') : '',
    'image_value' => $editing && ($editing['type'] ?? '') === 'image' ? (string)($editing['value'] ?? '') : '',
    'sort_order' => (int)($editing['sort_order'] ?? 0),
    'is_active' => $editing ? !empty($editing['is_active']) : true,
];

include THEMES . 'default/admin_header.php';
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Šypsenėlių valdymas</h1>
        <div class="admin-page-subtitle">Bendros šypsenėlės forumui, šaukyklai ir profilio komentarams.</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary admin-action-button" href="index.php">Admin Dashboard</a>
        <a class="btn btn-outline-primary admin-action-button" href="infusion-admin.php?folder=shoutbox">Šaukyklos administravimas</a>
    </div>
</div>

<?php if ($message = flash('success')): ?>
    <div class="alert alert-success"><?= e($message) ?></div>
<?php endif; ?>
<?php if ($message = flash('error')): ?>
    <div class="alert alert-danger"><?= e($message) ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-xl-5">
        <div class="card admin-smiley-form-card">
            <div class="card-header"><?= $form['id'] > 0 ? 'Redaguoti šypsenėlę' : 'Nauja šypsenėlė' ?></div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data" class="row g-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= (int)$form['id'] ?>">

                    <div class="col-md-6">
                        <label class="form-label">Kodas</label>
                        <input class="form-control" type="text" name="code" maxlength="32" value="<?= e($form['code']) ?>" placeholder=":)">
                        <div class="form-text">Pvz. <code>:)</code>, <code>:D</code>, <code>&lt;3</code>.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Pavadinimas</label>
                        <input class="form-control" type="text" name="title" maxlength="120" value="<?= e($form['title']) ?>" placeholder="Šypsena">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Tipas</label>
                        <select class="form-select" name="type">
                            <option value="emoji" <?= $form['type'] === 'emoji' ? 'selected' : '' ?>>Emoji / simbolis</option>
                            <option value="image" <?= $form['type'] === 'image' ? 'selected' : '' ?>>Paveikslėlis</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Rikiavimas</label>
                        <input class="form-control" type="number" name="sort_order" value="<?= (int)$form['sort_order'] ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Emoji reikšmė</label>
                        <input class="form-control" type="text" name="emoji_value" maxlength="32" value="<?= e($form['emoji_value']) ?>" placeholder="🙂">
                        <div class="form-text">Naudojama tik tada, kai tipas yra <strong>Emoji / simbolis</strong>.</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Paveikslėlio failas</label>
                        <input class="form-control" type="file" name="image" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp">
                        <div class="form-text">Naudojama tik tada, kai tipas yra <strong>Paveikslėlis</strong>. Leidžiami JPG, PNG, GIF arba WEBP iki 2 MB.</div>
                        <?php if ($form['image_value'] !== ''): ?>
                            <div class="admin-smiley-current mt-3">
                                <div class="small text-secondary mb-2">Dabartinis paveikslėlis</div>
                                <div class="d-flex align-items-center gap-3 flex-wrap">
                                    <?= site_smiley_preview_html([
                                        'code' => $form['code'],
                                        'title' => $form['title'],
                                        'type' => 'image',
                                        'value' => $form['image_value'],
                                    ], 'admin-smiley-preview') ?>
                                    <code class="admin-path-code"><?= e($form['image_value']) ?></code>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="smiley-active" name="is_active" value="1" <?= $form['is_active'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="smiley-active">Aktyvi šypsenėlė</label>
                        </div>
                    </div>

                    <div class="col-12 d-flex flex-wrap gap-2">
                        <button class="btn btn-primary admin-action-button" type="submit"><?= $form['id'] > 0 ? 'Išsaugoti pakeitimus' : 'Sukurti šypsenėlę' ?></button>
                        <?php if ($form['id'] > 0): ?>
                            <a class="btn btn-outline-secondary admin-action-button" href="smileys.php">Nauja forma</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-7">
        <div class="card">
            <div class="card-header">Esamos šypsenėlės</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle mb-0 admin-table-strong admin-smiley-table">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Peržiūra</th>
                            <th>Kodas</th>
                            <th>Pavadinimas</th>
                            <th>Tipas</th>
                            <th>Reikšmė</th>
                            <th>Rikiavimas</th>
                            <th>Būsena</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($smileys as $smiley): ?>
                            <tr>
                                <td class="admin-strong-cell"><?= (int)$smiley['id'] ?></td>
                                <td class="admin-smiley-preview-cell"><?= site_smiley_preview_html($smiley, 'admin-smiley-preview') ?></td>
                                <td><code class="admin-mono-pill"><?= e($smiley['code']) ?></code></td>
                                <td class="admin-strong-cell"><?= e($smiley['title']) ?></td>
                                <td>
                                    <span class="badge text-bg-light"><?= e(($smiley['type'] ?? 'emoji') === 'image' ? 'Paveikslėlis' : 'Emoji') ?></span>
                                </td>
                                <td class="admin-smiley-value">
                                    <?php if (($smiley['type'] ?? 'emoji') === 'image'): ?>
                                        <code class="admin-path-code"><?= e($smiley['value']) ?></code>
                                    <?php else: ?>
                                        <span class="admin-strong-cell"><?= e($smiley['value']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="admin-table-note"><?= (int)$smiley['sort_order'] ?></td>
                                <td>
                                    <span class="badge <?= !empty($smiley['is_active']) ? 'text-bg-success' : 'text-bg-light' ?>">
                                        <?= !empty($smiley['is_active']) ? 'Aktyvi' : 'Išjungta' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                                        <a class="btn btn-sm btn-outline-primary admin-action-button" href="smileys.php?edit=<?= (int)$smiley['id'] ?>">Redaguoti</a>
                                        <form method="post" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="id" value="<?= (int)$smiley['id'] ?>">
                                            <input type="hidden" name="enable" value="<?= !empty($smiley['is_active']) ? '0' : '1' ?>">
                                            <button class="btn btn-sm <?= !empty($smiley['is_active']) ? 'btn-outline-warning' : 'btn-outline-success' ?> admin-action-button" type="submit">
                                                <?= !empty($smiley['is_active']) ? 'Išjungti' : 'Įjungti' ?>
                                            </button>
                                        </form>
                                        <form method="post" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$smiley['id'] ?>">
                                            <button class="btn btn-sm btn-danger admin-danger-button" type="submit" data-confirm-message="Ar tikrai norite ištrinti šią šypsenėlę?">Trinti</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$smileys): ?>
                            <tr>
                                <td colspan="9" class="text-secondary">Kol kas šypsenėlių dar nėra.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include THEMES . 'default/admin_footer.php'; ?>
