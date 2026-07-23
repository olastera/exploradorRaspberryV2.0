<div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i aria-hidden="true" class="bi bi-upload me-2"></i>Puja fitxers</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form method="post" action="index.php?lib=<?php echo $library; ?>&path=<?php echo urlencode($relativePath); ?>" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <div class="mb-3">
                        <label for="uploadFiles" class="form-label">Selecciona fitxers</label>
                        <input type="file" class="form-control" name="upload_files[]" id="uploadFiles" multiple required>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" name="upload_as_zip" id="uploadAsZip" value="1">
                        <label class="form-check-label" for="uploadAsZip">Descomprimir ZIP automáticamente</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel·la</button>
                    <button type="submit" class="btn btn-sm btn-primary"><i aria-hidden="true" class="bi bi-cloud-arrow-up me-1"></i>Puja</button>
                </div>
            </form>
        </div>
    </div>
</div>
