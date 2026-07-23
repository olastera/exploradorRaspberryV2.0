<?php
require_once __DIR__ . '/config/bootstrap.php';
use App\Auth\Auth;
use App\Auth\Csrf;
use App\Auth\UserManager;
use App\Media\FileExplorer;
use App\Media\Clipboard;
use App\Security\PathValidator;

$currentUser = Auth::requireAuth();
$isAdmin = $currentUser['role'] === 'admin';

if (!$isAdmin && isset($_GET['lib']) && $_GET['lib'] !== 'movies') {
    $_GET['lib'] = 'movies';
}

$library = $_GET['lib'] ?? 'movies';
$requestPath = isset($_GET['path']) ? $_GET['path'] : '';
$root = FileExplorer::getLibraryRoot($library);
$msg = '';
$msgType = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin) {
    $token = $_POST['csrf_token'] ?? '';
    if (!Csrf::verify($token)) {
        $msg = 'Token de seguridad inválido.';
        $msgType = 'danger';
    } else {
        // Delete
        if (isset($_POST['delete'])) {
            $itemToDelete = basename($_POST['delete']);
            $fullPathToDelete = PathValidator::validateIn($root, $requestPath . '/' . $itemToDelete);
            $currentDirectory = PathValidator::validateIn($root, $requestPath);
            if ($fullPathToDelete && $currentDirectory && dirname($fullPathToDelete) === $currentDirectory) {
                if (is_dir($fullPathToDelete)) {
                    $result = deleteRecursive($fullPathToDelete);
                } else {
                    $result = unlink($fullPathToDelete);
                }
                $msg = $result ? 'Elemento eliminado correctamente.' : 'Error al eliminar.';
                $msgType = $result ? 'success' : 'danger';
            }
        }

        // Rename
        if (isset($_POST['rename_old']) && isset($_POST['rename_new'])) {
            $oldName = basename($_POST['rename_old']);
            $newName = basename($_POST['rename_new']);
            if (!empty($newName)) {
                $oldPath = PathValidator::validateIn($root, $requestPath . '/' . $oldName);
                $parentPath = PathValidator::validateIn($root, $requestPath);
                $newPath = $parentPath ? $parentPath . DIRECTORY_SEPARATOR . $newName : null;
                if ($oldPath && $newPath && dirname($oldPath) === $parentPath && !file_exists($newPath)) {
                    $result = rename($oldPath, $newPath);
                    $msg = $result ? 'Elemento renombrado correctamente.' : 'Error al renombrar.';
                    $msgType = $result ? 'success' : 'danger';
                }
            }
        }

        // Clipboard (copiar/cortar y pegar dentro de la misma biblioteca)
        if (isset($_POST['clipboard_action']) && isset($_POST['clipboard_items']) && !empty($_POST['clipboard_source'])) {
            $action = $_POST['clipboard_action'];
            $items = json_decode($_POST['clipboard_items'], true);
            $currentDirectory = PathValidator::validateIn($root, $requestPath);
            if (in_array($action, ['copy', 'cut'], true) && is_array($items) && $currentDirectory) {
                $pasted = 0;
                $errors = 0;
                foreach ($items as $itemName) {
                    $srcFull = PathValidator::validateIn($root, (string) $itemName);
                    if (!$srcFull || !file_exists($srcFull)) { $errors++; continue; }
                    $destFull = uniqueDestinationPath($currentDirectory, basename($srcFull));
                    $result = $action === 'copy' ? Clipboard::copy($srcFull, $destFull) : Clipboard::cut($srcFull, $destFull);
                    if ($result) $pasted++; else $errors++;
                }
                $msg = "$pasted elemento(s) pegado(s).";
                if ($errors) $msg .= " $errors error(es).";
                $msgType = ($pasted === 0 && $errors > 0) ? 'danger' : 'success';
            }
        }

        // Extract videos
        if (isset($_POST['merge_video_folders'])) {
            $selectedFolders = json_decode($_POST['merge_video_folders'], true);
            $destinationName = basename(trim($_POST['merge_destination'] ?? ''));
            $currentDirectory = PathValidator::validateIn($root, $requestPath);
            if ($library !== 'movies' || !is_array($selectedFolders) || count($selectedFolders) < 2) {
                $msg = 'Selecciona com a mínim dues carpetes de pel·lícules.';
                $msgType = 'danger';
            } elseif ($destinationName === '' || in_array($destinationName, ['.', '..'], true) || !$currentDirectory) {
                $msg = 'El nom de la carpeta de destinació no és vàlid.';
                $msgType = 'danger';
            } else {
                $destinationPath = $currentDirectory . DIRECTORY_SEPARATOR . $destinationName;
                if (file_exists($destinationPath)) {
                    $msg = 'Ja existeix un element amb el nom de destinació. Tria un nom nou.';
                    $msgType = 'danger';
                } elseif (!mkdir($destinationPath, 0755)) {
                    $msg = 'No s’ha pogut crear la carpeta de destinació.';
                    $msgType = 'danger';
                } else {
                    $moved = 0;
                    $errors = 0;
                    $removed = 0;
                    $retained = 0;
                    $sourcePaths = [];
                    foreach (array_unique($selectedFolders) as $folderName) {
                        $folderName = basename((string) $folderName);
                        $sourcePath = PathValidator::validateIn($root, $requestPath . '/' . $folderName);
                        if (!$sourcePath || !is_dir($sourcePath) || dirname($sourcePath) !== $currentDirectory || $sourcePath === $destinationPath) {
                            $errors++;
                            continue;
                        }
                        $sourcePaths[] = $sourcePath;
                        $iterator = new RecursiveIteratorIterator(
                            new RecursiveDirectoryIterator($sourcePath, RecursiveDirectoryIterator::SKIP_DOTS),
                            RecursiveIteratorIterator::LEAVES_ONLY
                        );
                        foreach ($iterator as $sourceFile) {
                            if (!$sourceFile->isFile() || !FileExplorer::isVideoFile($sourceFile->getFilename())) continue;
                            $targetFile = uniqueDestinationPath($destinationPath, $sourceFile->getFilename());
                            if (Clipboard::cut($sourceFile->getPathname(), $targetFile)) $moved++;
                            else $errors++;
                        }
                    }
                    foreach ($sourcePaths as $sourcePath) {
                        removeEmptyDirectories($sourcePath);
                        if (!file_exists($sourcePath)) $removed++;
                        else $retained++;
                    }
                    $msg = "$moved vídeo(s) moguts a «$destinationName». $removed carpeta(es) origen eliminada(es).";
                    if ($retained) $msg .= " $retained conservada(es) perquè encara contenen altres fitxers.";
                    if ($errors) $msg .= " $errors error(s).";
                    $msgType = $errors ? 'warning' : 'success';
                }
            }
        }

        if (isset($_POST['extract_folder'])) {
            $folder = basename($_POST['extract_folder']);
            $folderPath = PathValidator::validateIn($root, $requestPath . '/' . $folder);
            if ($folderPath && is_dir($folderPath)) {
                $extracted = 0;
                $items = scandir($folderPath);
                foreach ($items as $item) {
                    if ($item[0] === '.') continue;
                    $full = $folderPath . '/' . $item;
                    if (is_file($full) && FileExplorer::isVideoFile($item)) {
                        $dest = $root . '/' . $requestPath . '/' . $item;
                        if (!file_exists($dest)) {
                            if (copy($full, $dest)) $extracted++;
                        }
                    }
                }
                $msg = "Vídeos extraídos: $extracted.";
                $msgType = 'success';
            }
        }

        // User management
        if (isset($_POST['user_create'])) {
            $username = trim($_POST['user_username'] ?? '');
            $password = $_POST['user_password'] ?? '';
            $role = $_POST['user_role'] ?? 'user';
            if (!empty($username) && !empty($password)) {
                $result = UserManager::create($username, $password, $role);
                $msg = $result ? 'Usuario creado correctamente.' : 'El usuario ya existe.';
                $msgType = $result ? 'success' : 'danger';
            }
        }
        if (isset($_POST['user_update'])) {
            $oldname = $_POST['user_oldname'] ?? '';
            $newname = trim($_POST['user_username'] ?? '');
            $password = $_POST['user_password'] ?? '';
            $role = $_POST['user_role'] ?? 'user';
            if (!empty($oldname) && !empty($newname)) {
                $result = UserManager::update($oldname, $newname, $password, $role);
                $msg = $result ? 'Usuario actualizado.' : 'Error al actualizar.';
                $msgType = $result ? 'success' : 'danger';
            }
        }
        if (isset($_POST['user_delete_name'])) {
            $username = $_POST['user_delete_name'];
            $result = UserManager::delete($username);
            $msg = $result ? 'Usuario eliminado.' : 'No se puede eliminar el último admin.';
            $msgType = $result ? 'success' : 'danger';
        }

        // Create folder
        if (isset($_POST['create_folder']) && !empty(trim($_POST['folder_name'] ?? ''))) {
            $folderName = basename(trim($_POST['folder_name']));
            $parentPath = PathValidator::validateIn($root, $requestPath);
            if ($parentPath && !file_exists($parentPath . '/' . $folderName)) {
                $result = mkdir($parentPath . '/' . $folderName, 0755, true);
                $msg = $result ? 'Carpeta creada correctamente.' : 'Error al crear la carpeta.';
                $msgType = $result ? 'success' : 'danger';
            } else {
                $msg = 'La carpeta ya existe o la ruta no es válida.';
                $msgType = 'danger';
            }
        }

        // Delete selected (bulk)
        if (isset($_POST['delete_selected']) && isset($_POST['selected_items'])) {
            $items = json_decode($_POST['selected_items'], true);
            $count = 0;
            foreach ($items as $itemName) {
                $fullPath = PathValidator::validateIn($root, $requestPath . '/' . basename($itemName));
                if ($fullPath) {
                    if (deleteRecursive($fullPath)) $count++;
                }
            }
            $msg = "$count elemento(s) eliminados.";
            $msgType = 'success';
        }
    }
}

