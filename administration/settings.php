<?php
require_once __DIR__ . '/_guard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    foreach ([
        'site_name',
        'site_description',
        'site_keywords',
        'site_maintenance',
        'show_memory_usage',
        'show_memory_usage_visibility',
        'copyright_text',
        'show_counter',
        'show_counter_visibility',
        'show_banners',
        'show_banners_visibility',
        'show_sublinks',
    ] as $key) {
        save_setting($key, $_POST[$key] ?? '');
    }

    $shoutboxOrder = strtolower((string)($_POST['shoutbox_order'] ?? 'desc'));
    save_setting('shoutbox_order', $shoutboxOrder === 'asc' ? 'asc' : 'desc');

    $shoutboxPerPage = max(5, min(100, (int)($_POST['shoutbox_messages_per_page'] ?? 20)));
    save_setting('shoutbox_messages_per_page', (string)$shoutboxPerPage);

    $shoutboxPanelMessages = max(3, min(20, (int)($_POST['shoutbox_panel_messages'] ?? 5)));
    save_setting('shoutbox_panel_messages', (string)$shoutboxPanelMessages);

    flash('success', 'Nustatymai išsaugoti.');
    redirect('settings.php');
}

include THEMES . 'default/admin_header.php';
?>
<h1 class="h3 mb-3">Svetainės nustatymai</h1>
<?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>

<form method="post" class="row g-4">
<?= csrf_field() ?>
<div class="col-lg-8">
<div class="card mb-4">
<div class="card-header">SEO ir pagrindas</div>
<div class="card-body row g-3">
<div class="col-12">
<label class="form-label">Svetainės pavadinimas</label>
<input class="form-control" name="site_name" value="<?= e(setting('site_name', APP_NAME)) ?>">
</div>
<div class="col-12">
<label class="form-label">Svetainės aprašymas</label>
<textarea class="form-control" name="site_description" rows="3"><?= e(setting('site_description', '')) ?></textarea>
</div>
<div class="col-12">
<label class="form-label">Keywords</label>
<input class="form-control" name="site_keywords" value="<?= e(setting('site_keywords', '')) ?>">
</div>
<div class="col-md-6">
<label class="form-label">Maintenance</label>
<select class="form-select" name="site_maintenance">
<option value="0" <?= setting('site_maintenance', '0') === '0' ? 'selected' : '' ?>>Išjungta</option>
<option value="1" <?= setting('site_maintenance', '0') === '1' ? 'selected' : '' ?>>Įjungta</option>
</select>
</div>
<div class="col-md-6">
<label class="form-label">showcopyright tekstas</label>
<input class="form-control" name="copyright_text" value="<?= e(setting('copyright_text', '© ' . date('Y') . ' ' . APP_NAME)) ?>">
</div>
</div>
</div>

