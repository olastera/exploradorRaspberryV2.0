<?php
require_once __DIR__ . '/config/bootstrap.php';
use App\Media\PosterCache;

$key = $_GET['key'] ?? '';
if (!preg_match('/^[a-f0-9]{32}$/', $key)) {
    http_response_code(400);
    die('Invalid key');
}

$cachedPath = PosterCache::find($key);
if ($cachedPath !== null) {
    header('Cache-Control: public, max-age=2592000, immutable');
    PosterCache::stream($cachedPath);
    exit;
}

$metaFile = STORAGE_DIR . '/cache/imdb/' . $key . '.json';
$meta = is_file($metaFile) ? json_decode((string) file_get_contents($metaFile), true) : null;
$sourceUrl = is_array($meta) ? ($meta['poster_source'] ?? null) : null;

if (empty($sourceUrl)) {
    http_response_code(404);
    die('Poster not found');
}

$downloaded = PosterCache::download($sourceUrl);
if ($downloaded === null) {
    header('Location: ' . $sourceUrl);
    exit;
}

$savedPath = PosterCache::store($key, $downloaded['bytes'], $downloaded['contentType']);
header('Cache-Control: public, max-age=2592000, immutable');
PosterCache::stream($savedPath);