$contents = FileExplorer::listContents($root, $requestPath);
$targetPath = $contents['targetPath'];
$relativePath = $contents['relativePath'];
$realBase = $contents['realBase'];
$directories = $contents['directories'];
$files = $contents['files'];

$users = UserManager::load();
$csrfToken = Csrf::token();
$videoExtensions = FileExplorer::$videoExtensions;
$audioExtensions = FileExplorer::$audioExtensions;

// Breadcrumbs
$breadcrumbs = [];
if (!empty($relativePath)) {
    $parts = explode('/', $relativePath);
    $cumulative = '';
    foreach ($parts as $p) {
        $cumulative .= ($cumulative ? '/' : '') . $p;
        $breadcrumbs[] = ['name' => $p, 'path' => $cumulative];
    }
}

$libraryIcons = [
    'movies' => 'film',
    'music' => 'music-note-beamed',
    'docs' => 'file-earmark-text',
];
$libraryNames = [
    'movies' => 'Pel·lícules',
    'music' => 'Música',
    'docs' => 'Documents',
];

// Detect upload actions
$uploadResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin && isset($_FILES['upload_files']) && Csrf::verify($_POST['csrf_token'] ?? '')) {
    $uploadResult = handleUpload($root, $requestPath, $_FILES['upload_files']);
}

