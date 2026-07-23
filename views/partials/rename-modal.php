<div class="modal fade" id="renameModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i aria-hidden="true" class="bi bi-pencil me-2"></i>Canvia el nom</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form method="post" action="index.php?lib=<?php echo $library; ?>&path=<?php echo urlencode($relativePath); ?>">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="rename_old" id="renameOldInput" value="">
                    <div class="mb-3">
                        <label for="renameNewInput" class="form-label">Nuevo nombre</label>
                        <input type="text" class="form-control" name="rename_new" id="renameNewInput" required autofocus>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel·la</button>
                    <button type="submit" class="btn btn-sm btn-primary"><i aria-hidden="true" class="bi bi-check-lg me-1"></i>Canvia el nom</button>
                </div>
            </form>
        </div>
    </div>
</div>
