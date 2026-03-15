<?php
require_once __DIR__ . '/_guard.php';
require_permission($pdo, 'users.manage');
delete_user($pdo, (int)($_GET['id'] ?? 0));
flash('success', 'Vartotojas ištrintas.');
redirect('users.php');