// Move item to another library
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin && Csrf::verify($_POST['csrf_token'] ?? '') && isset($_POST['move_item']) && isset($_POST['move_target'])) {
    $itemName = basename($_POST['move_item']);
    $targetLib = $_POST['move_target'];
    if (!in_array($targetLib, ['movies', 'music', 'docs'], true) || $targetLib === $library) {
        $msg = 'La biblioteca de destinació no és vàlida.';
        $msgType = 'danger';
    } else {
    $targetDir = FileExplorer::getLibraryRoot($targetLib);
    $srcPath = PathValidator::validateIn($root, $requestPath . '/' . $itemName);
    $destPath = $targetDir . '/' . $itemName;
    if ($srcPath && file_exists($srcPath) && !file_exists($destPath)) {
        $result = Clipboard::cut($srcPath, $destPath);
        $msg = $result ? 'Movido a ' . $libraryNames[$targetLib] . ' correctamente.' : 'Error al mover.';
        $msgType = $result ? 'success' : 'danger';
    } elseif (file_exists($destPath)) {
        $msg = 'Ya existe un elemento con ese nombre en ' . $libraryNames[$targetLib] . '.';
        $msgType = 'danger';
    }
    }
}

// Move selected items to another library
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin && Csrf::verify($_POST['csrf_token'] ?? '') && isset($_POST['move_selected']) && isset($_POST['move_items']) && isset($_POST['move_target'])) {
    $items = json_decode($_POST['move_items'], true);
    $targetLib = $_POST['move_target'];
    if (!is_array($items) || !in_array($targetLib, ['movies', 'music', 'docs'], true) || $targetLib === $library) {
        $msg = 'La selecció o la biblioteca de destinació no és vàlida.';
        $msgType = 'danger';
    } else {
    $targetDir = FileExplorer::getLibraryRoot($targetLib);
    $count = 0;
    $errors = 0;
    foreach ($items as $itemName) {
        $itemName = basename($itemName);
        $srcPath = PathValidator::validateIn($root, $requestPath . '/' . $itemName);
        $destPath = $targetDir . '/' . $itemName;
        if ($srcPath && file_exists($srcPath) && !file_exists($destPath)) {
            if (Clipboard::cut($srcPath, $destPath)) $count++;
            else $errors++;
        } else {
            $errors++;
        }
    }
    $msg = "$count elemento(s) movidos a " . $libraryNames[$targetLib] . ".";
    if ($errors > 0) $msg .= " $errors error(es).";
    $msgType = $count > 0 ? 'success' : 'danger';
    }
}
?>
<?php
$pageTitle = ($libraryNames[$library] ?? 'Explorador') . ' - Explorador de Medios';
include __DIR__ . '/views/layouts/main-header.php';
?>

