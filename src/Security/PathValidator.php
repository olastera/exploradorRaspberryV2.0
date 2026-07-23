<?php
namespace App\Security;

class PathValidator
{
    public static function validate($root, $requestPath = '')
    {
        $realBase = realpath($root);
        if ($realBase === false) {
            throw new \Exception("El directorio raíz no existe: " . $root);
        }
        $targetPath = realpath($root . DIRECTORY_SEPARATOR . $requestPath);
        if ($targetPath === false || !self::isWithin($targetPath, $realBase)) {
            $targetPath = $realBase;
            $requestPath = '';
        }
        return ['path' => $targetPath, 'relativePath' => $requestPath, 'realBase' => $realBase];
    }

    public static function validateIn($base, $requestPath)
    {
        $full = realpath($base . DIRECTORY_SEPARATOR . $requestPath);
        $baseReal = realpath($base);
        if ($full === false || $baseReal === false || !self::isWithin($full, $baseReal)) {
            return null;
        }
        return $full;
    }

    private static function isWithin($path, $base)
    {
        $base = rtrim($base, DIRECTORY_SEPARATOR);
        return $path === $base || strpos($path, $base . DIRECTORY_SEPARATOR) === 0;
    }
}
