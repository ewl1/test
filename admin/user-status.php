<?php
require_once __DIR__ . '/_guard.php';
require_permission($pdo, 'users.manage');
change_user_status($pdo, (int)($_GET['id'] ?? 0), $_GET['status'] ?? 'inactive');
flash('success', 'Statusas pakeistas.');
redirect('users.php');
