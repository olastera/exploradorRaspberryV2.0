<?php
namespace App\System;

class Dashboard
{
    public static function getStats()
    {
        $disks = self::getDisks([
            'Sistema' => '/',
            'Pel·lícules' => MEDIA_ROOT,
            'Música' => MEDIA_MUSIC,
            'Documents' => MEDIA_DOCS,
        ]);
        return [
            'uptime' => self::getUptime(),
            'cpu' => self::getCpuUsage(),
            'cpuTemp' => self::getCpuTemp(),
            'memory' => self::getMemory(),
            'disk' => $disks[0] ?? self::emptyDisk(),
            'disks' => $disks,
            'load' => sys_getloadavg(),
            'hostname' => gethostname(),
            'ip' => self::getIp(),
            'os' => php_uname('s') . ' ' . php_uname('r'),
            'php' => phpversion(),
        ];
    }

    public static function getProcesses($limit = 15)
    {
        $output = @shell_exec('ps -eo pid=,comm=,%cpu=,%mem=,etime= --sort=-%cpu 2>/dev/null');
        if (!$output) return [];
        $processes = [];
        foreach (preg_split('/\R/', trim($output)) as $line) {
            if (!preg_match('/^\s*(\d+)\s+(\S+)\s+([\d.]+)\s+([\d.]+)\s+(\S+)\s*$/', $line, $matches)) {
                continue;
            }
            $processes[] = [
                'pid' => (int) $matches[1],
                'command' => $matches[2],
                'cpu' => (float) $matches[3],
                'memory' => (float) $matches[4],
                'elapsed' => $matches[5],
            ];
            if (count($processes) >= max(1, min(50, (int) $limit))) break;
        }
        return $processes;
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
        $df = @shell_exec('df -P -B1 ' . escapeshellarg($path) . ' 2>/dev/null');
        if (!$df) return self::emptyDisk();
        $lines = explode("\n", $df);
        if (count($lines) < 2) return self::emptyDisk();
        $parts = preg_split('/\s+/', trim($lines[1]));
        if (count($parts) < 6) return self::emptyDisk();
        $total = intval($parts[1]);
        $used = intval($parts[2]);
        $avail = intval($parts[3]);
        $percent = $total > 0 ? round(($used / $total) * 100, 1) : 0;
        return [
            'total' => self::formatBytes($total),
            'used' => self::formatBytes($used),
            'avail' => self::formatBytes($avail),
            'percent' => $percent,
            'filesystem' => $parts[0],
            'mount' => $parts[5],
        ];
    }

    private static function getDisks($libraryPaths)
    {
        $disks = [];
        foreach ($libraryPaths as $library => $path) {
            if (!is_dir($path)) continue;
            $disk = self::getDiskInfo($path);
            if ($disk['filesystem'] === '') continue;
            $key = $disk['filesystem'];
            if (!isset($disks[$key])) {
                $disk['libraries'] = [];
                $disks[$key] = $disk;
            }
            $disks[$key]['libraries'][] = $library;
        }
        return array_values($disks);
    }

    private static function emptyDisk()
    {
        return [
            'total' => 0, 'used' => 0, 'avail' => 0, 'percent' => 0,
            'filesystem' => '', 'mount' => '', 'libraries' => [],
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
