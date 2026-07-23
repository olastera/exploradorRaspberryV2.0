<?php
$hasAudio = !empty(array_filter($files, fn($f) => in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), $audioExtensions)));
?>

<?php if (empty($directories) && empty($hasAudio)): ?>
    <div class="text-center py-5 text-muted">
        <i aria-hidden="true" class="bi bi-music-note-beamed display-4 d-block mb-2"></i>
        No hay archivos
    </div>
<?php endif; ?>

<?php if (!empty($directories)): ?>
<div class="album-grid" id="albumGrid">
    <?php foreach ($directories as $dir):
        $coverPath = '';
        foreach (['folder.jpg', 'cover.jpg', 'Folder.jpg', 'Cover.jpg'] as $c) {
            $check = $targetPath . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . $c;
            if (file_exists($check)) {
                $relCover = ($relativePath ? $relativePath . '/' : '') . $dir . '/' . $c;
                $coverPath = 'serve.php?lib=music&file=' . urlencode($relCover);
                break;
            }
        }
        $link = '?lib=music&path=' . urlencode(($relativePath ? $relativePath . '/' : '') . $dir);
    ?>
        <div class="album-card explorer-item position-relative" data-name="<?php echo htmlspecialchars($dir, ENT_QUOTES); ?>" data-type="folder">
            <?php if ($isAdmin): ?><input class="form-check-input item-checkbox item-selector" type="checkbox" value="<?php echo htmlspecialchars($dir, ENT_QUOTES); ?>"><?php endif; ?>
            <a href="<?php echo $link; ?>" class="album-card text-decoration-none">
                <?php if ($coverPath): ?>
                    <img src="<?php echo $coverPath; ?>" alt="<?php echo htmlspecialchars($dir); ?>" class="album-cover album-card-cover" style="border-radius:12px 12px 0 0;"><div class="play-overlay"><button class="btn btn-light btn-sm rounded-circle p-2" onclick="event.preventDefault();window.location.href='<?php echo $link; ?>'"><i aria-hidden="true" class="bi bi-play-fill"></i></button></div>
                <?php else: ?>
                    <div class="album-card-cover" style="aspect-ratio:1;display:flex;align-items:center;justify-content:center;background:#0F172A;border-radius:12px 12px 0 0;">
                        <i aria-hidden="true" class="bi bi-music-note-beamed" style="font-size:3rem;color:#475569;"></i>
                    </div><div class="play-overlay"><button class="btn btn-light btn-sm rounded-circle p-2" onclick="event.preventDefault();window.location.href='<?php echo $link; ?>'"><i aria-hidden="true" class="bi bi-play-fill"></i></button></div>
                <?php endif; ?>
                <div class="album-card-body">
                    <p class="card-text small text-truncate w-100 mb-0 text-light"><?php echo htmlspecialchars($dir); ?></p>
                </div>
            </a>
            <?php if ($isAdmin): ?>
            <div class="item-actions p-2 pt-0">
                <button class="btn btn-sm btn-outline-light" onclick='showRenameModal(<?php echo htmlspecialchars(json_encode($dir), ENT_QUOTES); ?>)' title="Canvia el nom"><i class="bi bi-pencil"></i></button>
                <button class="btn btn-sm btn-outline-primary" onclick='moveLibraryItem(<?php echo htmlspecialchars(json_encode($dir), ENT_QUOTES); ?>, "movies")' title="Mou a Pel·lícules"><i class="bi bi-film"></i></button>
                <button class="btn btn-sm btn-outline-info" onclick='moveLibraryItem(<?php echo htmlspecialchars(json_encode($dir), ENT_QUOTES); ?>, "docs")' title="Mou a Documents"><i class="bi bi-file-earmark-text"></i></button>
                <button class="btn btn-sm btn-outline-danger" onclick='deleteLibraryItem(<?php echo htmlspecialchars(json_encode($dir), ENT_QUOTES); ?>)' title="Elimina"><i class="bi bi-trash"></i></button>
            </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($hasAudio): ?>
