<?php
require_once __DIR__ . '/config/bootstrap.php';
use App\Auth\Auth;
use App\Auth\Csrf;
use App\Media\Converter;
use App\Security\PathValidator;

header('Content-Type: application/json');
$currentUser = Auth::requireAuth();

if ($currentUser['role'] !== 'admin') {
    http_response_code(403);
    die(json_encode(['error' => 'Acceso denegado']));
}

$conversionsDir = STORAGE_DIR . '/conversions';
if (!is_dir($conversionsDir)) {
    @mkdir($conversionsDir, 0700, true);
}

$action = $_GET['action'] ?? '';
$action = $_POST['action'] ?? $action;
$maxConcurrent = 2;

if (in_array($action, ['start', 'delete', 'cancel'], true)) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405); die(json_encode(['error' => 'Método no permitido']));
    }
    $csrfToken = $_POST['csrf_token'] ?? ($_POST['_token'] ?? '');
    if (!Csrf::verify($csrfToken)) {
        http_response_code(403); die(json_encode(['error' => 'Token CSRF inválido']));
    }
}

if ($action === 'list') {
    Converter::processQueue($conversionsDir, $maxConcurrent);
    $conversions = [];
    $files = scandir($conversionsDir);
    foreach ($files as $f) {
        if (substr($f, -5) !== '.json') continue;
        $data = json_decode(file_get_contents($conversionsDir . '/' . $f), true);
        if ($data) {
            if ($data['status'] === 'running') {
                $data['progress'] = Converter::updateProgress($conversionsDir, $data, $maxConcurrent);
            }
            $conversions[] = $data;
        }
    }
    usort($conversions, function ($a, $b) {
        return ($b['startedAt'] ?? 0) - ($a['startedAt'] ?? 0);
    });
    echo json_encode($conversions);
    exit;
}

if ($action === 'status') {
    $id = preg_replace('/[^a-f0-9]/', '', $_GET['id'] ?? '');
    if (!$id) { http_response_code(400); die(json_encode(['error' => 'ID requerido'])); }
    $path = $conversionsDir . '/' . $id . '.json';
    if (!file_exists($path)) { http_response_code(404); die(json_encode(['error' => 'Conversión no encontrada'])); }
    $data = json_decode(file_get_contents($path), true);
    if ($data['status'] === 'running') {
        $data['progress'] = Converter::updateProgress($conversionsDir, $data, $maxConcurrent);
    }
    echo json_encode($data);
    exit;
}

if ($action === 'gentoken') {
    $file = $_GET['file'] ?? '';
    if (empty($file)) { http_response_code(400); die(json_encode(['error' => 'Archivo requerido'])); }
    $filePath = PathValidator::validateIn(MEDIA_ROOT, $file);
    if ($filePath === null || !is_file($filePath)) {
        http_response_code(404); die(json_encode(['error' => 'Archivo no encontrado']));
    }
    $token = Csrf::tokenGenerate($file);
    echo json_encode(['token' => $token]);
    exit;
}

if ($action === 'delete') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); die(json_encode(['error' => 'Método no permitido'])); }
    $id = preg_replace('/[^a-f0-9]/', '', $_POST['id'] ?? '');
    if (!$id) { http_response_code(400); die(json_encode(['error' => 'ID requerido'])); }
    @unlink($conversionsDir . '/' . $id . '.json');
    @unlink($conversionsDir . '/' . $id . '.log');
    echo json_encode(['ok' => true]);
    exit;
}

