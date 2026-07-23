<?php
namespace App\System;

class Dashboard
{
    public static function getStats()
    {
        return [
            'uptime' => self::getUptime(),
            'cpu' => self::getCpuUsage(),
            'cpuTemp' => self::getCpuTemp(),
            'memory' => self::getMemory(),
            'disk' => self::getDiskInfo(MEDIA_ROOT),
            'load' => sys_getloadavg(),
            'hostname' => gethostname(),
            'ip' => self::getIp(),
            'os' => php_uname('s') . ' ' . php_uname('r'),
            'php' => phpversion(),
        ];
    }

    private static function getUptime() {
        $uptime = @shell_exec('uptime -p 2>/dev/null');
        return $uptime ? trim($uptime) : 'N/A';
    }

    private static function getCpuUsage() {
        $prevIdle = 0; $prevTotal = 0;
        $stat1 = @file_get_contents('/proc/stat');
        if ($stat1 === false) return 0;
        $parts1 = explode(' ', preg_replace('/\s+/', ' ', trim(explode("\n", $stat1)[0])));
        if (count($parts1) < 5) return 0;
        $prevIdle = intval($parts1[4]);
        $prevTotal = array_sum(array_slice($parts1, 1, 8));
        usleep(200000);
        $stat2 = @file_get_contents('/proc/stat');
        if ($stat2 === false) return 0;
        $parts2 = explode(' ', preg_replace('/\s+/', ' ', trim(explode("\n", $stat2)[0])));
        if (count($parts2) < 5) return 0;
        $idle = intval($parts2[4]);
        $total = array_sum(array_slice($parts2, 1, 8));
        $deltaIdle = $idle - $prevIdle;
        $deltaTotal = $total - $prevTotal;
        if ($deltaTotal <= 0) return 0;
        return round((1 - $deltaIdle / $deltaTotal) * 100, 1);
    }

    private static function getCpuTemp() {
        $temp = @file_get_contents('/sys/class/thermal/thermal_zone0/temp');
        if ($temp !== false) {
            return round(intval($temp) / 1000, 1);
        }
        $temp2 = @shell_exec("vcgencmd measure_temp 2>/dev/null");
        if ($temp2 && preg_match('/(\d+\.?\d*)/', $temp2, $m)) {
            return floatval($m[1]);
        }
        return null;
    }

    private static function getMemory() {
        $free = @shell_exec('free -b 2>/dev/null');
        if (!$free) return ['total' => 0, 'used' => 0, 'percent' => 0];
        $lines = explode("\n", $free);
        if (count($lines) < 2) return ['total' => 0, 'used' => 0, 'percent' => 0];
        $parts = preg_split('/\s+/', trim($lines[1]));
        if (count($parts) < 3) return ['total' => 0, 'used' => 0, 'percent' => 0];
        $total = intval($parts[1]);
        $used = intval($parts[2]);
        $percent = $total > 0 ? round(($used / $total) * 100, 1) : 0;
        return [
            'total' => self::formatBytes($total),
            'used' => self::formatBytes($used),
            'percent' => $percent,
        ];
    }

    private static function getDiskInfo($path) {
        $df = @shell_exec('df ' . escapeshellarg($path) . ' -B1 2>/dev/null');
        if (!$df) return ['total' => 0, 'used' => 0, 'avail' => 0, 'percent' => 0];
        $lines = explode("\n", $df);
        if (count($lines) < 2) return ['total' => 0, 'used' => 0, 'avail' => 0, 'percent' => 0];
        $parts = preg_split('/\s+/', trim($lines[1]));
        if (count($parts) < 4) return ['total' => 0, 'used' => 0, 'avail' => 0, 'percent' => 0];
        $total = intval($parts[1]);
        $used = intval($parts[2]);
        $avail = intval($parts[3]);
        $percent = $total > 0 ? round(($used / $total) * 100, 1) : 0;
        return [
            'total' => self::formatBytes($total),
            'used' => self::formatBytes($used),
            'avail' => self::formatBytes($avail),
            'percent' => $percent,
        ];
    }

    private static function getIp() {
        $ip = @shell_exec("hostname -I 2>/dev/null");
        return $ip ? trim(explode(' ', trim($ip))[0]) : 'N/A';
    }

    private static function formatBytes($bytes) {
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 1) . ' GB';
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024) return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}
