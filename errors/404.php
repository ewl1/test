<?php
http_response_code(404);
$errorCode = 404;
$errorTitle = 'Puslapis nerastas';
$errorMessage = 'Ieškomas puslapis neegzistuoja arba buvo perkeltas.';
$errorBackUrl = '../index.php';
require __DIR__ . '/template.php';
