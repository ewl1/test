<?php
http_response_code(400);
$errorCode = 400;
$errorTitle = 'Netinkama užklausa';
$errorMessage = 'Užklausa negalėjo būti apdorota. Patikrinkite įvestus duomenis ir bandykite dar kartą.';
$errorBackUrl = '../index.php';
require __DIR__ . '/template.php';
