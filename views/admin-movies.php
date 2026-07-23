<div class="toolbar">
    <div class="d-flex align-items-center gap-2">
        <span class="text-muted small"><?php echo count($directories) + count($files); ?> elemento(s)</span>
    </div>
</div>

<?php if (empty($directories) && empty($files)): ?>
    <div class="text-center py-5 text-muted">
        <i aria-hidden="true" class="bi bi-folder2-open" style="font-size:3rem;display:block;margin-bottom:1rem"></i>
        Carpeta buida
    </div>
<?php else: ?>
    <!-- Multi-select bar -->
    <div class="selection-bar" id="selectionBar">
        <span id="selectedCount" class="text-muted small">0 seleccionados</span>
        <button class="btn btn-sm btn-outline-danger" onclick="deleteSelected()"><i aria-hidden="true" class="bi bi-trash"></i> Elimina</button>
        <button class="btn btn-sm btn-outline-info" onclick="extractSelected()"><i aria-hidden="true" class="bi bi-box-arrow-up-right"></i> Extraer</button>
        <button class="btn btn-sm btn-outline-success" onclick="moveSelected('musica')"><i aria-hidden="true" class="bi bi-music-note-beamed"></i> Música</button>
        <button class="btn btn-sm btn-outline-primary" onclick="moveSelected('documentos')"><i aria-hidden="true" class="bi bi-file-earmark-text"></i> Documents</button>
    </div>

    <div class="movie-grid">
        <?php foreach ($directories as $dir): ?>
        <div class="movie-card">
            <a href="?lib=movies&path=<?php echo urlencode($relativePath ? $relativePath . '/' . $dir : $dir); ?>" class="text-decoration-none">
                <div class="movie-card-folder">
                    <i aria-hidden="true" class="bi bi-folder text-warning"></i>
                    <div class="title" style="color:var(--text)"><?php echo htmlspecialchars($dir); ?></div>
                    <div class="subtitle">Carpeta</div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>

        <?php foreach ($files as $file):
            $fileUrl = 'serve.php?file=' . urlencode(($relativePath ? $relativePath . '/' : '') . $file);
            $isVideo = preg_match('/\.(mp4|mkv|avi|mov|webm|ogg|wmv|flv|m4v|ts|vob)$/i', $file);
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $cleanName = pathinfo($file, PATHINFO_FILENAME);
        ?>
        <div class="movie-card">
            <div class="movie-card-poster">
                <img class="poster-thumb shimmer" data-query="<?php echo htmlspecialchars($cleanName); ?>" alt="<?php echo htmlspecialchars($file); ?>" width="300" height="450" loading="lazy">
                <div class="movie-card-overlay">
                    <?php if ($isVideo): ?>
                    <button class="btn btn-light" onclick="playVideo('<?php echo $fileUrl; ?>', '<?php echo htmlspecialchars($file); ?>')" title="Reproducir">
                        <i aria-hidden="true" class="bi bi-play-fill"></i>
                    </button>
                    <?php endif; ?>
                    <button class="btn btn-light" onclick="copyToClipboard('<?php echo $fileUrl; ?>')" title="Copiar URL">
                        <i aria-hidden="true" class="bi bi-link"></i>
                    </button>
                    <button class="btn btn-light" onclick="searchTrailer('<?php echo htmlspecialchars($cleanName); ?>')" title="Trailer">
                        <i aria-hidden="true" class="bi bi-youtube" style="color:#dc2626"></i>
                    </button>
                    <button class="btn btn-light" onclick="showRenameModal('<?php echo htmlspecialchars($file); ?>')" title="Renombrar">
                        <i aria-hidden="true" class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-light" onclick="if(confirm('Eliminar <?php echo htmlspecialchars(addslashes($file)); ?>?')){var f=document.createElement('form');f.method='POST';f.innerHTML='<input name=action value=delete><input name=file value=\"<?php echo htmlspecialchars($file, ENT_QUOTES); ?>\"><input name=_token value=\"'+csrfToken+'\">';document.body.appendChild(f);f.submit();}" title="Eliminar">
                        <i aria-hidden="true" class="bi bi-trash" style="color:#dc2626"></i>
                    </button>
                    <?php if ($isVideo): ?>
                    <button class="btn btn-light" onclick="convertToMp4('<?php echo htmlspecialchars($file); ?>')" title="Convertir">
                        <i aria-hidden="true" class="bi bi-arrow-repeat"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="movie-card-body">
                <div class="title"><?php echo htmlspecialchars($file); ?></div>
                <div class="subtitle"><?php echo $isVideo ? 'Vídeo' : strtoupper($ext); ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
