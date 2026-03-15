<?php
require_once __DIR__ . '/includes/bootstrap.php';
include THEMES . setting('current_theme', CURRENT_THEME) . '/header.php';
?>
<div class="row g-4">
    <aside class="col-lg-3">
        <?= render_panels('left') ?>
    </aside>

    <main class="col-lg-6">
        <?= render_panels('u_center') ?>
        <div class="card mb-3"><div class="card-body">
            <h1 class="h3"><?= e(setting('site_name', APP_NAME)) ?></h1>
            <p class="text-secondary mb-0"><?= e(setting('site_description', 'Mini CMS Pro svetainė')) ?></p>
        </div></div>
        <?= render_panels('l_center') ?>

        <div class="row row-cols-1 row-cols-md-2 g-3">
            <?php foreach (['Sidebars','Dropdowns','List groups','Modals','Badges','Breadcrumbs','Buttons','Checkout','Navbars','Containers','Grid system','Form control','Form text','Sizing','Select','Color','Datalists','Checks and radios','Inline','Toggle buttons','Outlined styles','Input group','Wrapping','Border radius','Multiple inputs','Custom forms','Floating labels','Textareas','Layout','Tooltips','Accordion','Alerts','Link color','Icons','Progress','Navs and tabs'] as $item): ?>
                <div class="col"><div class="card h-100"><div class="card-body"><span class="badge text-bg-secondary mb-2">UI</span><div class="fw-semibold"><?= e($item) ?></div></div></div></div>
            <?php endforeach; ?>
        </div>

        <?= render_panels('bl_center') ?>
    </main>

    <aside class="col-lg-3">
        <?= render_panels('right') ?>
    </aside>
</div>

<div class="container-fluid mt-3">
    <?= render_panels('au_center') ?>
</div>

<?php include THEMES . setting('current_theme', CURRENT_THEME) . '/footer.php'; ?>
