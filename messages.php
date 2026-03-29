<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

include __DIR__ . '/themes/default/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><?= e(__('messages.title')) ?></div>
            <div class="card-body">
                <div class="empty-state"><?= e(__('messages.placeholder')) ?></div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/themes/default/footer.php'; ?>