if ($action === 'cancel') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); die(json_encode(['error' => 'Método no permitido'])); }
    $id = preg_replace('/[^a-f0-9]/', '', $_POST['id'] ?? '');
    if (!$id) { http_response_code(400); die(json_encode(['error' => 'ID requerido'])); }
    $path = $conversionsDir . '/' . $id . '.json';
    if (!file_exists($path)) { http_response_code(404); die(json_encode(['error' => 'Conversión no encontrada'])); }
    $data = json_decode(file_get_contents($path), true);
    if ($data['status'] === 'running') {
        $pid = $data['pid'] ?? 0;
        if ($pid > 0 && file_exists("/proc/$pid")) {
            exec("kill $pid 2>/dev/null");
        }
        $outputPath = '';
        if (!empty($data['outputRelative'])) {
            $validated = PathValidator::validate(MEDIA_ROOT, $data['outputRelative']);
            $outputPath = $validated['path'];
        }
        if (!empty($outputPath) && file_exists($outputPath)) {
            @unlink($outputPath);
        }
    }
    $data['status'] = 'failed';
    $data['error'] = 'Cancelada por el usuario';
    $data['completedAt'] = time();
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
    @unlink($conversionsDir . '/' . $id . '.log');
    Converter::processQueue($conversionsDir, $maxConcurrent);
    echo json_encode(['ok' => true, 'status' => 'cancelled']);
    exit;
}

if ($action === 'start') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); die(json_encode(['error' => 'Método no permitido'])); }
    $relativeFile = $_POST['file'] ?? '';
    if (empty($relativeFile)) { http_response_code(400); die(json_encode(['error' => 'Archivo requerido'])); }

    $inputFile = PathValidator::validateIn(MEDIA_ROOT, $relativeFile);
    if (!$inputFile || !file_exists($inputFile) || !is_file($inputFile)) {
        http_response_code(400); die(json_encode(['error' => 'Archivo no encontrado']));
    }

    $fileBase = pathinfo($relativeFile, PATHINFO_FILENAME);
    if (substr($fileBase, -4) === '_web') { http_response_code(400); die(json_encode(['error' => 'Ya es conversión web'])); }

    $existing = scandir($conversionsDir);
    foreach ($existing as $f) {
        if (substr($f, -5) !== '.json') continue;
        $data = json_decode(file_get_contents($conversionsDir . '/' . $f), true);
        if ($data && $data['inputRelative'] === $relativeFile && in_array($data['status'], ['running', 'pending'])) {
            http_response_code(409);
            $label = $data['status'] === 'running' ? 'en curso' : 'en cola';
            die(json_encode(['error' => 'Ya hay una conversión ' . $label . ' para este archivo', 'id' => $data['id']]));
        }
    }

    $dir = dirname($inputFile);
    $baseName = pathinfo($relativeFile, PATHINFO_FILENAME);
    $outputRelative = $baseName . '_web.mp4';
    $outputFile = dirname($inputFile) . '/' . $outputRelative;

    if (file_exists($outputFile)) {
        http_response_code(409);
        die(json_encode(['error' => 'El archivo ' . $outputRelative . ' ya existe.']));
    }

    $escapedInput = escapeshellarg($inputFile);
    $durationCmd = "ffprobe -v error -show_entries format=duration -of csv=p=0 $escapedInput 2>/dev/null";
    $totalDuration = floatval(trim(shell_exec($durationCmd)));

    $id = substr(md5($relativeFile . microtime()), 0, 12);
    $statusData = [
        'id' => $id,
        'input' => basename($inputFile),
        'inputRelative' => $relativeFile,
        'output' => $outputRelative,
        'outputRelative' => (dirname($relativeFile) !== '.' ? dirname($relativeFile) . '/' : '') . $outputRelative,
        'status' => 'pending',
        'progress' => 0,
        'pid' => 0,
        'totalDuration' => $totalDuration,
        'startedAt' => time(),
        'completedAt' => null,
        'error' => null,
    ];
    file_put_contents($conversionsDir . '/' . $id . '.json', json_encode($statusData, JSON_PRETTY_PRINT), LOCK_EX);

    Converter::processQueue($conversionsDir, $maxConcurrent);
    $finalStatus = 'pending';
    $updated = json_decode(file_get_contents($conversionsDir . '/' . $id . '.json'), true);
    if ($updated && $updated['status'] === 'running') $finalStatus = 'running';

    echo json_encode(['id' => $id, 'status' => $finalStatus]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Acción no válida']);
