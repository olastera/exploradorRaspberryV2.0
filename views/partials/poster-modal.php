<div class="modal fade" id="posterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="posterModalTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-5 text-center">
                        <div id="posterModalLoader" class="poster-modal-loader">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando…</span>
                            </div>
                        </div>
                        <img id="posterModalImage" class="img-fluid d-none" alt="Póster">
                    </div>
                    <div class="col-md-7">
                        <div id="posterModalMeta" class="mb-3">
                            <span id="posterModalRating" class="badge bg-warning text-dark me-1"></span>
                            <span id="posterModalGenre" class="badge bg-secondary me-1"></span>
                            <span id="posterModalRuntime" class="badge bg-info text-dark"></span>
                        </div>
                        <p id="posterModalPlot" class="text-muted"></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a id="posterModalLink" href="#" target="_blank" class="btn btn-sm btn-outline-primary" rel="noopener">
                    <i aria-hidden="true" class="bi bi-box-arrow-up-right me-1"></i>Ver en IMDb
                </a>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
