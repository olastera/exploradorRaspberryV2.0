<?php
namespace App\System;

class Settings
{
    public static function load()
    {
        $file = STORAGE_DIR . '/settings.json';
        if (!is_file($file)) return [];
        $data = json_decode((string) file_get_contents($file), true);
        return is_array($data) ? $data : [];
    }

    public static function savePaths($movies, $music, $docs)
    {
        $paths = [
            'movies_path' => self::validDirectory($movies),
            'music_path' => self::validDirectory($music),
            'docs_path' => self::validDirectory($docs),
        ];
        foreach ($paths as $path) {
            if ($path === null) return false;
        }

        $file = STORAGE_DIR . '/settings.json';
        $tmp = $file . '.tmp';
        $written = file_put_contents(
            $tmp,
            json_encode($paths, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            LOCK_EX
        );
        if ($written === false) return false;
        @chmod($tmp, 0600);
        return rename($tmp, $file);
    }

    private static function validDirectory($path)
    {
        $path = trim((string) $path);
        if ($path === '' || $path[0] !== '/') return null;
        $real = realpath($path);
        return $real !== false && is_dir($real) && is_readable($real) ? $real : null;
    }
}
