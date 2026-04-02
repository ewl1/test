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
        'profile_comments_per_page',
        'content_comments_per_page',
        'content_comments_flood_seconds',
        'content_comments_rate_limit_count',
        'content_comments_rate_limit_window_seconds',
        'content_comments_badwords',
        'badwords_enabled',
        'badwords_list',
        'security_headers_enabled',
        'security_header_hsts',
        'security_header_frame_options',
        'security_header_content_type_options',
        'security_header_referrer_policy',
        'security_header_permissions_policy',
        'security_header_coop',
        'security_header_corp',
    ] as $key) {
        save_setting($key, $_POST[$key] ?? '');
    }

    flash('success', 'Nustatymai issaugoti.');
    redirect('settings.php');
}

include THEMES . 'default/admin_header.php';
?>
<div class="<?= e(admin_layout_preset_class('split-settings', 'admin-layout-form-shell')) ?>">
<?php
admin_render_page_header([
    'variant' => 'split-settings',
    'title' => 'Svetaines nustatymai',
    'subtitle' => 'Branduolio, rodymo ir SEO pagrindo nustatymai vienoje vietoje',
    'actions' => [
        [
            'label' => 'Atidaryti infusion modulius',
            'href' => 'infusions.php',
            'class' => 'btn btn-outline-primary admin-action-button',
            'icon' => 'fa-solid fa-puzzle-piece',
        ],
        [
            'label' => 'Saukylos nustatymai',
            'href' => 'infusion-admin.php?folder=shoutbox',
            'class' => 'btn btn-outline-secondary admin-action-button',
            'icon' => 'fa-solid fa-comments',
        ],
    ],
]);
?>

<?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>