<div class="table-responsive">
    <table class="table table-hover align-middle mb-0" id="audioTable">
        <thead>
            <tr>
                <?php if ($isAdmin): ?><th style="width:4%"></th><?php endif; ?>
                <th style="width:5%">#</th>
                <th style="width:55%">Título</th>
                <th style="width:15%">Tamaño</th>
                <th style="width:25%">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php $idx = 0; ?>
            <?php foreach ($files as $file):
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (!in_array($ext, $audioExtensions)) continue;
                $idx++;
                $relPath = ($relativePath ? $relativePath . '/' : '') . $file;
                $fileUrl = 'serve.php?lib=music&file=' . urlencode($relPath);
                $title = pathinfo($file, PATHINFO_FILENAME);
                $fsize = filesize($targetPath . DIRECTORY_SEPARATOR . $file);
                $sizeStr = \App\Media\FileExplorer::formatSize($fsize);
            ?>
                <tr class="explorer-item" data-name="<?php echo htmlspecialchars($file, ENT_QUOTES); ?>" data-type="file" data-url="<?php echo htmlspecialchars($fileUrl); ?>" data-title="<?php echo htmlspecialchars($file); ?>">
                    <?php if ($isAdmin): ?><td><input class="form-check-input item-checkbox" type="checkbox" value="<?php echo htmlspecialchars($file, ENT_QUOTES); ?>"></td><?php endif; ?>
                    <td class="text-muted"><?php echo $idx; ?></td>
                    <td>
                        <i aria-hidden="true" class="bi bi-music-note text-primary me-2"></i>
                        <?php echo htmlspecialchars($title); ?>
                    </td>
                    <td class="text-muted small"><?php echo $sizeStr; ?></td>
                    <td>
                        <button onclick="playAudio('<?php echo htmlspecialchars($fileUrl, ENT_QUOTES); ?>', '<?php echo htmlspecialchars($file, ENT_QUOTES); ?>')" class="btn btn-sm px-2" style="background:#3B82F6;color:#fff;border-radius:6px;" title="Reproducir">
                            <i aria-hidden="true" class="bi bi-play-fill"></i>
                        </button>
                        <button onclick='copyToClipboard(<?php echo htmlspecialchars(json_encode($fileUrl), ENT_QUOTES); ?>)' class="btn btn-sm btn-outline-light px-2" style="border-radius:6px;" title="Copiar enlace">
                            <i aria-hidden="true" class="bi bi-clipboard"></i>
                        </button>
                        <?php if ($isAdmin): ?>
                            <button onclick='showRenameModal(<?php echo htmlspecialchars(json_encode($file), ENT_QUOTES); ?>)' class="btn btn-sm btn-outline-light px-2" style="border-radius:6px;" title="Canvia el nom">
                                <i aria-hidden="true" class="bi bi-pencil"></i>
                            </button>
                            <?php if (in_array($ext, $videoExtensions, true)): ?>
                            <button onclick='moveLibraryItem(<?php echo htmlspecialchars(json_encode($file), ENT_QUOTES); ?>, "movies")' class="btn btn-sm btn-outline-primary px-2" title="Mou a Pel·lícules"><i class="bi bi-film"></i></button>
                            <?php endif; ?>
                            <button onclick='moveLibraryItem(<?php echo htmlspecialchars(json_encode($file), ENT_QUOTES); ?>, "docs")' class="btn btn-sm btn-outline-info px-2" title="Mou a Documents"><i class="bi bi-file-earmark-text"></i></button>
                            <button onclick='deleteLibraryItem(<?php echo htmlspecialchars(json_encode($file), ENT_QUOTES); ?>)' class="btn btn-sm btn-outline-danger px-2" style="border-radius:6px;" title="Elimina">
                                <i aria-hidden="true" class="bi bi-trash"></i>
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
$albumArt = '';
foreach (['folder.jpg', 'cover.jpg', 'Folder.jpg', 'Cover.jpg'] as $c) {
    $check = $targetPath . DIRECTORY_SEPARATOR . $c;
    if (file_exists($check)) {
        $relCover = ($relativePath ? $relativePath . '/' : '') . $c;
        $albumArt = 'serve.php?lib=music&file=' . urlencode($relCover);
        break;
    }
}
?>

<div class="modal fade" id="audioPlayerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:#1E293B;border-color:rgba(255,255,255,0.08);">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="audioPlayerTitle">Reproductor de Audio</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <?php if ($albumArt): ?>
                        <img src="<?php echo $albumArt; ?>" class="rounded" loading="lazy" style="width:200px;height:200px;object-fit:cover;box-shadow:0 4px 20px rgba(0,0,0,0.3);">
                    <?php else: ?>
                        <div style="width:200px;height:200px;margin:0 auto;display:flex;align-items:center;justify-content:center;background:#0F172A;border-radius:8px;">
                            <i aria-hidden="true" class="bi bi-music-note-beamed" style="font-size:4rem;color:#475569;"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <p class="text-center mb-3 fw-semibold" id="audioTrackName">Selecciona una canción</p>
                <audio id="audioPlayer" controls autoplay class="w-100"></audio>
                <hr style="border-color:rgba(255,255,255,0.08);">
                <p class="small text-muted mb-2">Lista de reproducción</p>
                <div class="list-group list-group-flush" id="audioPlaylist" style="max-height:250px;overflow-y:auto;">
                    <?php $idx = 0; ?>
                    <?php foreach ($files as $file):
                        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                        if (!in_array($ext, $audioExtensions)) continue;
                        $idx++;
                        $relPath = ($relativePath ? $relativePath . '/' : '') . $file;
                        $fileUrl = 'serve.php?lib=music&file=' . urlencode($relPath);
                    ?>
                        <button class="list-group-item list-group-item-action d-flex align-items-center gap-2" style="background:transparent;border-color:rgba(255,255,255,0.05);color:#F1F5F9;padding:0.5rem 0.75rem;" onclick="playAudio('<?php echo htmlspecialchars($fileUrl, ENT_QUOTES); ?>', '<?php echo htmlspecialchars($file, ENT_QUOTES); ?>')">
                            <span class="text-muted small"><?php echo $idx; ?>.</span>
                            <span class="small text-truncate"><?php echo htmlspecialchars(pathinfo($file, PATHINFO_FILENAME)); ?></span>
                            <i aria-hidden="true" class="bi bi-play-fill ms-auto text-primary"></i>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function playAudio(url, title) {
    var player = document.getElementById('audioPlayer');
    document.getElementById('audioTrackName').textContent = title;
    player.src = url;
    player.play();
    var modal = new bootstrap.Modal(document.getElementById('audioPlayerModal'));
    modal.show();
}

<?php if ($isAdmin): ?>
function deleteAudio(filename) {
    if (!confirm('¿Eliminar ' + filename + '?')) return;
    var form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';
    form.innerHTML = '<input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">';
    form.innerHTML += '<input type="hidden" name="delete" value="' + filename + '">';
    document.body.appendChild(form);
    form.submit();
}
<?php endif; ?>
</script>
<?php endif; ?>
