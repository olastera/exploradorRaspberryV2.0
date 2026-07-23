<div class="modal fade" id="newFolderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i aria-hidden="true" class="bi bi-folder-plus me-2"></i>Nueva carpeta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form method="post" action="index.php?lib=<?php echo $library; ?>&path=<?php echo urlencode($relativePath); ?>">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="create_folder" value="1">
                    <div class="mb-3">
                        <label for="folderNameInput" class="form-label">Nombre de la carpeta</label>
                        <input type="text" class="form-control" name="folder_name" id="folderNameInput" placeholder="Nueva carpeta" required autofocus>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-primary"><i aria-hidden="true" class="bi bi-check-lg me-1"></i>Crear</button>
                </div>
            </form>
        </div>
    </div>
</div>
