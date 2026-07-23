<?php
namespace App\Auth;

class Csrf
{
    public static function token()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verify($token)
    {
        return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function tokenGenerate($filePath)
    {
        $tokenDir = STORAGE_DIR . '/tokens';
        if (!is_dir($tokenDir)) {
            @mkdir($tokenDir, 0700, true);
        }
        $token = bin2hex(random_bytes(16));
        $data = json_encode([
            'file' => $filePath,
            'expires' => time() + 7200
        ]);
        file_put_contents("$tokenDir/$token.json", $data, LOCK_EX);
        return $token;
    }

    public static function tokenVerify($token)
    {
        if (!is_string($token) || !preg_match('/\A[a-f0-9]{32}\z/D', $token)) {
            return null;
        }
        $path = STORAGE_DIR . '/tokens/' . $token . '.json';
        if (!file_exists($path)) return null;
        $data = json_decode(file_get_contents($path), true);
        if (!$data || $data['expires'] < time()) {
            @unlink($path);
            return null;
        }
        return $data['file'];
    }
}
