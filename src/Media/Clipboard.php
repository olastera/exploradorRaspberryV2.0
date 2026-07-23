<?php
namespace App\Media;

use App\Security\PathValidator;

class Clipboard
{
    public static function copy($src, $dest)
    {
        if (is_dir($src)) {
            return self::copyDir($src, $dest);
        }
        return copy($src, $dest);
    }

    public static function cut($src, $dest)
    {
        if (@rename($src, $dest)) {
            return true;
        }
        if (self::copy($src, $dest)) {
            return self::deleteRecursive($src);
        }
        return false;
    }

    private static function copyDir($src, $dest)
    {
        if (!is_dir($dest)) {
            if (!mkdir($dest, 0755, true)) return false;
        }
        $items = scandir($src);
        foreach ($items as $item) {
            if ($item[0] === '.') continue;
            $s = $src . '/' . $item;
            $d = $dest . '/' . $item;
            if (is_dir($s)) {
                if (!self::copyDir($s, $d)) return false;
            } else {
                if (!copy($s, $d)) return false;
            }
        }
        return true;
    }

    private static function deleteRecursive($path)
    {
        if (is_dir($path)) {
            $items = scandir($path);
            foreach ($items as $item) {
                if ($item[0] === '.') continue;
                if (!self::deleteRecursive($path . '/' . $item)) return false;
            }
            return rmdir($path);
        } else {
            return unlink($path);
        }
    }
}
