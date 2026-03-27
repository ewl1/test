<?php
http_response_code(500);
$errorCode = 500;
$errorTitle = 'Vidinė serverio klaida';
$errorMessage = 'Įvyko nenumatyta klaida. Ji užregistruota žurnale.';
$errorBackUrl = '../index.php';
require __DIR__ . '/template.php';
