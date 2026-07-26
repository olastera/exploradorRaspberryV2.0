<?php
namespace App\System;

// Arranca/para el servicio systemd transmission-daemon desde el botón de
// Administración > Sistema. Consultar el estado no requiere privilegios, pero
// arrancar/parar sí: www-data tiene permiso sudo NOPASSWD acotado a estos dos
// comandos exactos (/etc/sudoers.d/explorador-transmission), nada más.
class TransmissionService
{
    private const SERVICE = 'transmission-daemon.service';

    public static function isActive()
    {
        $output = @shell_exec('systemctl is-active ' . escapeshellarg(self::SERVICE) . ' 2>/dev/null');
        return trim((string) $output) === 'active';
    }

    public static function start()
    {
        @shell_exec('sudo -n /usr/bin/systemctl start ' . escapeshellarg(self::SERVICE) . ' 2>&1');
        return self::isActive();
    }

    public static function stop()
    {
        @shell_exec('sudo -n /usr/bin/systemctl stop ' . escapeshellarg(self::SERVICE) . ' 2>&1');
        return !self::isActive();
    }
}
