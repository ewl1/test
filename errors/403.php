<?php
http_response_code(403);
$errorCode = 403;
$errorTitle = 'Prieiga uždrausta';
$errorMessage = 'Neturite teisių atlikti šio veiksmo.';
$errorBackUrl = '../index.php';
require __DIR__ . '/template.php';
