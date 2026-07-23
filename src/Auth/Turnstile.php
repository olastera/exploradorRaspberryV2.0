<?php
namespace App\Auth;

class Turnstile
{
    public static function verify($response, $ip = '')
    {
        if (empty($response)) return false;
        if (empty(TURNSTILE_SECRET_KEY)) return true;

        $data = http_build_query([
            'secret' => TURNSTILE_SECRET_KEY,
            'response' => $response,
            'remoteip' => $ip ?: ($_SERVER['REMOTE_ADDR'] ?? ''),
        ]);
        $ctx = stream_context_create(['http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $data,
        ]]);
        $result = @file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, $ctx);
        if ($result === false) return false;
        $json = json_decode($result, true);
        return !empty($json['success']);
    }
}
