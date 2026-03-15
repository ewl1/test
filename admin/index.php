<?php
require_once __DIR__ . '/_guard.php';
$postCount = (int)$pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$userCount = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$shoutCount = (int)$pdo->query("SELECT COUNT(*) FROM shouts")->fetchColumn();
$logCount = (int)$pdo->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();
include dirname(__DIR__) . '/theme/header.php';
?>
<h1>Admin Dashboard</h1>
<div class="row g-3">
    <div class="col-md-3"><div class="card card-body"><strong>Postai</strong><div><?= $postCount ?></div></div></div>
    <div class="col-md-3"><div class="card card-body"><strong>Nariai</strong><div><?= $userCount ?></div></div></div>
    <div class="col-md-3"><div class="card card-body"><strong>Šūksniai</strong><div><?= $shoutCount ?></div></div></div>
    <div class="col-md-3"><div class="card card-body"><strong>Audit log</strong><div><?= $logCount ?></div></div></div>
</div>
<hr>
<div class="list-group">
    <a class="list-group-item list-group-item-action" href="posts.php">Postai</a>
    <a class="list-group-item list-group-item-action" href="users.php">Nariai</a>
    <a class="list-group-item list-group-item-action" href="permissions.php">Leidimai</a>
    <a class="list-group-item list-group-item-action" href="shouts.php">Šaukykla</a>
    <a class="list-group-item list-group-item-action" href="audit-logs.php">Audit log</a>
    <a class="list-group-item list-group-item-action" href="ip-bans.php">IP ban</a>
    <a class="list-group-item list-group-item-action" href="settings.php">Nustatymai</a>
</div>
<?php include dirname(__DIR__) . '/theme/footer.php'; ?>
