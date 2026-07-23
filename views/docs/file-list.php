<div class="card-custom p-0 overflow-hidden"><div class="table-responsive mb-0">
    <table class="table docs-table table-hover align-middle" id="docsTable">
        <thead>
            <tr>
                <?php if ($isAdmin): ?><th style="width:3%"></th><?php endif; ?>
                <th style="width:40%" class="sortable" data-col="name" onclick="sortDocsTable('name')">Nombre <i aria-hidden="true" class="bi bi-arrow-down-up sort-icon ms-1 text-muted"></i></th>
                <th style="width:15%" class="sortable" data-col="size" onclick="sortDocsTable('size')">Tamaño <i aria-hidden="true" class="bi bi-arrow-down-up sort-icon ms-1 text-muted"></i></th>
                <th style="width:15%" class="sortable" data-col="type" onclick="sortDocsTable('type')">Tipo <i aria-hidden="true" class="bi bi-arrow-down-up sort-icon ms-1 text-muted"></i></th>
                <th style="width:15%" class="sortable" data-col="date" onclick="sortDocsTable('date')">Fecha <i aria-hidden="true" class="bi bi-arrow-down-up sort-icon ms-1 text-muted"></i></th>
                <th style="width:15%" class="text-end">Acciones</th>
            </tr>
        </thead>
        <tbody id="docsTbody">
            <?php foreach ($directories as $dir):
                $link = '?lib=docs&path=' . urlencode(($relativePath ? $relativePath . '/' : '') . $dir);
                $mtime = filemtime($targetPath . DIRECTORY_SEPARATOR . $dir);
            ?>
                <tr class="explorer-item" data-type="folder" data-name="<?php echo htmlspecialchars($dir, ENT_QUOTES); ?>" data-size="0" data-date="<?php echo $mtime; ?>" style="cursor:pointer;" onclick="window.location.href='<?php echo $link; ?>'" role="link" tabindex="0" onkeydown="if(event.key==='Enter'||event.key===' ')window.location.href='<?php echo $link; ?>'">
                    <?php if ($isAdmin): ?><td onclick="event.stopPropagation()"><input class="form-check-input item-checkbox" type="checkbox" value="<?php echo htmlspecialchars($dir, ENT_QUOTES); ?>"></td><?php endif; ?>
                    <td>
                        <i aria-hidden="true" class="bi bi-folder-fill me-2" style="color:#fbbf24;"></i>
                        <?php echo htmlspecialchars($dir); ?>
                    </td>
                    <td class="text-muted small">-</td>
                    <td class="text-muted small">Carpeta</td>
                    <td class="text-muted small"><?php echo date('d/m/Y H:i', $mtime); ?></td>
                    <td class="text-end text-nowrap">
                        <a href="<?php echo $link; ?>" class="btn btn-sm btn-outline-light px-2" style="border-radius:6px;" title="Abrir"><i aria-hidden="true" class="bi bi-folder2-open"></i></a>
                        <?php if ($isAdmin): ?>
                            <button onclick='event.stopPropagation();showRenameModal(<?php echo htmlspecialchars(json_encode($dir), ENT_QUOTES); ?>)' class="btn btn-sm btn-outline-light px-2" style="border-radius:6px;" title="Canvia el nom"><i aria-hidden="true" class="bi bi-pencil"></i></button>
                            <button onclick='event.stopPropagation();moveLibraryItem(<?php echo htmlspecialchars(json_encode($dir), ENT_QUOTES); ?>, "movies")' class="btn btn-sm btn-outline-primary px-2" title="Mou a Pel·lícules"><i class="bi bi-film"></i></button>
                            <button onclick='event.stopPropagation();moveLibraryItem(<?php echo htmlspecialchars(json_encode($dir), ENT_QUOTES); ?>, "music")' class="btn btn-sm btn-outline-success px-2" title="Mou a Música"><i class="bi bi-music-note"></i></button>
                            <button onclick='event.stopPropagation();deleteLibraryItem(<?php echo htmlspecialchars(json_encode($dir), ENT_QUOTES); ?>)' class="btn btn-sm btn-outline-danger px-2" style="border-radius:6px;" title="Elimina"><i aria-hidden="true" class="bi bi-trash"></i></button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php foreach ($files as $file):
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                $relPath = ($relativePath ? $relativePath . '/' : '') . $file;
                $fileUrl = 'serve.php?lib=docs&file=' . urlencode($relPath);
                $fsize = filesize($targetPath . DIRECTORY_SEPARATOR . $file);
                $sizeStr = \App\Media\FileExplorer::formatSize($fsize);
                $mtime = filemtime($targetPath . DIRECTORY_SEPARATOR . $file);

                $icon = 'bi-file-earmark';
                $typeLabel = 'Archivo';
                $imageExts = ['jpg','jpeg','png','gif','webp','bmp','svg'];
                $textExts = ['txt','md','log','csv','ini','cfg','json','xml','yaml','yml'];
                if (in_array($ext, ['pdf'])) { $icon = 'bi-filetype-pdf'; $typeLabel = 'PDF'; }
                elseif (in_array($ext, $imageExts)) { $icon = 'bi-filetype-jpg'; $typeLabel = 'Imagen'; }
                elseif (in_array($ext, $textExts)) { $icon = 'bi-filetype-txt'; $typeLabel = 'Texto'; }
                elseif (in_array($ext, ['doc','docx'])) { $icon = 'bi-filetype-docx'; $typeLabel = 'Documento'; }
                elseif (in_array($ext, ['xls','xlsx'])) { $icon = 'bi-filetype-xlsx'; $typeLabel = 'Hoja de cálculo'; }
                elseif (in_array($ext, ['zip','rar','7z','tar','gz'])) { $icon = 'bi-file-earmark-zip'; $typeLabel = 'Comprimido'; }

                $canPreview = in_array($ext, array_merge(['pdf','txt','md','log','csv','ini','cfg','json','xml','yaml','yml'], $imageExts));
                $isImage = in_array($ext, $imageExts);
                $isText = in_array($ext, $textExts);
            ?>
                <tr class="explorer-item" data-type="file" data-name="<?php echo htmlspecialchars($file, ENT_QUOTES); ?>" data-size="<?php echo $fsize; ?>" data-date="<?php echo $mtime; ?>">
                    <?php if ($isAdmin): ?><td><input class="form-check-input item-checkbox" type="checkbox" value="<?php echo htmlspecialchars($file, ENT_QUOTES); ?>"></td><?php endif; ?>
                    <td>
                        <i aria-hidden="true" class="bi <?php echo $icon; ?> me-2 text-primary"></i>
                        <?php echo htmlspecialchars($file); ?>
                    </td>
                    <td class="text-muted small"><?php echo $sizeStr; ?></td>
                    <td class="text-muted small"><?php echo $typeLabel; ?></td>
                    <td class="text-muted small"><?php echo date('d/m/Y H:i', $mtime); ?></td>
                    <td class="text-end text-nowrap">
                        <a href="<?php echo $fileUrl; ?>" download class="btn btn-sm btn-outline-light px-2" style="border-radius:6px;" title="Descargar"><i aria-hidden="true" class="bi bi-download"></i></a>
                        <button onclick='copyToClipboard(<?php echo htmlspecialchars(json_encode($fileUrl), ENT_QUOTES); ?>)' class="btn btn-sm btn-outline-light px-2" title="Copia URL"><i class="bi bi-clipboard"></i></button>
                        <?php if ($canPreview): ?>
                            <?php if ($isImage || $ext === 'pdf'): ?>
                                <a href="<?php echo $fileUrl; ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary px-2" style="border-radius:6px;" title="Vista previa"><i aria-hidden="true" class="bi bi-eye"></i></a>
                            <?php else: ?>
                                <button onclick="previewDoc('<?php echo htmlspecialchars($fileUrl, ENT_QUOTES); ?>', '<?php echo htmlspecialchars($file, ENT_QUOTES); ?>')" class="btn btn-sm btn-outline-primary px-2" style="border-radius:6px;" title="Vista previa"><i aria-hidden="true" class="bi bi-eye"></i></button>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if ($isAdmin): ?>
                            <button onclick='showRenameModal(<?php echo htmlspecialchars(json_encode($file), ENT_QUOTES); ?>)' class="btn btn-sm btn-outline-light px-2" style="border-radius:6px;" title="Canvia el nom"><i aria-hidden="true" class="bi bi-pencil"></i></button>
                            <button onclick='moveLibraryItem(<?php echo htmlspecialchars(json_encode($file), ENT_QUOTES); ?>, "movies")' class="btn btn-sm btn-outline-primary px-2" title="Mou a Pel·lícules"><i class="bi bi-film"></i></button>
                            <button onclick='moveLibraryItem(<?php echo htmlspecialchars(json_encode($file), ENT_QUOTES); ?>, "music")' class="btn btn-sm btn-outline-success px-2" title="Mou a Música"><i class="bi bi-music-note"></i></button>
                            <button onclick='deleteLibraryItem(<?php echo htmlspecialchars(json_encode($file), ENT_QUOTES); ?>)' class="btn btn-sm btn-outline-danger px-2" style="border-radius:6px;" title="Elimina"><i aria-hidden="true" class="bi bi-trash"></i></button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if (empty($directories) && empty($files)): ?>
        <div class="text-center py-5 text-muted">
            <i aria-hidden="true" class="bi bi-file-earmark-x display-4 d-block mb-2"></i>
            No hay archivos
        </div>
    <?php endif; ?>
