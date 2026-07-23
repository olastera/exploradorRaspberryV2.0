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
        if (self::copy($src, $dest)) {
            self::deleteRecursive($src);
            return true;
        }
        return false;
    }

    private static function copyDir($src, $dest)
    {
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }
        $items = scandir($src);
        foreach ($items as $item) {
            if ($item[0] === '.') continue;
            $s = $src . '/' . $item;
            $d = $dest . '/' . $item;
            if (is_dir($s)) {
                self::copyDir($s, $d);
            } else {
                copy($s, $d);
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
                self::deleteRecursive($path . '/' . $item);
            }
            rmdir($path);
        } else {
            unlink($path);
        }
    }
}
