<?php
if (!headers_sent()) {
    http_response_code(503);
    header('Content-Type: text/html; charset=UTF-8');
    header('Retry-After: 3600');
    header_remove('X-Powered-By');
}

$scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/maintenance.php'));
$basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
$bootstrapCss = ($basePath !== '' ? $basePath : '') . '/themes/default/bootstrap.min.css';
$maintenanceCss = ($basePath !== '' ? $basePath : '') . '/themes/default/maintenance.css';
$adminUrl = ($basePath !== '' ? $basePath : '') . '/administration/login.php';
$retryUrl = ($basePath !== '' ? $basePath : '') . '/maintenance.php';
?>
<!doctype html>
<html lang="lt">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>503 | Svetainė laikinai nepasiekiama</title>
<link rel="stylesheet" href="<?= htmlspecialchars($bootstrapCss, ENT_QUOTES, 'UTF-8') ?>">
<link rel="stylesheet" href="<?= htmlspecialchars($maintenanceCss, ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
<div class="maintenance-shell">
    <div class="card maintenance-card">
        <div class="card-body p-4 p-md-5">
            <div class="badge text-bg-warning mb-3">Priežiūra</div>
            <div class="maintenance-code text-warning-emphasis mb-3">503</div>
            <h1 class="h2 mb-3">Svetainė laikinai nepasiekiama</h1>
            <p class="text-secondary fs-5 mb-4">Atliekami priežiūros darbai arba diegiami atnaujinimai. Bandykite vėliau dar kartą.</p>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-warning" href="<?= htmlspecialchars($adminUrl, ENT_QUOTES, 'UTF-8') ?>">Administracija</a>
                <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($retryUrl, ENT_QUOTES, 'UTF-8') ?>">Bandyti dar kartą</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
