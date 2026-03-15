<?php
require_once __DIR__ . '/include/bootstrap.php';
[$ok, $message] = activate_user_by_token($pdo, $_GET['token'] ?? '');
flash($ok ? 'success' : 'error', $message);
redirect('login.php');
