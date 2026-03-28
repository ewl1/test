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

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-4 align-items-center">
                    <div class="col-lg-7">
                        <h2 class="h4 mb-3">Sveiki atvykę</h2>
                        <p class="text-secondary mb-0">Pagrindinis puslapis dabar naudoja realų turinį ir paneles vietoje demonstracinių Bootstrap kortelių. Toliau galite pildyti įrašais, forumu, šaukykla ir savo moduliais.</p>
                    </div>
                    <div class="col-lg-5">
                        <div class="d-grid gap-2">
                            <a class="btn btn-primary" href="<?= public_path('forum.php') ?>">Atidaryti forumą</a>
                            <a class="btn btn-outline-secondary" href="<?= public_path('shoutbox.php') ?>">Atidaryti šaukyklą</a>
                            <a class="btn btn-outline-secondary" href="<?= public_path('search.php') ?>">Paieška</a>
                        </div>
                    </div>
                </div>
            </div>
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
