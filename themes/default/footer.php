</div>
<footer class="border-top mt-5 py-4">
<div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
<?= showcopyright() ?>
<div class="d-flex align-items-center gap-2">
<?= showcounter() ?>
<?= showMemoryUsage() ?>
<span class="text-secondary small"><?= date('Y-m-d H:i') ?></span>
</div>
</div>
</footer>
<script src="<?= asset_path('includes/jquery/jquery-3.7.1.min.js') ?>"></script>
<script src="<?= asset_path('includes/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= asset_path('includes/js/Sortable.min.js') ?>"></script>
<script src="<?= asset_path('includes/js/app.js') ?>"></script>
</body></html>
