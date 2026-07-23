<?php
namespace App\Media;

use App\Security\PathValidator;

class FileExplorer
{
    public static $videoExtensions = ['mp4', 'mkv', 'avi', 'mov', 'webm', 'ogg', 'wmv', 'flv', 'm4v', 'ts', 'vob'];
    public static $audioExtensions = ['mp3', 'flac', 'wav', 'ogg', 'm4a', 'aac', 'wma'];
    public static $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
    public static $docExtensions = ['pdf', 'txt', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp'];

    public static function getLibraryRoot($library)
    {
        switch ($library) {
            case 'music':
                return MEDIA_MUSIC;
            case 'docs':
                return MEDIA_DOCS;
            case 'images':
                return MEDIA_IMAGES;
            case 'movies':
            default:
                return MEDIA_ROOT . '/' . MEDIA_MOVIES;
        }
    }

    public static function listContents($root, $requestPath)
    {
        $validated = PathValidator::validate($root, $requestPath);
        $targetPath = $validated['path'];
        $relativePath = $validated['relativePath'];

        $items = scandir($targetPath);
        $directories = [];
        $files = [];

        foreach ($items as $item) {
            if ($item === '.' || ($item === '..' && empty($relativePath))) continue;
            if ($item[0] === '.') continue;
            if (pathinfo($item, PATHINFO_EXTENSION) === 'php') continue;
            $fullPath = $targetPath . DIRECTORY_SEPARATOR . $item;
            if (is_dir($fullPath)) {
                $directories[] = $item;
            } else {
                $files[] = $item;
            }
        }

        sort($directories);
        sort($files);

        return [
            'directories' => $directories,
            'files' => $files,
            'targetPath' => $targetPath,
            'relativePath' => $relativePath,
            'realBase' => $validated['realBase'],
        ];
    }

    public static function getVideoInfo($root, $relativeFile)
    {
        $fullPath = $root . DIRECTORY_SEPARATOR . $relativeFile;
        $ext = strtolower(pathinfo($relativeFile, PATHINFO_EXTENSION));
        $isVideo = in_array($ext, self::$videoExtensions);
        $isAudio = in_array($ext, self::$audioExtensions);
        $isImage = in_array($ext, self::$imageExtensions);
        $size = file_exists($fullPath) ? filesize($fullPath) : 0;

        return [
            'ext' => $ext,
            'isVideo' => $isVideo,
            'isAudio' => $isAudio,
            'isImage' => $isImage,
            'size' => $size,
            'sizeFormatted' => self::formatSize($size),
            'canConvert' => $isVideo && $ext !== 'mp4',
        ];
    }

    public static function formatSize($bytes)
    {
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 1) . ' GB';
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024) return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }

    public static function getFileUrl($relativePath, $library = 'movies')
    {
        return 'serve.php?lib=' . urlencode($library) . '&file=' . urlencode($relativePath);
    }

    public static function isVideoFile($file)
    {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        return in_array($ext, self::$videoExtensions);
    }

    public static function isAudioFile($file)
    {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        return in_array($ext, self::$audioExtensions);
    }
}
