<?php
namespace App\Media;

// Lanza y supervisa en segundo plano bin/refresh_movies.php (recorre toda la
// biblioteca de Películas y rellena la caché de metadatos IMDb/OMDb y de
// carátulas para lo que todavía no tengamos completo). Mismo patrón que
// App\Media\Converter para la cola de FFmpeg: nohup + PID en /proc.
class MoviesRefreshJob
{
    private static function statusFile()
    {
        return STORAGE_DIR . '/cache/movies_refresh.json';
    }

    private static function logFile()
    {
        return STORAGE_DIR . '/cache/movies_refresh.log';
    }

    public static function status()
    {
        $file = self::statusFile();
        if (!is_file($file)) return ['running' => false];

        $data = json_decode((string) file_get_contents($file), true);
        if (!is_array($data)) return ['running' => false];

        if (!empty($data['running']) && !empty($data['pid']) && !file_exists('/proc/' . (int) $data['pid'])) {
            $data['running'] = false;
            $data['finished_at'] = $data['finished_at'] ?? time();
            $data['error'] = $data['error'] ?? 'El procés s’ha aturat inesperadament.';
            self::write($data);
        }
        return $data;
    }

    public static function start()
    {
        $current = self::status();
        if (!empty($current['running'])) return false;

        $dir = STORAGE_DIR . '/cache';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);

        self::write([
            'running' => true,
            'processed' => 0,
            'total' => 0,
            'current' => '',
            'started_at' => time(),
            'finished_at' => null,
            'error' => null,
        ]);

        $php = escapeshellarg(PHP_BINARY);
        $script = escapeshellarg(APP_ROOT . '/bin/refresh_movies.php');
        $log = escapeshellarg(self::logFile());
        $pid = (int) trim((string) shell_exec("nohup {$php} {$script} > {$log} 2>&1 & echo $!"));

        $data = self::status();
        $data['pid'] = $pid;
        self::write($data);

        return true;
    }

    private static function write(array $data)
    {
        file_put_contents(self::statusFile(), json_encode($data), LOCK_EX);
    }
}
