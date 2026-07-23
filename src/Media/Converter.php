<?php
namespace App\Media;

class Converter
{
    public static function processQueue($conversionsDir, $maxConcurrent = 2)
    {
        $files = scandir($conversionsDir);
        $running = 0;
        $pending = [];

        foreach ($files as $f) {
            if (substr($f, -5) !== '.json') continue;
            $data = json_decode(file_get_contents($conversionsDir . '/' . $f), true);
            if (!$data) continue;
            if ($data['status'] === 'running') $running++;
            elseif ($data['status'] === 'pending') $pending[] = $data;
        }

        usort($pending, function ($a, $b) {
            return ($a['startedAt'] ?? 0) - ($b['startedAt'] ?? 0);
        });

        $started = 0;
        while ($running < $maxConcurrent && count($pending) > 0) {
            $job = array_shift($pending);
            $id = $job['id'];

            $dir = dirname($job['inputRelative']);
            $outputFile = MEDIA_ROOT . '/' . ($dir !== '.' ? $dir . '/' : '') . $job['output'];
            $logFile = $conversionsDir . '/' . $id . '.log';

            $escapedInput = escapeshellarg(MEDIA_ROOT . '/' . $job['inputRelative']);
            $escapedOutput = escapeshellarg($outputFile);
            $escapedLog = escapeshellarg($logFile);
            $cmd = "nohup ffmpeg -i $escapedInput -c:v libx264 -preset medium -crf 23 -c:a aac -movflags +faststart $escapedOutput >/dev/null 2> $escapedLog & echo \$!";
            $pid = trim(shell_exec($cmd));

            $job['status'] = 'running';
            $job['pid'] = intval($pid);
            $job['progress'] = 0;
            file_put_contents($conversionsDir . '/' . $id . '.json', json_encode($job, JSON_PRETTY_PRINT), LOCK_EX);

            $running++;
            $started++;
        }
        return $started;
    }

    public static function updateProgress($conversionsDir, $data, $maxConcurrent)
    {
        if ($data['totalDuration'] <= 0) return 0;

        $pid = $data['pid'] ?? 0;
        if ($pid > 0) {
            $running = file_exists("/proc/$pid");
            if (!$running) {
                $outputPath = MEDIA_ROOT . '/' . ($data['outputRelative'] ?? '');
                if (file_exists($outputPath) && filesize($outputPath) > 0) {
                    $data['status'] = 'completed';
                    $data['progress'] = 100;
                    $data['completedAt'] = time();
                } else {
                    $data['status'] = 'failed';
                    $data['progress'] = $data['progress'] ?? 0;
                    $data['error'] = 'El proceso terminó inesperadamente';
                }
                file_put_contents($conversionsDir . '/' . $data['id'] . '.json', json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
                self::processQueue($conversionsDir, $maxConcurrent);
                return $data['progress'];
            }
        }

        $logPath = $conversionsDir . '/' . $data['id'] . '.log';
        if (!file_exists($logPath)) return $data['progress'] ?? 0;

        $log = file_get_contents($logPath);
        if (preg_match_all('/time=(\d+):(\d+):(\d+)\.(\d+)/', $log, $m, PREG_SET_ORDER)) {
            $lastMatch = end($m);
            $parsed = intval($lastMatch[1]) * 3600 + intval($lastMatch[2]) * 60 + intval($lastMatch[3]) + intval($lastMatch[4]) / 100;
            $progress = min(99, round(($parsed / $data['totalDuration']) * 100));
            if ($progress > ($data['progress'] ?? 0)) {
                $data['progress'] = $progress;
                file_put_contents($conversionsDir . '/' . $data['id'] . '.json', json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
            }
        }
        return $data['progress'] ?? 0;
    }
}
