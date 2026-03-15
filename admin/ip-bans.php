<?php
require_once __DIR__ . '/_guard.php';
require_permission($pdo, 'ipban.manage');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $ip = trim($_POST['ip'] ?? '');
    $reason = trim($_POST['reason'] ?? '');
    $permanent = !empty($_POST['is_permanent']) ? 1 : 0;
    $until = !empty($_POST['banned_until']) ? $_POST['banned_until'] . ':00' : null;

    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        $stmt = $pdo->prepare("
            INSERT INTO ip_bans (ip_address, reason, banned_until, is_permanent, created_by, created_at)
            VALUES (INET6_ATON(:ip), :reason, :banned_until, :is_permanent, :created_by, NOW())
            ON DUPLICATE KEY UPDATE reason = VALUES(reason), banned_until = VALUES(banned_until), is_permanent = VALUES(is_permanent)
        ");
        $stmt->execute([
            ':ip' => $ip,
            ':reason' => $reason,
            ':banned_until' => $until,
            ':is_permanent' => $permanent,
            ':created_by' => $_SESSION['user']['id'],
        ]);
        audit_log($pdo, $_SESSION['user']['id'], 'ip_ban_save', 'ip_bans', null, ['ip' => $ip]);
        flash('success', 'IP išsaugotas.');
    } else {
        flash('error', 'Neteisingas IP.');
    }
    redirect('ip-bans.php');
}

$bans = $pdo->query("SELECT *, INET6_NTOA(ip_address) AS ip_text FROM ip_bans ORDER BY id DESC")->fetchAll();
include dirname(__DIR__) . '/theme/header.php';
?>
<h1>IP ban</h1>
<form method="post" class="card card-body mb-3">
    <?= csrf_input() ?>
    <div class="row g-2">
        <div class="col-md-4"><input class="form-control" name="ip" placeholder="IPv4 arba IPv6"></div>
        <div class="col-md-4"><input class="form-control" name="reason" placeholder="Priežastis"></div>
        <div class="col-md-3"><input class="form-control" type="datetime-local" name="banned_until"></div>
        <div class="col-md-1"><input class="form-check-input mt-2" type="checkbox" name="is_permanent" value="1"> <small>Visam</small></div>
    </div>
    <button class="btn btn-primary mt-3">Išsaugoti</button>
</form>

<table class="table table-striped">
    <tr><th>ID</th><th>IP</th><th>Priežastis</th><th>Iki</th><th>Pastovus</th></tr>
    <?php foreach ($bans as $ban): ?>
        <tr>
            <td><?= (int)$ban['id'] ?></td>
            <td><?= e($ban['ip_text']) ?></td>
            <td><?= e($ban['reason']) ?></td>
            <td><?= e(format_dt($ban['banned_until'])) ?></td>
            <td><?= (int)$ban['is_permanent'] ? 'Taip' : 'Ne' ?></td>
        </tr>
    <?php endforeach; ?>
</table>
<?php include dirname(__DIR__) . '/theme/footer.php'; ?>