<!-- ALERTS -->
<?php if ($msg): ?>
    <div class="alert alert-<?php echo $msgType; ?> alert-dismissible fade show py-2">
        <?php echo $msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($uploadResult): ?>
    <div class="alert alert-<?php echo $uploadResult['type']; ?> alert-dismissible fade show py-2">
        <?php echo $uploadResult['msg']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Breadcrumbs + Actions -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="?lib=<?php echo $library; ?>"><i aria-hidden="true" class="bi bi-house-fill"></i></a></li>
            <?php if (!empty($relativePath)): ?>
                <?php foreach ($breadcrumbs as $b): ?>
                    <li class="breadcrumb-item <?php echo ($b['path'] === $relativePath) ? 'active' : '' ?>">
                        <?php if ($b['path'] !== $relativePath): ?>
                            <a href="?lib=<?php echo $library; ?>&path=<?php echo urlencode($b['path']); ?>"><?php echo htmlspecialchars($b['name']); ?></a>
                        <?php else: ?>
                            <?php echo htmlspecialchars($b['name']); ?>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ol>
    </nav>
    <div class="d-flex gap-2">
        <?php if ($isAdmin && ($library === 'docs' || $library === 'music')): ?>
            <button class="btn btn-sm btn-outline-primary" onclick="showUploadModal()"><i aria-hidden="true" class="bi bi-upload"></i> Puja</button>
            <button class="btn btn-sm btn-outline-secondary" onclick="showNewFolderModal()"><i aria-hidden="true" class="bi bi-folder-plus"></i> Carpeta</button>
        <?php endif; ?>
    </div>
</div>

