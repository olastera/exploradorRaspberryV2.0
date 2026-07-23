<?php
namespace App\Media;

class Thumbnailer
{
    const MAX_SIZE = 400;
    const JPEG_QUALITY = 82;

    public static function get($sourcePath)
    {
        if (!is_file($sourcePath)) return null;

        $mtime = @filemtime($sourcePath);
        if ($mtime === false) return null;

        $dir = STORAGE_DIR . '/cache/thumbs';
        $key = md5($sourcePath) . '_' . $mtime;
        $thumbPath = $dir . '/' . $key . '.jpg';

        if (is_file($thumbPath)) return $thumbPath;

        $image = self::loadImage($sourcePath);
        if ($image === null) return null;

        $image = self::applyExifOrientation($image, $sourcePath);

        $width = imagesx($image);
        $height = imagesy($image);
        if ($width < 1 || $height < 1) {
            imagedestroy($image);
            return null;
        }

        $scale = min(1, self::MAX_SIZE / max($width, $height));
        $destWidth = max(1, (int) round($width * $scale));
        $destHeight = max(1, (int) round($height * $scale));

        $thumb = imagecreatetruecolor($destWidth, $destHeight);
        imagecopyresampled($thumb, $image, 0, 0, 0, 0, $destWidth, $destHeight, $width, $height);
        imagedestroy($image);

        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $saved = imagejpeg($thumb, $thumbPath, self::JPEG_QUALITY);
        imagedestroy($thumb);

        return $saved ? $thumbPath : null;
    }

    private static function loadImage($path)
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $image = false;
        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                $image = @imagecreatefromjpeg($path);
                break;
            case 'png':
                $image = @imagecreatefrompng($path);
                break;
            case 'gif':
                $image = @imagecreatefromgif($path);
                break;
            case 'webp':
                if (function_exists('imagecreatefromwebp')) $image = @imagecreatefromwebp($path);
                break;
            case 'bmp':
                if (function_exists('imagecreatefrombmp')) $image = @imagecreatefrombmp($path);
                break;
        }
        return $image === false ? null : $image;
    }

    private static function applyExifOrientation($image, $sourcePath)
    {
        $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg'], true) || !function_exists('exif_read_data')) return $image;

        $exif = @exif_read_data($sourcePath);
        if (!$exif || empty($exif['Orientation'])) return $image;

        switch ($exif['Orientation']) {
            case 3:
                $rotated = imagerotate($image, 180, 0);
                break;
            case 6:
                $rotated = imagerotate($image, -90, 0);
                break;
            case 8:
                $rotated = imagerotate($image, 90, 0);
                break;
            default:
                $rotated = false;
        }
        if ($rotated !== false) {
            imagedestroy($image);
            return $rotated;
        }
        return $image;
    }
}
