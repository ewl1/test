<?php
http_response_code(408);
$errorCode = 408;
$errorTitle = 'Užklausos laikas baigėsi';
$errorMessage = 'Serveris per ilgai laukė užklausos. Pabandykite dar kartą.';
$errorBackUrl = '../index.php';
require __DIR__ . '/template.php';