<div class="card mb-4">
<div class="card-header">Rodymo nustatymai</div>
<div class="card-body row g-3">
<div class="col-md-6">
<label class="form-label">showMemoryUsage()</label>
<select class="form-select" name="show_memory_usage">
<option value="0" <?= setting('show_memory_usage', '0') === '0' ? 'selected' : '' ?>>Išjungta</option>
<option value="1" <?= setting('show_memory_usage', '0') === '1' ? 'selected' : '' ?>>Įjungta</option>
</select>
</div>
<div class="col-md-6">
<label class="form-label">Kas mato</label>
<select class="form-select" name="show_memory_usage_visibility">
<option value="all" <?= setting('show_memory_usage_visibility', 'all') === 'all' ? 'selected' : '' ?>>Visi</option>
<option value="admin" <?= setting('show_memory_usage_visibility', 'all') === 'admin' ? 'selected' : '' ?>>Tik adminai</option>
</select>
</div>
<div class="col-md-6">
<label class="form-label">showcounter()</label>
<select class="form-select" name="show_counter">
<option value="0" <?= setting('show_counter', '0') === '0' ? 'selected' : '' ?>>Išjungta</option>
<option value="1" <?= setting('show_counter', '0') === '1' ? 'selected' : '' ?>>Įjungta</option>
</select>
</div>
<div class="col-md-6">
<label class="form-label">Kas mato</label>
<select class="form-select" name="show_counter_visibility">
<option value="all" <?= setting('show_counter_visibility', 'all') === 'all' ? 'selected' : '' ?>>Visi</option>
<option value="admin" <?= setting('show_counter_visibility', 'all') === 'admin' ? 'selected' : '' ?>>Tik adminai</option>
</select>
</div>
<div class="col-md-6">
<label class="form-label">showbanners</label>
<select class="form-select" name="show_banners">
<option value="0" <?= setting('show_banners', '0') === '0' ? 'selected' : '' ?>>Išjungta</option>
<option value="1" <?= setting('show_banners', '0') === '1' ? 'selected' : '' ?>>Įjungta</option>
</select>
</div>
<div class="col-md-6">
<label class="form-label">Kas mato</label>
<select class="form-select" name="show_banners_visibility">
<option value="all" <?= setting('show_banners_visibility', 'all') === 'all' ? 'selected' : '' ?>>Visi</option>
<option value="admin" <?= setting('show_banners_visibility', 'all') === 'admin' ? 'selected' : '' ?>>Tik adminai</option>
</select>
</div>
<div class="col-md-6">
<label class="form-label">showsublinks</label>
<select class="form-select" name="show_sublinks">
<option value="0" <?= setting('show_sublinks', '1') === '0' ? 'selected' : '' ?>>Išjungta</option>
<option value="1" <?= setting('show_sublinks', '1') === '1' ? 'selected' : '' ?>>Įjungta</option>
</select>
</div>
</div>
</div>

<div class="card">
<div class="card-header">Šaukykla</div>
<div class="card-body row g-3">
<div class="col-md-6">
<label class="form-label">Žinučių eiliškumas</label>
<select class="form-select" name="shoutbox_order">
<option value="desc" <?= setting('shoutbox_order', 'desc') === 'desc' ? 'selected' : '' ?>>Naujausios viršuje</option>
<option value="asc" <?= setting('shoutbox_order', 'desc') === 'asc' ? 'selected' : '' ?>>Seniausios viršuje</option>
</select>
</div>
<div class="col-md-3">
<label class="form-label">Žinučių puslapyje</label>
<input class="form-control" type="number" min="5" max="100" name="shoutbox_messages_per_page" value="<?= e(setting('shoutbox_messages_per_page', '20')) ?>">
</div>
<div class="col-md-3">
<label class="form-label">Žinučių panelėje</label>
<input class="form-control" type="number" min="3" max="20" name="shoutbox_panel_messages" value="<?= e(setting('shoutbox_panel_messages', '5')) ?>">
</div>
</div>
</div>
</div>

<div class="col-lg-4">
<div class="card">
<div class="card-header">Bootstrap komponentų bazė</div>
<div class="card-body">
<div class="d-flex flex-wrap gap-2">
<?php foreach (['Sidebars', 'Dropdowns', 'List groups', 'Modals', 'Badges', 'Breadcrumbs', 'Buttons', 'Checkout', 'Navbars', 'Containers', 'Grid system', 'Form control', 'Form text', 'Sizing', 'Select', 'Color', 'Datalists', 'Checks and radios', 'Inline', 'Toggle buttons', 'Outlined styles', 'Input group', 'Wrapping', 'Border radius', 'Multiple inputs', 'Custom forms', 'Floating labels', 'Textareas', 'Layout', 'Tooltips', 'Accordion', 'Alerts', 'Link color', 'Icons', 'Progress', 'Navs and tabs'] as $item): ?>
<span class="badge text-bg-light border"><?= e($item) ?></span>
<?php endforeach; ?>
</div>
</div>
</div>
</div>

<div class="col-12"><button class="btn btn-primary">Išsaugoti</button></div>
</form>
<?php include THEMES . 'default/admin_footer.php'; ?>
