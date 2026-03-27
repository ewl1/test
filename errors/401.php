<?php
http_response_code(401);
$errorCode = 401;
$errorTitle = 'Reikalingas prisijungimas';
$errorMessage = 'Norint pasiekti šį puslapį, reikia prisijungti.';
$errorBackUrl = '../login.php';
require __DIR__ . '/template.php';
