<?php
require_once __DIR__ . '/_guard.php';
require_permission('panels.manage');
$positions = ['left','u_center','l_center','right','au_center','bl_center'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach ($_POST['panels'] ?? [] as $id => $panel) {
        $stmt = $GLOBALS['pdo']->prepare("UPDATE infusion_panels SET panel_name=:n, position=:p, sort_order=:s, is_enabled=:e WHERE id=:id");
        $stmt->execute([
            ':id' => (int)$id,
            ':n' => trim($panel['panel_name'] ?? 'Panel'),
            ':p' => in_array(($panel['position'] ?? 'left'), $positions, true) ? $panel['position'] : 'left',
            ':s' => (int)($panel['sort_order'] ?? 0),
            ':e' => isset($panel['is_enabled']) ? 1 : 0
        ]);
    }

    if (($_POST['action'] ?? '') === 'create') {
        $stmt = $GLOBALS['pdo']->prepare("INSERT INTO infusion_panels (panel_name, position, sort_order, is_enabled) VALUES (:n,:p,:s,1)");
        $stmt->execute([
            ':n' => trim($_POST['new_panel_name'] ?? 'New Panel'),
            ':p' => in_array(($_POST['new_position'] ?? 'left'), $positions, true) ? $_POST['new_position'] : 'left',
            ':s' => 999
        ]);
    }

    audit_log(current_user()['id'], 'panels_update', 'infusion_panels');
    flash('success', 'Panelės atnaujintos.');
    redirect('panels.php');
}

$panels = $GLOBALS['pdo']->query("SELECT * FROM infusion_panels ORDER BY position ASC, sort_order ASC, id ASC")->fetchAll();
$grouped = [];
foreach ($positions as $pos) $grouped[$pos] = [];
foreach ($panels as $panel) $grouped[$panel['position']][] = $panel;

include THEMES . 'default/admin_header.php';
?>
<h1 class="h3 mb-3">Panelių / infusionų išdėstymas</h1>
<div class="alert alert-info">SortableJS prijungtas. Gali tempti paneles tarp pozicijų ir išsaugoti.</div>
<?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>

<form method="post">
  <?= csrf_field() ?>

  <div class="card mb-4">
    <div class="card-header">Nauja panelė</div>
    <div class="card-body row g-2 align-items-end">
      <input type="hidden" name="action" value="create">
      <div class="col-md-6"><label class="form-label">Pavadinimas</label><input class="form-control" name="new_panel_name" value="New Panel"></div>
      <div class="col-md-4"><label class="form-label">Pozicija</label><select class="form-select" name="new_position"><?php foreach ($positions as $pos): ?><option value="<?= e($pos) ?>"><?= e($pos) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-2"><button class="btn btn-primary w-100">Pridėti</button></div>
    </div>
  </div>

  <div class="row g-3">
    <?php foreach ($positions as $position): ?>
      <div class="col-lg-4">
        <div class="card h-100">
          <div class="card-header d-flex justify-content-between"><span><?= e($position) ?></span><span class="badge text-bg-secondary"><?= count($grouped[$position]) ?></span></div>
          <div class="card-body">
            <div class="sortable-panel-list d-grid gap-2" data-position="<?= e($position) ?>">
              <?php foreach ($grouped[$position] as $panel): ?>
                <div class="card panel-item" data-id="<?= (int)$panel['id'] ?>">
                  <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <strong><?= e($panel['panel_name']) ?></strong>
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
                        <label class="form-label">Sort</label>
                        <input class="form-control" name="panels[<?= (int)$panel['id'] ?>][sort_order]" value="<?= (int)$panel['sort_order'] ?>">
                      </div>
                      <div class="col-12">
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" name="panels[<?= (int)$panel['id'] ?>][is_enabled]" <?= (int)$panel['is_enabled'] ? 'checked' : '' ?>>
                          <label class="form-check-label">Įjungta</label>
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

  <div class="mt-3"><button class="btn btn-success">Išsaugoti išdėstymą</button></div>
</form>
<?php include THEMES . 'default/admin_footer.php'; ?>
