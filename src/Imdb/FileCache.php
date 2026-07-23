<?php
namespace App\Imdb;

class FileCache
{
    const COMPLETE_TTL = 2592000; // 30 days
    const INCOMPLETE_TTL = 21600; // Retry incomplete metadata after 6 hours
    const MISS_TTL = 3600; // Retry failed searches after 1 hour

    public static function get($key)
    {
        $file = STORAGE_DIR . '/cache/imdb/' . $key . '.json';
        if (!file_exists($file)) return null;
        $data = json_decode(file_get_contents($file), true);
        if (!is_array($data)) {
            @unlink($file);
            return null;
        }

        $ttl = self::ttlFor($data);
        if ((time() - filemtime($file)) > $ttl) {
            @unlink($file);
            return null;
        }
        return $data;
    }

    public static function set($key, $data)
    {
        $dir = STORAGE_DIR . '/cache/imdb';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        file_put_contents(
            $dir . '/' . $key . '.json',
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            LOCK_EX
        );
    }

    public static function isComplete($data)
    {
        return !empty($data['found'])
            && !empty($data['title'])
            && !empty($data['poster'])
            && !empty($data['plot'])
            && ($data['plot_lang'] ?? 'ca') === 'ca';
    }

    private static function ttlFor($data)
    {
        if (self::isComplete($data)) return self::COMPLETE_TTL;
        if (empty($data['found'])) return self::MISS_TTL;
        return self::INCOMPLETE_TTL;
    }
}
