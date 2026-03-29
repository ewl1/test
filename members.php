<?php
require_once __DIR__ . '/includes/bootstrap.php';

$query = trim((string)($_GET['q'] ?? ''));
$params = [];
$sql = "
    SELECT u.id, u.username, u.email, u.avatar, u.created_at, r.name AS role_name, r.slug AS role_slug
    FROM users u
    LEFT JOIN roles r ON r.id = u.role_id
    WHERE u.is_active = 1
      AND u.status = 'active'
";

if ($query !== '') {
    $sql .= " AND (u.username LIKE :query OR u.email LIKE :query)";
    $params[':query'] = '%' . $query . '%';
}

$sql .= " ORDER BY u.username ASC, u.id ASC LIMIT 100";

$stmt = $GLOBALS['pdo']->prepare($sql);
$stmt->execute($params);
$members = $stmt->fetchAll();

include __DIR__ . '/themes/default/header.php';
?>
<div class="row justify-content-center">
    <div class="col-xl-10">
        <div class="card">
            <div class="card-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                <span><?= e(__('members.title')) ?></span>
                <form method="get" class="d-flex align-items-center gap-2">
                    <input
                        class="form-control form-control-sm"
                        type="search"
                        name="q"
                        value="<?= e($query) ?>"
                        maxlength="100"
                        placeholder="<?= e(__('members.search.placeholder')) ?>"
                    >
                    <button class="btn btn-sm btn-primary" type="submit">
                        <i class="fa-solid fa-magnifying-glass me-2"></i><?= e(__('nav.search.button')) ?>
                    </button>
                </form>
            </div>
            <div class="card-body">
                <?php if (!$members): ?>
                    <div class="empty-state"><?= e(__('members.empty')) ?></div>
                <?php else: ?>
                    <div class="member-directory-grid">
                        <?php foreach ($members as $member): ?>
                            <?php $status = user_membership_status_meta($member); ?>
                            <article class="member-directory-card">
                                <div class="d-flex align-items-center gap-3">
                                    <a href="<?= user_profile_url((int)$member['id']) ?>" class="text-decoration-none">
                                        <img src="<?= escape_url(user_avatar_url($member)) ?>" alt="" class="member-panel-avatar">
                                    </a>
                                    <div class="min-w-0">
                                        <h2 class="member-directory-title">
                                            <a class="text-decoration-none" href="<?= user_profile_url((int)$member['id']) ?>"><?= e($member['username']) ?></a>
                                        </h2>
                                        <span class="member-status-badge <?= e($status['class']) ?>">
                                            <i class="<?= e($status['icon']) ?>"></i> <?= e($status['label']) ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="member-directory-meta">
                                    <div><?= e($member['role_name'] ?? __('member.none')) ?></div>
                                    <div><?= e(__('profile.stat.registered')) ?>: <?= e(format_dt($member['created_at'])) ?></div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/themes/default/footer.php'; ?>
