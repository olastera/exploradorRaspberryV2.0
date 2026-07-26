<?php
namespace App\Imdb;

class FileCache
{
    // Una vez tenemos datos de OMDb (found=true) no hace falta volver a pedirlos:
    // el título, póster, sinopsis, etc. de una película no cambian. Que la
    // traducción al catalán siga pendiente (plot_lang='en', p.ej. por cuota de
    // MyMemory agotada) no debe tirar la caché ni obligar a repetir las llamadas
    // a IMDb/OMDb en cada visita: eso lo reintenta solo el job en segundo plano
    // (bin/refresh_movies.php vía ImdbSearch::refresh()), nunca una petición en vivo.
    const FOUND_TTL = 2592000; // 30 days
    const MISS_TTL = 3600; // Retry failed searches after 1 hour

    public static function get($key)
    {
        $file = STORAGE_DIR . '/cache/imdb/' . $key . '.json';
        if (!file_exists($file)) return null;
        $data = json_decode(file_get_contents($file), true);
        if (!is_array($data)) {
            @unlink($file);
            return null;
        }

        $ttl = self::ttlFor($data);
        if ((time() - filemtime($file)) > $ttl) {
            @unlink($file);
            return null;
        }
        return $data;
    }

    public static function set($key, $data)
    {
        $dir = STORAGE_DIR . '/cache/imdb';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        file_put_contents(
            $dir . '/' . $key . '.json',
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            LOCK_EX
        );
    }

    // "Completo" = ya tenemos también la traducción al catalán. Solo lo usa
    // ImdbSearch::refresh() (el job en segundo plano) para decidir si merece la
    // pena reintentar la traducción; NO afecta al TTL de la caché (ver FOUND_TTL).
    public static function isComplete($data)
    {
        return !empty($data['found'])
            && !empty($data['title'])
            && !empty($data['poster'])
            && !empty($data['plot'])
            && ($data['plot_lang'] ?? 'ca') === 'ca';
    }

    public static function clear()
    {
        $count = 0;
        foreach (glob(STORAGE_DIR . '/cache/imdb/*.json') as $file) {
            if (is_file($file) && @unlink($file)) $count++;
        }
        return $count;
    }

    private static function ttlFor($data)
    {
        return empty($data['found']) ? self::MISS_TTL : self::FOUND_TTL;
    }
}
