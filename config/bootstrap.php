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
define('MEDIA_ROOT', realpath(getenv('MEDIA_ROOT') ?: '/mnt/disco/torrent-complete'));
define('MEDIA_MOVIES', getenv('MEDIA_MOVIES') ?: '.');
define('MEDIA_MUSIC', getenv('MEDIA_MUSIC') ?: 'musica');
define('MEDIA_DOCS', getenv('MEDIA_DOCS') ?: 'documentos');
define('TURNSTILE_SITE_KEY', getenv('TURNSTILE_SITE_KEY') ?: '');
define('TURNSTILE_SECRET_KEY', getenv('TURNSTILE_SECRET_KEY') ?: '');
define('OMDB_API_KEY', getenv('OMDB_API_KEY') ?: '');
define('STORAGE_DIR', APP_ROOT . '/storage');

if (session_status() === PHP_SESSION_NONE) session_start();
