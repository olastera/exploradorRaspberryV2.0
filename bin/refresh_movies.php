<?php
// Solo se puede invocar por CLI (lanzado por MoviesRefreshJob::start() vía nohup).
// Cualquier fichero .php de este proyecto es accesible por HTTP (.htaccess deja
// pasar todo *.php), así que este guardián es la única protección de este script:
// no lleva sesión ni CSRF porque no está pensado para ejecutarse como petición web.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die('CLI only');
}

require_once __DIR__ . '/../config/bootstrap.php';

use App\Media\FileExplorer;
use App\Media\PosterCache;
use App\Imdb\ImdbSearch;

$statusFile = STORAGE_DIR . '/cache/movies_refresh.json';

function writeStatus($statusFile, array $data)
{
    file_put_contents($statusFile, json_encode($data), LOCK_EX);
}

function readStatus($statusFile)
{
    $data = is_file($statusFile) ? json_decode((string) file_get_contents($statusFile), true) : null;
    return is_array($data) ? $data : ['running' => true];
}

// Recorre toda la biblioteca de Películas recursivamente. Cada carpeta y cada
// archivo de vídeo es, en la interfaz, una tarjeta con su propia búsqueda IMDb
// (ver views/admin-movies.php), así que aquí replicamos la misma limpieza de
// nombre que esa vista para que el nombre buscado (y su cacheKey resultante)
// coincida con lo que el navegador pediría al visitar esa carpeta.
function collectMovieNames($root, $relativePath = '')
{
    $contents = FileExplorer::listContents($root, $relativePath);
    $names = [];

    foreach ($contents['directories'] as $dir) {
        $clean = preg_replace('/\[.*?\]/', ' ', $dir);
        $clean = preg_replace('/\((?:19|20)\d{2}\)/', ' ', $clean);
        $clean = trim(preg_replace('/\s+/', ' ', $clean));
        if ($clean !== '') $names[] = $clean;

        $childPath = $relativePath ? $relativePath . '/' . $dir : $dir;
        $names = array_merge($names, collectMovieNames($root, $childPath));
    }

    foreach ($contents['files'] as $file) {
        $name = pathinfo($file, PATHINFO_FILENAME);
        if ($name !== '') $names[] = $name;
    }

    return $names;
}

// Si search() acaba de encontrar/renovar una carátula (poster.php?key=...) pero
// todavía no está descargada en disco, la descarga ya mismo en vez de esperar
// a que el navegador la pida de forma perezosa (ver poster.php).
function ensurePosterDownloaded($result)
{
    if (empty($result['poster']) || empty($result['poster_source'])) return;
    if (!preg_match('/^poster\.php\?key=([a-f0-9]{32})$/', $result['poster'], $m)) return;

    $key = $m[1];
    if (PosterCache::find($key) !== null) return;

    $downloaded = PosterCache::download($result['poster_source']);
    if ($downloaded !== null) {
        PosterCache::store($key, $downloaded['bytes'], $downloaded['contentType']);
    }
}

$root = FileExplorer::getLibraryRoot('movies');
$names = array_values(array_unique(collectMovieNames($root)));

$status = readStatus($statusFile);
$status['running'] = true;
$status['total'] = count($names);
$status['processed'] = 0;
$status['current'] = '';
writeStatus($statusFile, $status);

foreach ($names as $name) {
    $status['current'] = $name;
    writeStatus($statusFile, $status);

    try {
        $result = ImdbSearch::search($name);
        ensurePosterDownloaded($result);
    } catch (\Throwable $e) {
        $status['error'] = $name . ': ' . $e->getMessage();
    }

    $status['processed']++;
    writeStatus($statusFile, $status);
}

$status['running'] = false;
$status['current'] = '';
$status['finished_at'] = time();
writeStatus($statusFile, $status);
