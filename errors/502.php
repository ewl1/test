<?php
http_response_code(502);
$errorCode = 502;
$errorTitle = 'Serverio ryšio klaida';
$errorMessage = 'Nepavyko gauti atsakymo iš vidinės sistemos arba duomenų bazės.';
$errorBackUrl = '../index.php';
require __DIR__ . '/template.php';
