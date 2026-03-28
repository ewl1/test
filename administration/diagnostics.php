<?php
require_once __DIR__ . '/_guard.php';
require_any_permission(['settings.manage', 'logs.view']);

$diagnostics = app_runtime_diagnostics();
$opcache = $diagnostics['server']['opcache'];

$badgeClass = static function ($ok) {
    return $ok ? 'text-bg-success' : 'text-bg-secondary';
};

include THEMES . 'default/admin_header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 mb-0">Serverio diagnostika</h1>
        <div class="admin-page-subtitle small">Branduolio ir serverio būsena vienoje vietoje</div>
    </div>
    <span class="badge text-bg-dark">v<?= e(app_version()) ?></span>
</div>

<div class="row g-4">
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
                    <dd class="col-sm-8"><code class="admin-path-code"><?= e($diagnostics['application']['site_url']) ?></code></dd>
                    <dt class="col-sm-4">Katalogas</dt>
                    <dd class="col-sm-8"><code class="admin-path-code"><?= e($diagnostics['application']['basedir']) ?></code></dd>
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
            <div class="card-header">Resursų limitai</div>
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
                        <?= is_opcache_enabled() ? 'Įjungtas' : 'Išjungtas' ?>
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
                    <p class="text-secondary mb-0">Išsami OPcache statistika šiuo metu nepasiekiama.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">Plėtiniai</div>
            <div class="card-body d-flex flex-wrap gap-2">
                <?php foreach ($diagnostics['extensions'] as $extension => $enabled): ?>
                    <span class="badge <?= $enabled ? 'text-bg-success' : 'text-bg-secondary' ?> admin-extension-badge">
                        <?= e($extension) ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">Keliai ir teisės</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Kelias</th><th>Yra</th><th>Writable</th></tr></thead>
                        <tbody>
                        <?php foreach ($diagnostics['paths'] as $pathInfo): ?>
                            <tr>
                                <td><code class="admin-path-code admin-path-code-strong"><?= e($pathInfo['path']) ?></code></td>
                                <td><span class="badge <?= $badgeClass($pathInfo['exists']) ?>"><?= $pathInfo['exists'] ? 'Taip' : 'Ne' ?></span></td>
                                <td><span class="badge <?= $badgeClass($pathInfo['writable']) ?>"><?= $pathInfo['writable'] ? 'Taip' : 'Ne' ?></span></td>
                            </tr>
                        <?php endforeach; ?>
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
                <span class="badge text-bg-success">Uploads vykdymas Apache pusėje blokuojamas</span>
            </div>
        </div>
    </div>
</div>
<?php include THEMES . 'default/admin_footer.php'; ?>
