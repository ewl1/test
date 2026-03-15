<?php
try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    $GLOBALS['pdo'] = $pdo;
} catch (Throwable $e) {
    @error_log($e->getMessage() . PHP_EOL, 3, BASEDIR . 'logs/error.log');
    die('DB connection error');
}