</div></div>

<?php if (!empty($files) && !empty(array_filter($files, fn($f) => in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), ['txt','md','log','csv','ini','cfg','json','xml','yaml','yml'])))): ?>
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="background:#1E293B;border-color:rgba(255,255,255,0.08);">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="previewModalTitle">Vista previa</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <pre id="previewContent" style="background:#0F172A;color:#F1F5F9;padding:1rem;border-radius:8px;max-height:65vh;overflow:auto;white-space:pre-wrap;word-break:break-word;margin:0;"></pre>
            </div>
        </div>
    </div>
</div>

<script>
function previewDoc(url, title) {
    document.getElementById('previewModalTitle').textContent = title;
    document.getElementById('previewContent').textContent = 'Cargando…';
    fetch(url)
        .then(function(r) { return r.text(); })
        .then(function(text) {
            document.getElementById('previewContent').textContent = text;
        })
        .catch(function() {
            document.getElementById('previewContent').textContent = 'Error al cargar el archivo.';
        });
    new bootstrap.Modal(document.getElementById('previewModal')).show();
}
</script>
<?php endif; ?>

<script>
var docsSortDir = {};
function sortDocsTable(col) {
    var tbody = document.getElementById('docsTbody');
    var rows = Array.from(tbody.querySelectorAll('tr'));
    var dir = docsSortDir[col] === 'asc' ? 'desc' : 'asc';
    docsSortDir[col] = dir;

    rows.sort(function(a, b) {
        var va, vb;
        if (col === 'name') {
            va = a.getAttribute('data-name') || '';
            vb = b.getAttribute('data-name') || '';
            return dir === 'asc' ? va.localeCompare(vb) : vb.localeCompare(va);
        } else if (col === 'size') {
            va = parseInt(a.getAttribute('data-size')) || 0;
            vb = parseInt(b.getAttribute('data-size')) || 0;
            return dir === 'asc' ? va - vb : vb - va;
        } else if (col === 'type') {
            va = a.getAttribute('data-type') || '';
            vb = b.getAttribute('data-type') || '';
            return dir === 'asc' ? va.localeCompare(vb) : vb.localeCompare(va);
        } else if (col === 'date') {
            va = parseInt(a.getAttribute('data-date')) || 0;
            vb = parseInt(b.getAttribute('data-date')) || 0;
            return dir === 'asc' ? va - vb : vb - va;
        }
    });

    rows.forEach(function(r) { tbody.appendChild(r); });

    document.querySelectorAll('#docsTable .sort-icon').forEach(function(icon) {
        icon.className = 'bi bi-arrow-down-up sort-icon ms-1 text-muted';
    });
    var icon = document.querySelector('#docsTable th[data-col="' + col + '"] .sort-icon');
    if (icon) {
        icon.className = 'bi bi-arrow-' + (dir === 'asc' ? 'down' : 'up') + ' sort-icon ms-1 text-primary';
    }
}

<?php if ($isAdmin): ?>
function deleteDoc(filename) {
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
