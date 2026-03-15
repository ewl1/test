<?php
require_once __DIR__ . '/_guard.php';
require_permission($pdo, 'posts.delete');
delete_post($pdo, (int)($_GET['id'] ?? 0));
flash('success', 'Postas ištrintas.');
redirect('posts.php');
