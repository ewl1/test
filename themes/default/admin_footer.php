</div>

<footer class="text-center mt-5 mb-4 text-secondary small">
Mini CMS Admin · v<?= e(app_version()) ?> · PHP <?= e(PHP_VERSION) ?> · OPcache <?= is_opcache_enabled() ? 'On' : 'Off' ?>
</footer>

<script src="<?= asset_path('includes/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= asset_path('includes/js/Sortable.min.js') ?>"></script>
<script src="<?= asset_path('includes/js/app.js') ?>"></script>

</body>
</html>
