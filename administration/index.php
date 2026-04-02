<?php
require_once __DIR__ . '/_guard.php';
include THEMES . 'default/admin_header.php';

$adminCountTableRows = static function (string $table): int {
    try {
        $stmt = $GLOBALS['pdo']->prepare('SHOW TABLES LIKE :table');
        $stmt->execute([':table' => $table]);
        if (!$stmt->fetchColumn()) {
            return 0;
        }

        return (int)$GLOBALS['pdo']->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
};

$dashboardStats = [
    [
        'label' => 'Nariai',
        'value' => $adminCountTableRows('users'),
        'tone' => 'info',
        'icon' => 'fa-solid fa-users',
    ],
    [
        'label' => 'Forumo žinutės',
        'value' => $adminCountTableRows('infusion_forum_posts'),
        'tone' => 'info',
        'icon' => 'fa-solid fa-comments',
    ],
    [
        'label' => 'Šaukyklos žinutės',
        'value' => $adminCountTableRows('infusion_shoutbox_messages'),
        'tone' => 'info',
        'icon' => 'fa-solid fa-comment-dots',
    ],
    [
        'label' => 'Komentarai',
        'value' => $adminCountTableRows('user_profile_comments'),
        'tone' => 'info',
        'icon' => 'fa-solid fa-message',
    ],
    [
        'label' => 'Siuntiniai',
        'value' => $adminCountTableRows('infusion_downloads'),
        'tone' => 'info',
        'icon' => 'fa-solid fa-download',
    ],
    [
        'label' => 'Naujienos',
        'value' => $adminCountTableRows('infusion_news'),
        'tone' => 'info',
        'icon' => 'fa-solid fa-newspaper',
    ],
];
?>
<div class="<?= e(admin_layout_preset_class('dashboard')) ?>">
<?php
admin_render_page_header([
    'variant' => 'dashboard',
    'title' => __('admin.dashboard'),
    'subtitle' => 'Pagrindiniai svetainės aktyvumo skaitikliai vienoje vietoje.',
    'actions' => [
        [
            'label' => __('admin.site'),
            'href' => public_path('index.php'),
            'class' => 'btn btn-outline-secondary admin-action-button',
            'icon' => 'fa-solid fa-globe',
        ],
    ],
]);

admin_render_stat_strip($dashboardStats);
?>

<div class="row g-3 admin-dashboard-grid admin-layout-dashboard-grid">
    <div class="col-lg-4">
        <div class="card admin-dashboard-card h-100">
            <div class="card-header">Branduolys</div>
            <div class="list-group list-group-flush">
                <a class="list-group-item list-group-item-action" href="settings.php"><?= e(__('nav.admin.settings')) ?></a>
                <a class="list-group-item list-group-item-action" href="themes.php"><?= e(__('nav.admin.themes')) ?></a>
                <a class="list-group-item list-group-item-action" href="navigation.php"><?= e(__('nav.admin.navigation')) ?></a>
                <a class="list-group-item list-group-item-action" href="diagnostics.php"><?= e(__('nav.admin.diagnostics')) ?></a>
                <a class="list-group-item list-group-item-action" href="smileys.php">&#352;ypsen&#279;l&#279;s</a>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card admin-dashboard-card h-100">
            <div class="card-header">Moduliai</div>
            <div class="list-group list-group-flush">
                <a class="list-group-item list-group-item-action" href="infusions.php"><?= e(__('nav.admin.infusions')) ?></a>
                <a class="list-group-item list-group-item-action" href="panels.php">Paneli&#371; i&#353;d&#279;stymas</a>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card admin-dashboard-card h-100">
            <div class="card-header">Administravimas</div>
            <div class="list-group list-group-flush">
                <a class="list-group-item list-group-item-action" href="roles.php"><?= e(__('nav.admin.roles')) ?></a>
                <a class="list-group-item list-group-item-action" href="permissions.php"><?= e(__('nav.admin.permissions')) ?></a>
                <a class="list-group-item list-group-item-action" href="users.php"><?= e(__('nav.admin.members')) ?></a>
                <a class="list-group-item list-group-item-action" href="audit-logs.php"><?= e(__('admin.audit')) ?></a>
                <a class="list-group-item list-group-item-action" href="security-logs.php">Saugumo &#382;urnalas</a>
                <a class="list-group-item list-group-item-action" href="moderation-logs.php">Moderavimo &#382;urnalas</a>
                <a class="list-group-item list-group-item-action" href="error-logs.php"><?= e(__('admin.error_log')) ?></a>
            </div>
        </div>
    </div>
</div>
</div>
<?php include THEMES . 'default/admin_footer.php'; ?>