<?php if ($isAdmin): ?>
<div class="toolbar card-custom p-2 mb-3">
    <div class="input-group input-group-sm explorer-search">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="search" class="form-control" id="explorerSearch" placeholder="Cerca per nom…" autocomplete="off">
    </div>
    <div class="d-flex gap-2 ms-auto">
        <button class="btn btn-sm btn-outline-success d-none" type="button" id="pasteClipboardBtn" onclick="pasteClipboard()" title="Enganxa aquí">
            <i class="bi bi-clipboard-check"></i> <span id="pasteClipboardLabel">Enganxa</span>
        </button>
        <button class="btn btn-sm btn-outline-secondary d-none" type="button" id="clearClipboardBtn" onclick="clearClipboard()" title="Buida el porta-retalls">
            <i class="bi bi-x-lg"></i>
        </button>
        <?php if ($library === 'movies'): ?>
        <div class="btn-group btn-group-sm" role="group" aria-label="Visualització">
            <button class="btn btn-outline-light active" type="button" id="gridViewBtn" onclick="setExplorerView('grid')" title="Graella"><i class="bi bi-grid"></i></button>
            <button class="btn btn-outline-light" type="button" id="listViewBtn" onclick="setExplorerView('list')" title="Llista"><i class="bi bi-list-ul"></i></button>
        </div>
        <?php endif; ?>
        <span class="text-muted small align-self-center" id="explorerItemCount"></span>
    </div>
</div>
<?php endif; ?>

<?php if ($isAdmin): ?>
<div class="selection-bar" id="selectionBar">
    <span id="selectedCount" class="text-muted small">0 seleccionats</span>
    <button class="btn btn-sm btn-outline-light" onclick="clipboardCopySelected()"><i class="bi bi-clipboard-plus"></i> Copia</button>
    <button class="btn btn-sm btn-outline-light" onclick="clipboardCutSelected()"><i class="bi bi-scissors"></i> Retalla</button>
    <button class="btn btn-sm btn-outline-danger" onclick="deleteSelected()"><i class="bi bi-trash"></i> Elimina</button>
    <?php if ($library === 'movies'): ?>
    <button class="btn btn-sm btn-outline-warning" onclick="openMergeVideoFolders()"><i class="bi bi-collection-play"></i> Agrupa pel·lícules</button>
    <?php endif; ?>
    <?php foreach (['movies' => 'Pel·lícules', 'music' => 'Música', 'docs' => 'Documents'] as $targetKey => $targetLabel): ?>
        <?php if ($targetKey !== $library): ?>
        <button class="btn btn-sm btn-outline-primary" onclick="moveSelected('<?php echo $targetKey; ?>')"><i class="bi bi-folder-symlink"></i> Mou a <?php echo $targetLabel; ?></button>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($isAdmin && $library === 'movies'): ?>
<div class="modal fade" id="mergeVideoFoldersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-collection-play me-2"></i>Agrupa carpetes de pel·lícules</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tanca"></button>
            </div>
            <form method="post" id="mergeVideoFoldersForm">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">
                    <input type="hidden" name="merge_video_folders" id="mergeVideoFoldersInput">
                    <p class="small text-muted">Es mouran recursivament tots els vídeos de les carpetes seleccionades.</p>
                    <div class="small mb-3" id="mergeVideoFoldersList"></div>
                    <label class="form-label" for="mergeDestinationInput">Nom de la carpeta nova</label>
                    <input class="form-control" name="merge_destination" id="mergeDestinationInput" placeholder="Ex.: Col·lecció de pel·lícules" required>
                    <div class="form-text">Les carpetes origen només s’eliminaran si queden completament buides.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel·la</button>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-box-arrow-in-right me-1"></i>Mou i agrupa</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Main content -->
<div id="explorerContent" data-library="<?php echo htmlspecialchars($library, ENT_QUOTES); ?>">
<?php if ($library === 'movies' && !$isAdmin): ?>
    <?php include __DIR__ . '/views/user-cards.php'; ?>
<?php elseif ($library === 'movies'): ?>
    <?php include __DIR__ . '/views/admin-movies.php'; ?>
<?php elseif ($library === 'music'): ?>
    <?php include __DIR__ . '/views/music/album-grid.php'; ?>
<?php elseif ($library === 'docs'): ?>
    <?php include __DIR__ . '/views/docs/file-list.php'; ?>
<?php endif; ?>
</div>

