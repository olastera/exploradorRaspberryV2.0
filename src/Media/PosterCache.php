<?php
namespace App\Media;

class PosterCache
{
    // Si la descarga de una carátula falla (enlace muerto, CDN caído...) no
    // reintentamos en cada petición: eso convertía cada visita a esa película en
    // una espera de hasta 5s. Se marca como fallida durante este tiempo y se
    // reintenta más tarde (o cuando el job de refresco vuelva a pasar).
    const FAIL_TTL = 86400; // 1 day

    private static function directory()
    {
        $dir = STORAGE_DIR . '/cache/posters';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        return $dir;
    }

    public static function find($key)
    {
        foreach (glob(self::directory() . '/' . $key . '.*') as $match) {
            if (substr($match, -5) !== '.fail') return $match;
        }
        return null;
    }

    public static function isRecentlyFailed($key)
    {
        $file = self::directory() . '/' . $key . '.fail';
        if (!is_file($file)) return false;
        if ((time() - filemtime($file)) > self::FAIL_TTL) {
            @unlink($file);
            return false;
        }
        return true;
    }

    public static function markFailed($key)
    {
        file_put_contents(self::directory() . '/' . $key . '.fail', (string) time(), LOCK_EX);
    }

    public static function download($url)
    {
        $ctx = stream_context_create([
            'http' => ['timeout' => 5, 'follow_location' => true, 'ignore_errors' => true,
                'header' => "User-Agent: Mozilla/5.0\r\n"],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]
        ]);
        $bytes = @file_get_contents($url, false, $ctx);
        if ($bytes === false || $bytes === '') return null;

        $contentType = 'image/jpeg';
        if (isset($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (stripos($header, 'Content-Type:') === 0) {
                    $contentType = trim(substr($header, strlen('Content-Type:')));
                    break;
                }
            }
        }
        return ['bytes' => $bytes, 'contentType' => $contentType];
    }

    public static function store($key, $bytes, $contentType)
    {
        $path = self::directory() . '/' . $key . '.' . self::extensionFor($contentType);
        file_put_contents($path, $bytes, LOCK_EX);
        return $path;
    }

    public static function stream($path)
    {
        $contentTypes = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif'];
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        header('Content-Type: ' . ($contentTypes[$ext] ?? 'application/octet-stream'));
        header('Content-Length: ' . filesize($path));
        readfile($path);
    }

    public static function clear()
    {
        $count = 0;
        foreach (glob(self::directory() . '/*') as $file) {
            if (is_file($file) && @unlink($file)) $count++;
        }
        return $count;
    }

    private static function extensionFor($contentType)
    {
        $contentType = strtolower(trim(explode(';', $contentType)[0]));
        switch ($contentType) {
            case 'image/png': return 'png';
            case 'image/webp': return 'webp';
            case 'image/gif': return 'gif';
            default: return 'jpg';
        }
    }
}
