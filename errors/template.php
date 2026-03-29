<?php
if (!headers_sent()) {
    header_remove('X-Powered-By');
}
$scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
$basePath = rtrim(str_replace('\\', '/', dirname(dirname($scriptName))), '/');
$bootstrapCss = ($basePath !== '' ? $basePath : '') . '/themes/default/bootstrap.min.css';
$errorCss = ($basePath !== '' ? $basePath : '') . '/themes/default/error.css';
$defaultBackUrl = ($basePath !== '' ? $basePath : '') . '/index.php';
if (empty($errorBackUrl) || !preg_match('#^(?:https?:)?/#i', (string)$errorBackUrl)) {
    $errorBackUrl = $defaultBackUrl;
}

$backUrl = $defaultBackUrl;
$referer = trim((string)($_SERVER['HTTP_REFERER'] ?? ''));
if ($referer !== '') {
    $refererHost = (string)(parse_url($referer, PHP_URL_HOST) ?? '');
    $currentHost = (string)($_SERVER['HTTP_HOST'] ?? '');
    if (
        preg_match('#^(?:https?:)?/#i', $referer)
        && ($refererHost === '' || strcasecmp($refererHost, $currentHost) === 0)
    ) {
        $backUrl = $referer;
    }
}

$escape = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!doctype html>
<html lang="lt">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $escape($errorCode) ?> | <?= $escape($errorTitle) ?></title>
<link rel="stylesheet" href="<?= $escape($bootstrapCss) ?>">
<link rel="stylesheet" href="<?= $escape($errorCss) ?>">
</head>
<body>
<div class="error-shell">
    <div class="card error-card">
        <div class="card-body p-4 p-md-5">
            <div class="badge text-bg-dark mb-3">Mini CMS</div>
            <div class="error-code text-primary mb-3"><?= $escape($errorCode) ?></div>
            <h1 class="h2 mb-3"><?= $escape($errorTitle) ?></h1>
            <p class="text-secondary fs-5 mb-4"><?= $escape($errorMessage) ?></p>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-primary" href="<?= $escape($errorBackUrl) ?>">Grįžti į pradžią</a>
                <a class="btn btn-outline-secondary" href="<?= $escape($backUrl) ?>">Grįžti atgal</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
