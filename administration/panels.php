<?php
require_once __DIR__ . '/_guard.php';
require_permission('panels.manage');

$positions = ['left', 'u_center', 'l_center', 'right', 'au_center', 'bl_center'];
$positionLabels = [
    'left' => 'Kaire',
    'u_center' => 'Virsutinis centras',
    'l_center' => 'Centras',
    'right' => 'Desine',
    'au_center' => 'Virs turinio',
    'bl_center' => 'Po turiniu',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? 'save_layout';

    if ($action === 'create') {
        $panelName = trim((string)($_POST['new_panel_name'] ?? ''));
        if ($panelName === '') {
            $panelName = 'Nauja panele';
        }

        $stmt = $GLOBALS['pdo']->prepare('
            INSERT INTO infusion_panels (panel_name, position, sort_order, is_enabled)
            VALUES (:n, :p, :s, 1)
        ');
        $stmt->execute([
            ':n' => $panelName,
            ':p' => in_array(($_POST['new_position'] ?? 'left'), $positions, true) ? $_POST['new_position'] : 'left',
            ':s' => 999,
        ]);

        audit_log(current_user()['id'], 'panel_create', 'infusion_panels', $GLOBALS['pdo']->lastInsertId());
        flash('success', 'Panele sukurta.');
        redirect('panels.php');
    }

    foreach ($_POST['panels'] ?? [] as $id => $panel) {
        $stmt = $GLOBALS['pdo']->prepare('
            UPDATE infusion_panels
            SET panel_name = :n, position = :p, sort_order = :s, is_enabled = :e
            WHERE id = :id
        ');
        $stmt->execute([
            ':id' => (int)$id,
            ':n' => trim((string)($panel['panel_name'] ?? 'Panele')),
            ':p' => in_array(($panel['position'] ?? 'left'), $positions, true) ? $panel['position'] : 'left',
            ':s' => (int)($panel['sort_order'] ?? 0),
            ':e' => isset($panel['is_enabled']) ? 1 : 0,
        ]);
    }

    audit_log(current_user()['id'], 'panels_update', 'infusion_panels');
    flash('success', 'Paneliu isdestymas issaugotas.');
    redirect('panels.php');
}

$panels = $GLOBALS['pdo']->query('SELECT * FROM infusion_panels ORDER BY position ASC, sort_order ASC, id ASC')->fetchAll();
$grouped = [];
foreach ($positions as $position) {
    $grouped[$position] = [];
}
foreach ($panels as $panel) {
    $grouped[$panel['position']][] = $panel;
}

include THEMES . 'default/admin_header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <div>
        <h1 class="h3 mb-1">Paneliu isdestymas</h1>
        <div class="admin-page-subtitle">Perkelkite paneles tarp zonu, stebekite aktyvias drop vietas ir po pakeitimu issaugokite isdestyma.</div>
    </div>
    <div class="admin-panels-save-indicator" data-panels-feedback-badge>
        <span class="badge text-bg-secondary">Busena: sinchronizuota</span>
    </div>
</div>

<div class="alert alert-info admin-panels-feedback" data-panels-feedback>
    <strong>Drag &amp; drop paruostas.</strong> Tempiant bus paryskintos galimos numetimo zonos, o neissaugoti pakeitimai bus pazymeti virsuje ir prie issaugojimo mygtuko.
</div>
<?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($msg = flash('error')): ?><div class="alert alert-danger"><?= e($msg) ?></div><?php endif; ?>

<div class="card mb-4">
    <div class="card-header">Nauja panele</div>
    <div class="card-body">
        <form method="post" class="row g-2 align-items-end">
            <?= csrf_field() ?>
            <div class="col-md-6">
                <label class="form-label">Pavadinimas</label>
                <input class="form-control" name="new_panel_name" value="Nauja panele">
            </div>
            <div class="col-md-4">
                <label class="form-label">Pozicija</label>
                <select class="form-select" name="new_position">
                    <?php foreach ($positions as $position): ?>
                        <option value="<?= e($position) ?>"><?= e($positionLabels[$position] ?? $position) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100 admin-action-button" type="submit" name="action" value="create">
                    <i class="fa-solid fa-plus"></i> Prideti
                </button>
            </div>
        </form>
    </div>
</div>

<form method="post" data-panels-form>
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save_layout">

    <div class="row g-3">
        <?php foreach ($positions as $position): ?>
            <div class="col-lg-4">
                <div class="card h-100 admin-panel-column">
                    <div class="card-header d-flex justify-content-between align-items-center gap-2">
                        <span><?= e($positionLabels[$position] ?? $position) ?></span>
                        <span class="badge text-bg-secondary admin-panel-count" data-panel-count><?= count($grouped[$position]) ?></span>
                    </div>
                    <div class="card-body">
                        <div class="small admin-page-subtitle mb-2">Zona: <code class="admin-mono-pill"><?= e($position) ?></code></div>
                        <div class="sortable-panel-list d-grid gap-2" data-position="<?= e($position) ?>" data-position-label="<?= e($positionLabels[$position] ?? $position) ?>">
                            <div class="admin-panel-empty<?= count($grouped[$position]) ? ' d-none' : '' ?>" data-empty-state>
                                <i class="fa-solid fa-up-down-left-right"></i> Cia galite numesti panele.
                            </div>
                            <?php foreach ($grouped[$position] as $panel): ?>
                                <div class="card panel-item admin-panel-item" data-id="<?= (int)$panel['id'] ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2 gap-2">
                                            <div class="d-flex align-items-center gap-2 min-width-0">
                                                <button class="btn btn-sm btn-outline-secondary admin-panel-handle" type="button" title="Tempti panele">
                                                    <i class="fa-solid fa-grip-vertical"></i>
                                                </button>
                                                <strong class="admin-panel-item-title text-truncate"><?= e($panel['panel_name']) ?></strong>
                                            </div>
                                            <span class="badge text-bg-light border panel-position-badge"><?= e($panel['position']) ?></span>
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-12">
                                                <label class="form-label">Pavadinimas</label>
                                                <input class="form-control" name="panels[<?= (int)$panel['id'] ?>][panel_name]" value="<?= e($panel['panel_name']) ?>">
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label">Pozicija</label>
                                                <input class="form-control" name="panels[<?= (int)$panel['id'] ?>][position]" value="<?= e($panel['position']) ?>" readonly>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label">Rikiavimas</label>
                                                <input class="form-control" name="panels[<?= (int)$panel['id'] ?>][sort_order]" value="<?= (int)$panel['sort_order'] ?>">
                                            </div>
                                            <div class="col-12">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="panels[<?= (int)$panel['id'] ?>][is_enabled]" <?= (int)$panel['is_enabled'] ? 'checked' : '' ?>>
                                                    <label class="form-check-label">Ijungta</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-3 d-flex flex-wrap align-items-center justify-content-between gap-3 admin-panels-savebar" data-panels-savebar>
        <div class="small admin-page-subtitle" data-panels-feedback-text>Kol kas nera neissaugotu paneliu isdestymo pakeitimu.</div>
        <button class="btn btn-success admin-action-button" type="submit" name="action" value="save_layout" data-panels-save-button>
            <i class="fa-solid fa-floppy-disk"></i> Issaugoti isdestyma
        </button>
    </div>
</form>
<?php include THEMES . 'default/admin_footer.php'; ?>
