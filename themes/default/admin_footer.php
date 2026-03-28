</div>

<footer class="text-center mt-5 mb-4 text-secondary small">
Mini CMS administracija · v<?= e(app_version()) ?> · PHP <?= e(PHP_VERSION) ?> · OPcache <?= is_opcache_enabled() ? 'Įjungta' : 'Išjungta' ?>
</footer>

<script src="<?= asset_path('includes/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= asset_path('includes/js/Sortable.min.js') ?>"></script>
<script src="<?= asset_path('includes/js/app.js') ?>"></script>
<?php foreach (get_registered_page_scripts() as $scriptPath): ?>
<script src="<?= asset_path($scriptPath) ?>"></script>
<?php endforeach; ?>

</body>
</html>