<form method="post" class="row g-4 admin-layout-split">
<?= csrf_field() ?>
<div class="col-lg-8 admin-layout-main">
    <div class="card mb-4">
        <div class="card-header">SEO ir pagrindas</div>
        <div class="card-body row g-3">
            <div class="col-12">
                <label class="form-label">Svetaines pavadinimas</label>
                <input class="form-control" name="site_name" value="<?= e(setting('site_name', APP_NAME)) ?>">
            </div>
            <div class="col-12">
                <label class="form-label">Svetaines aprasymas</label>
                <textarea class="form-control" name="site_description" rows="3"><?= e(setting('site_description', '')) ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Keywords</label>
                <input class="form-control" name="site_keywords" value="<?= e(setting('site_keywords', '')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Maintenance</label>
                <select class="form-select" name="site_maintenance">
                    <option value="0" <?= setting('site_maintenance', '0') === '0' ? 'selected' : '' ?>>Isjungta</option>
                    <option value="1" <?= setting('site_maintenance', '0') === '1' ? 'selected' : '' ?>>Ijungta</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">`showcopyright()` tekstas</label>
                <input class="form-control" name="copyright_text" value="<?= e(setting('copyright_text', '(c) ' . date('Y') . ' ' . APP_NAME)) ?>">
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">Rodymo nustatymai</div>
        <div class="card-body row g-3">
            <div class="col-md-6">
                <label class="form-label">`showMemoryUsage()`</label>
                <select class="form-select" name="show_memory_usage">
                    <option value="0" <?= setting('show_memory_usage', '0') === '0' ? 'selected' : '' ?>>Isjungta</option>
                    <option value="1" <?= setting('show_memory_usage', '0') === '1' ? 'selected' : '' ?>>Ijungta</option>
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
                <label class="form-label">`showcounter()`</label>
                <select class="form-select" name="show_counter">
                    <option value="0" <?= setting('show_counter', '0') === '0' ? 'selected' : '' ?>>Isjungta</option>
                    <option value="1" <?= setting('show_counter', '0') === '1' ? 'selected' : '' ?>>Ijungta</option>
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
                <label class="form-label">`showbanners()`</label>
                <select class="form-select" name="show_banners">
                    <option value="0" <?= setting('show_banners', '0') === '0' ? 'selected' : '' ?>>Isjungta</option>
                    <option value="1" <?= setting('show_banners', '0') === '1' ? 'selected' : '' ?>>Ijungta</option>
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
                <label class="form-label">`showsublinks()`</label>
                <select class="form-select" name="show_sublinks">
                    <option value="0" <?= setting('show_sublinks', '1') === '0' ? 'selected' : '' ?>>Isjungta</option>
                    <option value="1" <?= setting('show_sublinks', '1') === '1' ? 'selected' : '' ?>>Ijungta</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Profilio komentaru per puslapi</label>
                <input class="form-control" type="number" min="1" max="100" name="profile_comments_per_page" value="<?= e(setting('profile_comments_per_page', '10')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Turinio komentaru per puslapi</label>
                <input class="form-control" type="number" min="1" max="100" name="content_comments_per_page" value="<?= e(setting('content_comments_per_page', '10')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Komentaru flood tarpas sekundemis</label>
                <input class="form-control" type="number" min="3" max="3600" name="content_comments_flood_seconds" value="<?= e(setting('content_comments_flood_seconds', '30')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Komentaru limitas lange</label>
                <input class="form-control" type="number" min="1" max="100" name="content_comments_rate_limit_count" value="<?= e(setting('content_comments_rate_limit_count', '5')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Komentaru rate limit langas sekundemis</label>
                <input class="form-control" type="number" min="30" max="86400" name="content_comments_rate_limit_window_seconds" value="<?= e(setting('content_comments_rate_limit_window_seconds', '300')) ?>">
            </div>
            <div class="col-12">
                <label class="form-label">Globalus badwords filtras</label>
                <select class="form-select" name="badwords_enabled">
                    <option value="1" <?= setting('badwords_enabled', '1') === '1' ? 'selected' : '' ?>>Ijungtas</option>
                    <option value="0" <?= setting('badwords_enabled', '1') === '0' ? 'selected' : '' ?>>Isjungtas</option>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Globalus badwords sarasas</label>
                <textarea class="form-control" name="badwords_list" rows="4" placeholder="zodis1&#10;zodis2&#10;zodis3"><?= e(setting('badwords_list', setting('content_comments_badwords', ''))) ?></textarea>
                <div class="form-text">Sis sarasas naudojamas komentarams, forumui ir saukyklai.</div>
            </div>
            <div class="col-12">
                <label class="form-label">Komentaru badwords</label>
                <textarea class="form-control" name="content_comments_badwords" rows="4" placeholder="zodis1&#10;zodis2&#10;zodis3"><?= e(setting('content_comments_badwords', '')) ?></textarea>
                <div class="form-text">Legacy fallback komentarams. Jei globalus sarasas uzpildytas, jis turi prioriteta.</div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">Saugumo antrastes</div>
        <div class="card-body row g-3">
            <div class="col-md-6">
                <label class="form-label">Security headers manager</label>
                <select class="form-select" name="security_headers_enabled">
                    <option value="1" <?= setting('security_headers_enabled', '1') === '1' ? 'selected' : '' ?>>Ijungtas</option>
                    <option value="0" <?= setting('security_headers_enabled', '1') === '0' ? 'selected' : '' ?>>Isjungtas</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">HSTS</label>
                <select class="form-select" name="security_header_hsts">
                    <option value="1" <?= setting('security_header_hsts', '1') === '1' ? 'selected' : '' ?>>Ijungtas</option>
                    <option value="0" <?= setting('security_header_hsts', '1') === '0' ? 'selected' : '' ?>>Isjungtas</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">X-Frame-Options</label>
                <select class="form-select" name="security_header_frame_options">
                    <option value="1" <?= setting('security_header_frame_options', '1') === '1' ? 'selected' : '' ?>>SAMEORIGIN</option>
                    <option value="0" <?= setting('security_header_frame_options', '1') === '0' ? 'selected' : '' ?>>Isjungta</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">X-Content-Type-Options</label>
                <select class="form-select" name="security_header_content_type_options">
                    <option value="1" <?= setting('security_header_content_type_options', '1') === '1' ? 'selected' : '' ?>>nosniff</option>
                    <option value="0" <?= setting('security_header_content_type_options', '1') === '0' ? 'selected' : '' ?>>Isjungta</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Referrer-Policy</label>
                <input class="form-control" name="security_header_referrer_policy" value="<?= e(setting('security_header_referrer_policy', 'strict-origin-when-cross-origin')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Permissions-Policy</label>
                <input class="form-control" name="security_header_permissions_policy" value="<?= e(setting('security_header_permissions_policy', 'camera=(), microphone=(), geolocation=()')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Cross-Origin-Opener-Policy</label>
                <input class="form-control" name="security_header_coop" value="<?= e(setting('security_header_coop', 'same-origin')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Cross-Origin-Resource-Policy</label>
                <input class="form-control" name="security_header_corp" value="<?= e(setting('security_header_corp', 'same-site')) ?>">
            </div>
        </div>
    </div>

    <div class="admin-layout-form-actions">
        <div class="small admin-page-subtitle">Pakeitimai taikomi iskart po issaugojimo.</div>
        <button class="btn btn-primary admin-action-button">
            <i class="fa-solid fa-floppy-disk"></i>
            <span>Issaugoti</span>
        </button>
    </div>
</div>

<div class="col-lg-4 admin-layout-sidebar">
    <div class="card">
        <div class="card-header">Greitos nuorodos</div>
        <div class="card-body">
            <p class="text-secondary mb-3">Moduliu nustatymai perkelti i atitinkamus infusion administravimo puslapius.</p>
            <div class="d-grid gap-2">
                <a class="btn btn-outline-primary admin-action-button" href="infusions.php">
                    <i class="fa-solid fa-puzzle-piece"></i>
                    <span>Atidaryti infusion modulius</span>
                </a>
                <a class="btn btn-outline-secondary admin-action-button" href="infusion-admin.php?folder=shoutbox">
                    <i class="fa-solid fa-comments"></i>
                    <span>Saukyklos nustatymai</span>
                </a>
            </div>
        </div>
    </div>
</div>
</form>
</div>
<?php include THEMES . 'default/admin_footer.php'; ?>