<?php if ($isAdmin): ?>
<nav class="d-flex justify-content-between align-items-center gap-2 mt-3" id="explorerPagination" aria-label="Paginació">
    <span class="text-muted small" id="explorerPageInfo"></span>
    <div class="btn-group btn-group-sm">
        <button class="btn btn-outline-light" type="button" id="explorerPrev"><i class="bi bi-chevron-left"></i></button>
        <button class="btn btn-outline-light" type="button" id="explorerNext"><i class="bi bi-chevron-right"></i></button>
    </div>
</nav>
<?php endif; ?>

<?php
ob_start();
?>
document.addEventListener('DOMContentLoaded', function() {
<?php if ($library === 'movies'): ?>
    var posterImages = document.querySelectorAll('.poster-thumb[data-query]');
    for (let start = 0; start < posterImages.length; start += 5) {
        setTimeout(function() {
            loadPosterBatch(posterImages, start, 5);
        }, (start / 5) * 250);
    }
<?php endif; ?>
    initExplorerBrowser();
    updatePasteButton();
});
<?php
$pageScript = ob_get_clean();

include __DIR__ . '/views/layouts/main-footer.php';
?>

<?php
function deleteRecursive($path) {
    if (is_dir($path)) {
        $items = scandir($path);
        foreach ($items as $item) {
            if ($item[0] === '.') continue;
            deleteRecursive($path . '/' . $item);
        }
        return rmdir($path);
    }
    return unlink($path);
}

function uniqueDestinationPath($directory, $filename) {
    $candidate = $directory . DIRECTORY_SEPARATOR . basename($filename);
    if (!file_exists($candidate)) return $candidate;
    $name = pathinfo($filename, PATHINFO_FILENAME);
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    $suffix = $extension === '' ? '' : '.' . $extension;
    $counter = 1;
    do {
        $candidate = $directory . DIRECTORY_SEPARATOR . $name . '_' . $counter . $suffix;
        $counter++;
    } while (file_exists($candidate));
    return $candidate;
}

function removeEmptyDirectories($directory) {
    if (!is_dir($directory) || is_link($directory)) return false;
    foreach (scandir($directory) as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $directory . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path) && !is_link($path)) removeEmptyDirectories($path);
    }
    $remaining = array_values(array_diff(scandir($directory), ['.', '..']));
    return empty($remaining) ? rmdir($directory) : false;
}

function handleUpload($root, $requestPath, $files) {
    $uploaded = 0;
    $errors = [];
    $targetDir = \App\Security\PathValidator::validateIn($root, $requestPath);
    if (!$targetDir) return ['type' => 'danger', 'msg' => 'Ruta inválida.'];

    if (isset($_POST['upload_as_zip']) && $_POST['upload_as_zip'] === '1') {
        foreach ($files['tmp_name'] as $i => $tmp) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
            $name = basename($files['name'][$i]);
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if ($ext === 'zip') {
                $zipPath = $targetDir . '/' . $name;
                if (move_uploaded_file($tmp, $zipPath)) {
                    $zip = new ZipArchive();
                    if ($zip->open($zipPath) === true) {
                        $extractDir = $targetDir . '/' . pathinfo($name, PATHINFO_FILENAME);
                        if (!is_dir($extractDir)) mkdir($extractDir, 0755, true);
                        $zip->extractTo($extractDir);
                        $zip->close();
                        unlink($zipPath);
                        $uploaded++;
                    } else {
                        $errors[] = "Error al descomprimir: $name";
                    }
                }
            }
        }
    } else {
        foreach ($files['tmp_name'] as $i => $tmp) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
            $name = basename($files['name'][$i]);
            if (move_uploaded_file($tmp, $targetDir . '/' . $name)) {
                $uploaded++;
            }
        }
    }

    $msg = $uploaded > 0 ? "$uploaded archivo(s) subidos correctamente." : '';
    if (!empty($errors)) $msg .= ' ' . implode(', ', $errors);
    return ['type' => ($uploaded > 0 || empty($errors)) ? 'success' : 'danger', 'msg' => $msg ?: 'Error al subir archivos.'];
}
