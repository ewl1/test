<?php
require_once __DIR__ . '/includes/bootstrap.php';

$userId = (int)($_GET['id'] ?? 0);
$profile = fetch_public_user_profile($userId);
if (!$profile) {
    abort_http(404, 'Narys nerastas.');
}

$viewer = current_user();
$viewerIsAdmin = $viewer && has_permission($GLOBALS['pdo'], (int)$viewer['id'], 'admin.access');
$latestIp = $viewerIsAdmin ? fetch_user_latest_ip((int)$profile['id']) : null;
$banStatus = $viewerIsAdmin && $latestIp ? fetch_ip_ban_status($latestIp) : null;
$shoutCount = count_user_shoutbox_messages((int)$profile['id']);

include __DIR__ . '/themes/default/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row align-items-md-center gap-4">
                    <img src="<?= escape_url(user_avatar_url($profile)) ?>" alt="" class="user-profile-avatar">
                    <div>
                        <h1 class="h3 mb-1"><?= e($profile['username']) ?></h1>
                        <div class="text-secondary mb-2"><?= e($profile['role_name'] ?? 'Narys') ?></div>
                        <?php if (!empty($profile['signature'])): ?>
                            <div class="user-signature"><?= render_user_signature($profile['signature']) ?></div>
                        <?php else: ?>
                            <div class="text-secondary">Narys dar nepridėjo parašo.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="user-stat-card">
                    <div class="small text-secondary mb-1">Šaukyklos žinutės</div>
                    <div class="h3 mb-0"><?= (int)$shoutCount ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="user-stat-card">
                    <div class="small text-secondary mb-1">Prisijungė</div>
                    <div class="h6 mb-0"><?= e(format_dt($profile['created_at'])) ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="user-stat-card">
                    <div class="small text-secondary mb-1">Statusas</div>
                    <div class="h6 mb-0"><?= e($profile['status'] ?? 'active') ?></div>
                </div>
            </div>
        </div>

        <?php if ($viewerIsAdmin): ?>
            <div class="card">
                <div class="card-header">Admin informacija</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="small text-secondary mb-1">Paskutinis IP</div>
                            <div><?= e($latestIp ?: 'Nėra duomenų') ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="small text-secondary mb-1">BAN</div>
                            <?php if ($banStatus): ?>
                                <div class="fw-semibold text-danger">Taip</div>
                                <div class="small text-secondary">Priežastis: <?= e($banStatus['reason'] ?? '-') ?></div>
                                <div class="small text-secondary">Iki: <?= e(format_dt($banStatus['banned_until'], 'neribotai')) ?></div>
                            <?php else: ?>
                                <div class="fw-semibold text-success">Ne</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/themes/default/footer.php'; ?>
