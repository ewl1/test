<?php
require_once __DIR__ . '/include/bootstrap.php';
logout_user($pdo);
flash('success', 'Atsijungta.');
redirect('index.php');
