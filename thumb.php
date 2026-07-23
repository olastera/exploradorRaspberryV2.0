<?php
require_once __DIR__ . '/config/bootstrap.php';
use App\Auth\Auth;
use App\Media\FileExplorer;
use App\Media\Thumbnailer;
use App\Security\PathValidator;

$currentUser = Auth::requireAuth();
if ($currentUser['role'] !== 'admin') {
    http_response_code(403);
    die('Access denied');
}

$file = $_GET['file'] ?? '';
if (empty($file)) {
    http_response_code(400);
    die('File parameter required');
}

$root = FileExplorer::getLibraryRoot('images');
$validated = PathValidator::validate($root, $file);
$filePath = $validated['path'];

$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
if (!file_exists($filePath) || !is_file($filePath) || !in_array($ext, FileExplorer::$imageExtensions, true)) {
    http_response_code(404);
    die('File not found');
}

$thumbPath = Thumbnailer::get($filePath);
if ($thumbPath === null) {
    header('Location: serve.php?lib=images&file=' . urlencode($file));
    exit;
}

header('Content-Type: image/jpeg');
header('Cache-Control: public, max-age=2592000, immutable');
header('Content-Length: ' . filesize($thumbPath));
readfile($thumbPath);
