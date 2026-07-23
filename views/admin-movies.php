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
    <div class="movie-grid">
        <?php foreach ($directories as $dir):
            $cleanName = preg_replace('/\[.*?\]/', ' ', $dir);
            $cleanName = preg_replace('/\((?:19|20)\d{2}\)/', ' ', $cleanName);
            $cleanName = trim(preg_replace('/\s+/', ' ', $cleanName));
        ?>
        <div class="movie-card explorer-item position-relative" data-name="<?php echo htmlspecialchars($dir, ENT_QUOTES); ?>" data-type="folder">
            <input class="form-check-input item-checkbox item-selector" type="checkbox" value="<?php echo htmlspecialchars($dir, ENT_QUOTES); ?>" aria-label="Selecciona <?php echo htmlspecialchars($dir, ENT_QUOTES); ?>">
            <a href="?lib=movies&path=<?php echo urlencode($relativePath ? $relativePath . '/' . $dir : $dir); ?>" class="text-decoration-none">
                <div class="movie-card-poster">
                    <div class="poster-fallback" aria-hidden="true">
                        <i class="bi bi-film"></i>
                    </div>
                    <img class="poster-thumb shimmer" data-query="<?php echo htmlspecialchars($cleanName, ENT_QUOTES); ?>" alt="Portada de <?php echo htmlspecialchars($dir, ENT_QUOTES); ?>" width="300" height="450" loading="lazy">
                </div>
            </a>
            <div class="movie-card-body">
                <div class="title" style="color:var(--text)"><?php echo htmlspecialchars($dir); ?></div>
                <div class="subtitle mb-2">Carpeta</div>
                <div class="item-actions">
                    <button class="btn btn-sm btn-outline-info" onclick='showMovieInfo(<?php echo htmlspecialchars(json_encode($cleanName), ENT_QUOTES); ?>)' title="Informació"><i class="bi bi-info-circle"></i></button>
                    <button class="btn btn-sm btn-outline-light" onclick='showRenameModal(<?php echo htmlspecialchars(json_encode($dir), ENT_QUOTES); ?>)' title="Canvia el nom"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-outline-success" onclick='moveLibraryItem(<?php echo htmlspecialchars(json_encode($dir), ENT_QUOTES); ?>, "music")' title="Mou a Música"><i class="bi bi-music-note"></i></button>
                    <button class="btn btn-sm btn-outline-primary" onclick='moveLibraryItem(<?php echo htmlspecialchars(json_encode($dir), ENT_QUOTES); ?>, "docs")' title="Mou a Documents"><i class="bi bi-file-earmark-text"></i></button>
                    <button class="btn btn-sm btn-outline-danger" onclick='deleteLibraryItem(<?php echo htmlspecialchars(json_encode($dir), ENT_QUOTES); ?>)' title="Elimina"><i class="bi bi-trash"></i></button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <?php foreach ($files as $file):
            $fileUrl = 'serve.php?file=' . urlencode(($relativePath ? $relativePath . '/' : '') . $file);
            $isVideo = preg_match('/\.(mp4|mkv|avi|mov|webm|ogg|wmv|flv|m4v|ts|vob)$/i', $file);
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $cleanName = pathinfo($file, PATHINFO_FILENAME);
        ?>
        <div class="movie-card explorer-item position-relative" data-name="<?php echo htmlspecialchars($file, ENT_QUOTES); ?>" data-type="file">
            <input class="form-check-input item-checkbox item-selector" type="checkbox" value="<?php echo htmlspecialchars($file, ENT_QUOTES); ?>" aria-label="Selecciona <?php echo htmlspecialchars($file, ENT_QUOTES); ?>">
            <div class="movie-card-poster">
                <img class="poster-thumb shimmer" data-query="<?php echo htmlspecialchars($cleanName); ?>" alt="<?php echo htmlspecialchars($file); ?>" width="300" height="450" loading="lazy">
                <div class="movie-card-overlay">
                    <button class="btn btn-light" onclick='showMovieInfo(<?php echo htmlspecialchars(json_encode($cleanName), ENT_QUOTES); ?>)' title="Informació">
                        <i aria-hidden="true" class="bi bi-info-circle" style="color:#0284c7"></i>
                    </button>
                    <?php if ($isVideo): ?>
                    <button class="btn btn-light" onclick='playVideo(<?php echo htmlspecialchars(json_encode($fileUrl), ENT_QUOTES); ?>, <?php echo htmlspecialchars(json_encode($file), ENT_QUOTES); ?>)' title="Reprodueix">
                        <i aria-hidden="true" class="bi bi-play-fill"></i>
                    </button>
                    <?php endif; ?>
                    <button class="btn btn-light" onclick='copyToClipboard(<?php echo htmlspecialchars(json_encode($fileUrl), ENT_QUOTES); ?>)' title="Copia URL">
                        <i aria-hidden="true" class="bi bi-link"></i>
                    </button>
                    <button class="btn btn-light" onclick="searchTrailer('<?php echo htmlspecialchars($cleanName); ?>')" title="Trailer">
                        <i aria-hidden="true" class="bi bi-youtube" style="color:#dc2626"></i>
                    </button>
                    <button class="btn btn-light" onclick='showRenameModal(<?php echo htmlspecialchars(json_encode($file), ENT_QUOTES); ?>)' title="Canvia el nom">
                        <i aria-hidden="true" class="bi bi-pencil"></i>
                    </button>
                    <?php if (in_array($ext, $audioExtensions, true)): ?>
                    <button class="btn btn-light" onclick='moveLibraryItem(<?php echo htmlspecialchars(json_encode($file), ENT_QUOTES); ?>, "music")' title="Mou a Música">
                        <i aria-hidden="true" class="bi bi-music-note"></i>
                    </button>
                    <?php endif; ?>
                    <button class="btn btn-light" onclick='moveLibraryItem(<?php echo htmlspecialchars(json_encode($file), ENT_QUOTES); ?>, "docs")' title="Mou a Documents">
                        <i aria-hidden="true" class="bi bi-file-earmark-text"></i>
                    </button>
                    <button class="btn btn-light" onclick='deleteLibraryItem(<?php echo htmlspecialchars(json_encode($file), ENT_QUOTES); ?>)' title="Elimina">
                        <i aria-hidden="true" class="bi bi-trash" style="color:#dc2626"></i>
                    </button>
                    <?php if ($isVideo && $ext !== 'mp4' && substr(pathinfo($file, PATHINFO_FILENAME), -4) !== '_web'): ?>
                    <button class="btn btn-light" onclick='convertToMp4(<?php echo htmlspecialchars(json_encode(($relativePath ? $relativePath . '/' : '') . $file), ENT_QUOTES); ?>)' title="Converteix a MP4">
                        <i aria-hidden="true" class="bi bi-arrow-repeat"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="movie-card-body">
                <div class="title"><?php echo htmlspecialchars($file); ?></div>
                <div class="subtitle"><?php echo $isVideo ? 'Vídeo' : strtoupper($ext); ?></div>
                <div class="item-actions list-item-actions mt-2">
                    <button class="btn btn-sm btn-outline-info" onclick='showMovieInfo(<?php echo htmlspecialchars(json_encode($cleanName), ENT_QUOTES); ?>)' title="Informació"><i class="bi bi-info-circle"></i></button>
                    <?php if ($isVideo): ?>
                    <button class="btn btn-sm btn-outline-success" onclick='playVideo(<?php echo htmlspecialchars(json_encode($fileUrl), ENT_QUOTES); ?>, <?php echo htmlspecialchars(json_encode($file), ENT_QUOTES); ?>)' title="Reprodueix"><i class="bi bi-play-fill"></i></button>
                    <?php endif; ?>
                    <button class="btn btn-sm btn-outline-light" onclick='copyToClipboard(<?php echo htmlspecialchars(json_encode($fileUrl), ENT_QUOTES); ?>)' title="Copia URL"><i class="bi bi-link"></i></button>
                    <?php if ($isVideo): ?>
                    <button class="btn btn-sm btn-outline-danger" onclick='searchTrailer(<?php echo htmlspecialchars(json_encode($cleanName), ENT_QUOTES); ?>)' title="Tràiler"><i class="bi bi-youtube"></i></button>
                    <?php endif; ?>
                    <button class="btn btn-sm btn-outline-light" onclick='showRenameModal(<?php echo htmlspecialchars(json_encode($file), ENT_QUOTES); ?>)' title="Canvia el nom"><i class="bi bi-pencil"></i></button>
                    <?php if (in_array($ext, $audioExtensions, true)): ?>
                    <button class="btn btn-sm btn-outline-success" onclick='moveLibraryItem(<?php echo htmlspecialchars(json_encode($file), ENT_QUOTES); ?>, "music")' title="Mou a Música"><i class="bi bi-music-note"></i></button>
                    <?php endif; ?>
                    <button class="btn btn-sm btn-outline-primary" onclick='moveLibraryItem(<?php echo htmlspecialchars(json_encode($file), ENT_QUOTES); ?>, "docs")' title="Mou a Documents"><i class="bi bi-file-earmark-text"></i></button>
                    <?php if ($isVideo && $ext !== 'mp4' && substr(pathinfo($file, PATHINFO_FILENAME), -4) !== '_web'): ?>
                    <button class="btn btn-sm btn-outline-warning" onclick='convertToMp4(<?php echo htmlspecialchars(json_encode(($relativePath ? $relativePath . '/' : '') . $file), ENT_QUOTES); ?>)' title="Converteix a MP4"><i class="bi bi-arrow-repeat"></i></button>
                    <?php endif; ?>
                    <button class="btn btn-sm btn-outline-danger" onclick='deleteLibraryItem(<?php echo htmlspecialchars(json_encode($file), ENT_QUOTES); ?>)' title="Elimina"><i class="bi bi-trash"></i></button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
