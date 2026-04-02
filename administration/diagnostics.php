<?php
require_once __DIR__ . '/_guard.php';
require_any_permission(['settings.manage', 'logs.view']);

$message = null;
$messageType = 'success';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && (string)($_POST['action'] ?? '') === 'rebuild_sitemap') {
    verify_csrf();
    require_permission('settings.manage');

    $result = sitemap_rebuild();
    if ($result['ok']) {
        $message = 'Sitemap perkurta: ' . (int)$result['entries'] . ' URL, ' . (int)$result['bytes'] . ' B.';
        $messageType = 'success';
    } else {
        $message = 'Sitemap nepavyko perkurti.';
        $messageType = 'danger';
    }
}

$diagnostics = app_runtime_diagnostics();
$opcache = $diagnostics['server']['opcache'];

$badgeClass = static function ($ok) {
    return $ok ? 'text-bg-success' : 'text-bg-secondary';
};

include THEMES . 'default/admin_header.php';
?>
<div class="<?= e(admin_layout_preset_class('diagnostics', 'admin-layout-diagnostics-shell')) ?>">
<?php
admin_render_page_header([
    'variant' => 'diagnostics',
    'title' => 'Serverio diagnostika',
    'subtitle' => 'Branduolio ir serverio busena vienoje vietoje',
    'badge_html' => '<span class="badge text-bg-dark">v' . e(app_version()) . '</span>',
    'actions_html' => has_permission($GLOBALS['pdo'], (int)(current_user()['id'] ?? 0), 'settings.manage')
        ? '<form method="post" class="d-inline-block">'
            . csrf_field()
            . '<input type="hidden" name="action" value="rebuild_sitemap">'
            . '<button type="submit" class="btn btn-outline-primary admin-action-button"><i class="fa-solid fa-sitemap me-2"></i>Perkurti sitemap.xml</button>'
            . '</form>'
        : '',
]);

if ($message): ?>
    <div class="alert alert-<?= e($messageType) ?> mb-4"><?= e($message) ?></div>
<?php endif;

admin_render_stat_strip([
    [
        'label' => 'PHP',
        'value' => $diagnostics['php']['version'],
        'tone' => 'info',
        'icon' => 'fa-brands fa-php',
    ],
    [
        'label' => 'OPcache',
        'value' => is_opcache_enabled() ? 'Ijungtas' : 'Isjungtas',
        'tone' => is_opcache_enabled() ? 'success' : 'warning',
        'icon' => 'fa-solid fa-gauge-high',
    ],
    [
        'label' => 'Pletiniai',
        'value' => (string)count($diagnostics['extensions']),
        'tone' => 'info',
        'icon' => 'fa-solid fa-puzzle-piece',
    ],
]);
?>

