    </main>

    <!-- Modals -->
    <?php include __DIR__ . '/../partials/poster-modal.php'; ?>
    <?php include __DIR__ . '/../partials/rename-modal.php'; ?>
    <?php if (isset($isAdmin) && $isAdmin): ?>
        <?php include __DIR__ . '/../partials/upload-modal.php'; ?>
        <?php include __DIR__ . '/../partials/new-folder-modal.php'; ?>
    <?php endif; ?>

    <script src="assets/vendor/bootstrap/bootstrap.bundle.min.js?v=5.3.0"></script>
    <script src="assets/js/app.js?v=6"></script>
    <script>
    var library = '<?php echo isset($library) ? $library : ''; ?>';
    var csrfToken = '<?php echo isset($csrfToken) ? $csrfToken : ''; ?>';
    var isAdmin = <?php echo (isset($isAdmin) && $isAdmin) ? 'true' : 'false'; ?>;
    </script>
    <?php if (isset($pageScript)): ?>
    <script><?php echo $pageScript; ?></script>
    <?php endif; ?>
</body>
</html>
