<?php
require_once __DIR__ . '/autoload.php';

$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        putenv(trim($line));
    }
}

define('APP_ROOT', realpath(__DIR__ . '/..'));
define('STORAGE_DIR', APP_ROOT . '/storage');

$settings = [];
$settingsFile = STORAGE_DIR . '/settings.json';
if (is_file($settingsFile)) {
    $decodedSettings = json_decode((string) file_get_contents($settingsFile), true);
    if (is_array($decodedSettings)) $settings = $decodedSettings;
}
$envRoot = realpath(getenv('MEDIA_ROOT') ?: '/mnt/disco/torrent-complete');
$moviesRoot = $settings['movies_path'] ?? ($envRoot . '/' . (getenv('MEDIA_MOVIES') ?: '.'));
$musicRoot = $settings['music_path'] ?? ($envRoot . '/' . (getenv('MEDIA_MUSIC') ?: 'musica'));
$docsRoot = $settings['docs_path'] ?? ($envRoot . '/' . (getenv('MEDIA_DOCS') ?: 'documentos'));
$imagesRoot = $settings['images_path'] ?? ($envRoot . '/' . (getenv('MEDIA_IMAGES') ?: 'imatges'));
define('MEDIA_ROOT', realpath($moviesRoot) ?: $moviesRoot);
define('MEDIA_MOVIES', '.');
define('MEDIA_MUSIC', realpath($musicRoot) ?: $musicRoot);
define('MEDIA_DOCS', realpath($docsRoot) ?: $docsRoot);
define('MEDIA_IMAGES', realpath($imagesRoot) ?: $imagesRoot);
define('TURNSTILE_SITE_KEY', getenv('TURNSTILE_SITE_KEY') ?: '');
define('TURNSTILE_SECRET_KEY', getenv('TURNSTILE_SECRET_KEY') ?: '');
define('OMDB_API_KEY', getenv('OMDB_API_KEY') ?: '');

if (session_status() === PHP_SESSION_NONE) session_start();