<div class="row g-4 admin-layout-diagnostics-grid">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">Programa</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Pavadinimas</dt>
                    <dd class="col-sm-8"><?= e($diagnostics['application']['name']) ?></dd>
                    <dt class="col-sm-4">Versija</dt>
                    <dd class="col-sm-8"><?= e($diagnostics['application']['version']) ?></dd>
                    <dt class="col-sm-4">SITE_URL</dt>
                    <dd class="col-sm-8"><code class="admin-path-code admin-path-code-strong"><?= e($diagnostics['application']['site_url']) ?></code></dd>
                    <dt class="col-sm-4">Katalogas</dt>
                    <dd class="col-sm-8"><code class="admin-path-code admin-path-code-strong"><?= e($diagnostics['application']['basedir']) ?></code></dd>
                    <dt class="col-sm-4">Maintenance</dt>
                    <dd class="col-sm-8"><span class="badge <?= $diagnostics['application']['maintenance'] ? 'text-bg-warning' : 'text-bg-success' ?>"><?= $diagnostics['application']['maintenance'] ? 'On' : 'Off' ?></span></dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">PHP ir serveris</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">PHP</dt>
                    <dd class="col-sm-8"><?= e($diagnostics['php']['version']) ?></dd>
                    <dt class="col-sm-4">SAPI</dt>
                    <dd class="col-sm-8"><?= e($diagnostics['php']['sapi']) ?></dd>
                    <dt class="col-sm-4">Serveris</dt>
                    <dd class="col-sm-8"><?= e($diagnostics['server']['software']) ?></dd>
                    <dt class="col-sm-4">HTTPS</dt>
                    <dd class="col-sm-8"><span class="badge <?= $diagnostics['server']['https'] ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $diagnostics['server']['https'] ? 'On' : 'Off' ?></span></dd>
                    <dt class="col-sm-4">php.ini</dt>
                    <dd class="col-sm-8"><code class="admin-path-code admin-path-code-strong"><?= e($diagnostics['php']['loaded_ini']) ?></code></dd>
                    <dt class="col-sm-4">Laiko juosta</dt>
                    <dd class="col-sm-8"><?= e($diagnostics['php']['timezone']) ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">Resursu limitai</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">memory_limit</dt>
                    <dd class="col-sm-7"><?= e($diagnostics['php']['memory_limit_raw']) ?> <span class="text-secondary small">(<?= e($diagnostics['php']['memory_limit']) ?>)</span></dd>
                    <dt class="col-sm-5">upload_max_filesize</dt>
                    <dd class="col-sm-7"><?= e($diagnostics['php']['upload_max_filesize_raw']) ?> <span class="text-secondary small">(<?= e($diagnostics['php']['upload_max_filesize']) ?>)</span></dd>
                    <dt class="col-sm-5">post_max_size</dt>
                    <dd class="col-sm-7"><?= e($diagnostics['php']['post_max_size_raw']) ?> <span class="text-secondary small">(<?= e($diagnostics['php']['post_max_size']) ?>)</span></dd>
                    <dt class="col-sm-5">max_execution_time</dt>
                    <dd class="col-sm-7"><?= (int)$diagnostics['php']['max_execution_time'] ?> s</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">OPcache</div>
            <div class="card-body">
                <p class="mb-3">
                    <span class="badge <?= is_opcache_enabled() ? 'text-bg-success' : 'text-bg-secondary' ?>">
                        <?= is_opcache_enabled() ? 'Ijungtas' : 'Isjungtas' ?>
                    </span>
                </p>
                <?php if ($opcache): ?>
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Cached scripts</dt>
                        <dd class="col-sm-7"><?= (int)$opcache['cached_scripts'] ?></dd>
                        <dt class="col-sm-5">Hit rate</dt>
                        <dd class="col-sm-7"><?= $opcache['hit_rate'] !== null ? e((string)$opcache['hit_rate']) . '%' : 'n/a' ?></dd>
                        <dt class="col-sm-5">Hits / misses</dt>
                        <dd class="col-sm-7"><?= (int)$opcache['hits'] ?> / <?= (int)$opcache['misses'] ?></dd>
                        <dt class="col-sm-5">Naudojama atmintis</dt>
                        <dd class="col-sm-7"><?= e(format_bytes_human((int)$opcache['used_memory'])) ?></dd>
                        <dt class="col-sm-5">Laisva atmintis</dt>
                        <dd class="col-sm-7"><?= e(format_bytes_human((int)$opcache['free_memory'])) ?></dd>
                    </dl>
                <?php else: ?>
                    <p class="text-secondary mb-0">Issami OPcache statistika siuo metu nepasiekiama.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">Pletiniai</div>
            <div class="card-body d-flex flex-wrap gap-2">
                <?php foreach ($diagnostics['extensions'] as $extension => $enabled): ?>
                    <span class="badge <?= $enabled ? 'text-bg-success' : 'text-bg-secondary' ?> admin-extension-badge">
                        <?= e($extension) ?>
                    </span>
                <?php endforeach; ?>
                <div class="alert alert-info mt-3 mb-0 w-100">
                    <strong>Butini:</strong> `pdo`, `pdo_mysql`, `mbstring`, `json`, `session`, `fileinfo`, `openssl`.
                    <strong class="ms-2">Rekomenduojami:</strong> `curl`, `gd`, `intl`, `Zend OPcache`.
                    <strong class="ms-2">Pasirenkami:</strong> `zip`, `sodium`, `exif`, XML seimos pletiniai, jei ju konkreciai reikia moduliams.
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">Keliai ir teises</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0 admin-table-strong">
                        <thead><tr><th>Kelias</th><th>Yra</th><th>Writable</th></tr></thead>
                        <tbody>
                        <?php foreach ($diagnostics['paths'] as $pathInfo): ?>
                            <tr>
                                <td><code class="admin-path-code admin-path-code-strong"><?= e($pathInfo['path']) ?></code></td>
                                <td><span class="badge <?= $badgeClass($pathInfo['exists']) ?>"><?= $pathInfo['exists'] ? 'Taip' : 'Ne' ?></span></td>
                                <td><span class="badge <?= $badgeClass($pathInfo['writable']) ?>"><?= $pathInfo['writable'] ? 'Taip' : 'Ne' ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td><code class="admin-path-code admin-path-code-strong"><?= e(sitemap_path()) ?></code></td>
                            <td><span class="badge <?= $badgeClass(is_file(sitemap_path())) ?>"><?= is_file(sitemap_path()) ? 'Taip' : 'Ne' ?></span></td>
                            <td><span class="badge <?= $badgeClass(is_writable(dirname(sitemap_path()))) ?>"><?= is_writable(dirname(sitemap_path())) ? 'Taip' : 'Ne' ?></span></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header">Saugumo indikatoriai</div>
            <div class="card-body d-flex flex-wrap gap-2">
                <span class="badge text-bg-success">CSRF formoms: On</span>
                <span class="badge text-bg-success">Logout tik per POST: On</span>
                <span class="badge text-bg-success">Login brute-force ribojimas: On</span>
                <span class="badge text-bg-success">Upload MIME whitelist: On</span>
                <span class="badge text-bg-success">Upload extension whitelist: On</span>
                <span class="badge text-bg-success">Uploads vykdymas Apache puseje blokuojamas</span>
            </div>
        </div>
    </div>
</div>
</div>
<?php include THEMES . 'default/admin_footer.php'; ?>
