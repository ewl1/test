<?php
if (!headers_sent()) {
    header_remove('X-Powered-By');
}
$scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
$basePath = rtrim(str_replace('\\', '/', dirname(dirname($scriptName))), '/');
$bootstrapCss = ($basePath !== '' ? $basePath : '') . '/themes/default/bootstrap.min.css';
$defaultBackUrl = ($basePath !== '' ? $basePath : '') . '/index.php';
if (empty($errorBackUrl) || !preg_match('#^(?:https?:)?/#i', (string)$errorBackUrl)) {
    $errorBackUrl = $defaultBackUrl;
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
<style>
body {
    min-height: 100vh;
    margin: 0;
    background:
        radial-gradient(circle at top left, rgba(13, 110, 253, 0.14), transparent 32%),
        radial-gradient(circle at bottom right, rgba(25, 135, 84, 0.14), transparent 28%),
        #f8f9fa;
}
.error-shell {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem 1rem;
}
.error-card {
    width: 100%;
    max-width: 720px;
    border: 0;
    border-radius: 1.25rem;
    box-shadow: 0 20px 60px rgba(33, 37, 41, 0.12);
}
.error-code {
    font-size: clamp(3rem, 10vw, 6rem);
    line-height: 1;
    font-weight: 800;
    letter-spacing: -0.05em;
}
</style>
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
                <button class="btn btn-outline-secondary" type="button" onclick="history.back()">Grįžti atgal</button>
            </div>
        </div>
    </div>
</div>
</body>
</html>
