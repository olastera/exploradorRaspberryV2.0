<?php
$imageFiles = array_values(array_filter($files, function ($f) use ($imageExtensions) {
    return in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), $imageExtensions, true);
}));
?>

<?php if (empty($directories) && empty($imageFiles)): ?>
    <div class="text-center py-5 text-muted">
        <i aria-hidden="true" class="bi bi-images display-4 d-block mb-2"></i>
        No hay archivos
    </div>
<?php endif; ?>

<?php if (!empty($directories) || !empty($imageFiles)): ?>
<div class="movie-grid">
    <?php foreach ($directories as $dir):
        $coverUrl = '';
        $dirFull = $targetPath . DIRECTORY_SEPARATOR . $dir;
        $innerItems = @scandir($dirFull) ?: [];
        sort($innerItems);
        foreach ($innerItems as $innerItem) {
            if ($innerItem[0] === '.') continue;
            $innerExt = strtolower(pathinfo($innerItem, PATHINFO_EXTENSION));
            if (in_array($innerExt, $imageExtensions, true) && is_file($dirFull . DIRECTORY_SEPARATOR . $innerItem)) {
                $relCover = ($relativePath ? $relativePath . '/' : '') . $dir . '/' . $innerItem;
                $coverUrl = 'thumb.php?file=' . urlencode($relCover);
                break;
            }
        }
        $link = '?lib=images&path=' . urlencode(($relativePath ? $relativePath . '/' : '') . $dir);
    ?>
        <div class="movie-card explorer-item position-relative" data-name="<?php echo htmlspecialchars($dir, ENT_QUOTES); ?>" data-type="folder">
            <?php if ($isAdmin): ?><input class="form-check-input item-checkbox item-selector" type="checkbox" value="<?php echo htmlspecialchars($dir, ENT_QUOTES); ?>" aria-label="Selecciona <?php echo htmlspecialchars($dir, ENT_QUOTES); ?>"><?php endif; ?>
            <a href="<?php echo $link; ?>" class="text-decoration-none">
                <div class="image-card-thumb">
                    <?php if ($coverUrl): ?>
                        <img src="<?php echo htmlspecialchars($coverUrl); ?>" alt="<?php echo htmlspecialchars($dir, ENT_QUOTES); ?>" loading="lazy">
                    <?php else: ?>
                        <div class="poster-fallback"><i aria-hidden="true" class="bi bi-folder-fill"></i></div>
                    <?php endif; ?>
                </div>
            </a>
            <div class="movie-card-body">
                <div class="title" style="color:var(--text)"><?php echo htmlspecialchars($dir); ?></div>
                <div class="subtitle mb-2">Carpeta</div>
                <?php if ($isAdmin): ?>
                <div class="item-actions">
                    <button class="btn btn-sm btn-outline-light" onclick='showRenameModal(<?php echo htmlspecialchars(json_encode($dir), ENT_QUOTES); ?>)' title="Canvia el nom"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-outline-success" onclick='moveLibraryItem(<?php echo htmlspecialchars(json_encode($dir), ENT_QUOTES); ?>, "music")' title="Mou a Música"><i class="bi bi-music-note"></i></button>
                    <button class="btn btn-sm btn-outline-primary" onclick='moveLibraryItem(<?php echo htmlspecialchars(json_encode($dir), ENT_QUOTES); ?>, "docs")' title="Mou a Documents"><i class="bi bi-file-earmark-text"></i></button>
                    <button class="btn btn-sm btn-outline-danger" onclick='deleteLibraryItem(<?php echo htmlspecialchars(json_encode($dir), ENT_QUOTES); ?>)' title="Elimina"><i class="bi bi-trash"></i></button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <?php foreach ($imageFiles as $file):
        $relPath = ($relativePath ? $relativePath . '/' : '') . $file;
        $thumbUrl = 'thumb.php?file=' . urlencode($relPath);
        $fileUrl = 'serve.php?lib=images&file=' . urlencode($relPath);
    ?>
        <div class="movie-card explorer-item position-relative" data-name="<?php echo htmlspecialchars($file, ENT_QUOTES); ?>" data-type="file">
            <?php if ($isAdmin): ?><input class="form-check-input item-checkbox item-selector" type="checkbox" value="<?php echo htmlspecialchars($file, ENT_QUOTES); ?>" aria-label="Selecciona <?php echo htmlspecialchars($file, ENT_QUOTES); ?>"><?php endif; ?>
            <div class="image-card-thumb" style="cursor:pointer;" onclick='openImageLightbox(<?php echo htmlspecialchars(json_encode($fileUrl), ENT_QUOTES); ?>, <?php echo htmlspecialchars(json_encode($file), ENT_QUOTES); ?>)'>
                <img src="<?php echo htmlspecialchars($thumbUrl); ?>" alt="<?php echo htmlspecialchars($file, ENT_QUOTES); ?>" loading="lazy">
            </div>
            <div class="movie-card-body">
                <div class="title"><?php echo htmlspecialchars($file); ?></div>
                <div class="item-actions mt-2">
                    <button class="btn btn-sm btn-outline-info" onclick='openImageLightbox(<?php echo htmlspecialchars(json_encode($fileUrl), ENT_QUOTES); ?>, <?php echo htmlspecialchars(json_encode($file), ENT_QUOTES); ?>)' title="Veure"><i class="bi bi-eye"></i></button>
                    <button class="btn btn-sm btn-outline-light" onclick='copyToClipboard(<?php echo htmlspecialchars(json_encode($fileUrl), ENT_QUOTES); ?>)' title="Copia URL"><i class="bi bi-link"></i></button>
                    <?php if ($isAdmin): ?>
                    <button class="btn btn-sm btn-outline-light" onclick='showRenameModal(<?php echo htmlspecialchars(json_encode($file), ENT_QUOTES); ?>)' title="Canvia el nom"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-outline-success" onclick='moveLibraryItem(<?php echo htmlspecialchars(json_encode($file), ENT_QUOTES); ?>, "movies")' title="Mou a Pel·lícules"><i class="bi bi-film"></i></button>
                    <button class="btn btn-sm btn-outline-success" onclick='moveLibraryItem(<?php echo htmlspecialchars(json_encode($file), ENT_QUOTES); ?>, "music")' title="Mou a Música"><i class="bi bi-music-note"></i></button>
                    <button class="btn btn-sm btn-outline-primary" onclick='moveLibraryItem(<?php echo htmlspecialchars(json_encode($file), ENT_QUOTES); ?>, "docs")' title="Mou a Documents"><i class="bi bi-file-earmark-text"></i></button>
                    <button class="btn btn-sm btn-outline-danger" onclick='deleteLibraryItem(<?php echo htmlspecialchars(json_encode($file), ENT_QUOTES); ?>)' title="Elimina"><i class="bi bi-trash"></i></button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
