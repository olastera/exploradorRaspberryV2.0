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
            if ($fullPathToDelete && strpos($fullPathToDelete, PathValidator::validate($root, $requestPath)['path']) === 0) {
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
                $newPath = PathValidator::validateIn($root, $requestPath . '/' . $newName);
                if ($oldPath && $newPath) {
                    $result = rename($oldPath, $newPath);
                    $msg = $result ? 'Elemento renombrado correctamente.' : 'Error al renombrar.';
                    $msgType = $result ? 'success' : 'danger';
                }
            }
        }

        // Clipboard
        if (isset($_POST['clipboard_action']) && isset($_POST['clipboard_items'])) {
            $action = $_POST['clipboard_action'];
            $items = json_decode($_POST['clipboard_items'], true);
            if (!empty($_POST['clipboard_source'])) {
                $srcBase = PathValidator::validate($root, '')['path'];
                foreach ($items as $itemName) {
                    $srcFull = PathValidator::validateIn($root, $itemName);
                    $destFull = $root . '/' . $requestPath . '/' . basename($itemName);
                    if ($srcFull) {
                        if ($action === 'copy') Clipboard::copy($srcFull, $destFull);
                        elseif ($action === 'cut') Clipboard::cut($srcFull, $destFull);
                    }
                }
                $msg = 'Operación completada.';
                $msgType = 'success';
            }
        }

        // Extract videos
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
    $targetDir = FileExplorer::getLibraryRoot($targetLib);
    $srcPath = PathValidator::validateIn($root, $requestPath . '/' . $itemName);
    $destPath = $targetDir . '/' . $itemName;
    if ($srcPath && file_exists($srcPath) && !file_exists($destPath)) {
        $result = rename($srcPath, $destPath);
        $msg = $result ? 'Movido a ' . $libraryNames[$targetLib] . ' correctamente.' : 'Error al mover.';
        $msgType = $result ? 'success' : 'danger';
    } elseif (file_exists($destPath)) {
        $msg = 'Ya existe un elemento con ese nombre en ' . $libraryNames[$targetLib] . '.';
        $msgType = 'danger';
    }
}

// Move selected items to another library
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin && Csrf::verify($_POST['csrf_token'] ?? '') && isset($_POST['move_selected']) && isset($_POST['move_items']) && isset($_POST['move_target'])) {
    $items = json_decode($_POST['move_items'], true);
    $targetLib = $_POST['move_target'];
    $targetDir = FileExplorer::getLibraryRoot($targetLib);
    $count = 0;
    $errors = 0;
    foreach ($items as $itemName) {
        $itemName = basename($itemName);
        $srcPath = PathValidator::validateIn($root, $requestPath . '/' . $itemName);
        $destPath = $targetDir . '/' . $itemName;
        if ($srcPath && file_exists($srcPath) && !file_exists($destPath)) {
            if (rename($srcPath, $destPath)) $count++;
            else $errors++;
        } else {
            $errors++;
        }
    }
    $msg = "$count elemento(s) movidos a " . $libraryNames[$targetLib] . ".";
    if ($errors > 0) $msg .= " $errors error(es).";
    $msgType = $count > 0 ? 'success' : 'danger';
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

<!-- Main content -->
<?php if ($library === 'movies' && !$isAdmin): ?>
    <?php include __DIR__ . '/views/user-cards.php'; ?>
<?php elseif ($library === 'movies'): ?>
    <?php include __DIR__ . '/views/admin-movies.php'; ?>
<?php elseif ($library === 'music'): ?>
    <?php include __DIR__ . '/views/music/album-grid.php'; ?>
<?php elseif ($library === 'docs'): ?>
    <?php include __DIR__ . '/views/docs/file-list.php'; ?>
<?php endif; ?>

<?php
ob_start();
if ($library === 'movies'):
?>
document.addEventListener('DOMContentLoaded', function() {
    var posterImages = document.querySelectorAll('.poster-thumb[data-query]');
    if (posterImages.length > 0) loadPosterBatch(posterImages, 0, 5);
});
<?php
endif;
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
