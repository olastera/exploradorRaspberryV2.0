<?php
namespace App\Media;

class PosterCache
{
    private static function directory()
    {
        $dir = STORAGE_DIR . '/cache/posters';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        return $dir;
    }

    public static function find($key)
    {
        $matches = glob(self::directory() . '/' . $key . '.*');
        return $matches ? $matches[0] : null;
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
