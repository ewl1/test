<?php
try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $GLOBALS['pdo'] = $pdo;
} catch (Throwable $e) {
    error_log('DB connection error: ' . $e->getMessage());
    if (function_exists('abort_http')) {
        abort_http(502, 'Nepavyko prisijungti prie duomenų bazės.');
    }
    die('DB connection error');
}
