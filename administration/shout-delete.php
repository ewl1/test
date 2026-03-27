<?php define('IN_ADMIN', true); ?>
<?php
require_once __DIR__ . '/_guard.php';
require_permission($pdo, 'shoutbox.moderate');
delete_shout($pdo, (int)($_GET['id'] ?? 0));
flash('success', 'Žinutė ištrinta.');
redirect('shouts.php');
