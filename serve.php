<?php
require_once __DIR__ . '/config/bootstrap.php';
use App\Auth\Auth;
use App\Media\FileExplorer;
use App\Security\PathValidator;

$currentUser = Auth::requireAuth();

$file = '';
if (isset($currentUser['token_file'])) {
    $file = $currentUser['token_file'];
} elseif (isset($_GET['file'])) {
    $file = $_GET['file'];
}
if (empty($file)) {
    http_response_code(400);
    die('File parameter required');
}

$library = $_GET['lib'] ?? 'movies';
if (!in_array($library, ['movies', 'music', 'docs', 'images'], true)) {
    http_response_code(400);
    die('Invalid library');
}
if ($currentUser['role'] !== 'admin' && $library !== 'movies') {
    http_response_code(403);
    die('Access denied');
}
$root = FileExplorer::getLibraryRoot($library);
$validated = PathValidator::validate($root, $file);
$filePath = $validated['path'];

if (!file_exists($filePath) || !is_file($filePath)) {
    http_response_code(404);
    die('File not found');
}

$mimeTypes = [
    'mp4' => 'video/mp4',
    'mkv' => 'video/x-matroska',
    'avi' => 'video/x-msvideo',
    'mov' => 'video/quicktime',
    'webm' => 'video/webm',
    'ogg' => 'video/ogg',
    'wmv' => 'video/x-ms-wmv',
    'flv' => 'video/x-flv',
    'm4v' => 'video/mp4',
    'ts' => 'video/mp2t',
    'vob' => 'video/dvd',
    'mp3' => 'audio/mpeg',
    'flac' => 'audio/flac',
    'wav' => 'audio/wav',
    'm4a' => 'audio/mp4',
    'aac' => 'audio/aac',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
    'bmp' => 'image/bmp',
    'pdf' => 'application/pdf',
    'zip' => 'application/zip',
    'rar' => 'application/x-rar-compressed',
    '7z' => 'application/x-7z-compressed',
    'srt' => 'text/plain',
    'sub' => 'text/plain',
    'txt' => 'text/plain',
];

$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
$contentType = $mimeTypes[$ext] ?? 'application/octet-stream';
$fileName = basename($filePath);
$fileSize = filesize($filePath);
$isPlayable = in_array($ext, ['mp4', 'mkv', 'avi', 'mov', 'webm', 'ogg', 'wmv', 'flv', 'm4v', 'ts', 'vob', 'mp3', 'flac', 'wav', 'm4a', 'aac', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp']);

header('Content-Type: ' . $contentType);
header('Content-Disposition: ' . ($isPlayable ? 'inline' : 'attachment') . '; filename="' . addcslashes($fileName, '"') . '"');
header('Accept-Ranges: bytes');
header('X-Accel-Buffering: no');

if (isset($_SERVER['HTTP_RANGE'])) {
    if (!preg_match('/^bytes=(\d+)-(\d*)$/', trim($_SERVER['HTTP_RANGE']), $matches)) {
        header("Content-Range: bytes */$fileSize");
        http_response_code(416);
        exit;
    }
    $start = intval($matches[1]);
    $end = $matches[2] !== '' ? intval($matches[2]) : $fileSize - 1;
    if ($start >= $fileSize || $end < $start) {
        header("Content-Range: bytes */$fileSize");
        http_response_code(416);
        exit;
    }
    $end = min($end, $fileSize - 1);

    header('HTTP/1.1 206 Partial Content');
    header("Content-Range: bytes $start-$end/$fileSize");
    header('Content-Length: ' . ($end - $start + 1));

    $fp = fopen($filePath, 'rb');
    fseek($fp, $start);
    $remaining = $end - $start + 1;
    while ($remaining > 0 && !feof($fp)) {
        $read = min(8192, $remaining);
        echo fread($fp, $read);
        $remaining -= $read;
        flush();
    }
    fclose($fp);
} else {
    header('Content-Length: ' . $fileSize);
    $fp = fopen($filePath, 'rb');
    while (!feof($fp)) {
        echo fread($fp, 8192);
        flush();
    }
    fclose($fp);
}
