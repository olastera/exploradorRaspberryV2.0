<?php
namespace App\Imdb;

class ImdbSearch
{
    // Usado por peticiones en vivo (imdb_search.php, disparadas desde el navegador
    // de cada usuario). Si ya hay algo en caché (aunque la traducción al catalán
    // siga pendiente) lo devuelve tal cual: nunca llama a IMDb/OMDb/MyMemory desde
    // aquí, para que cargar la galería no dependa de APIs externas lentas o con
    // cuota agotada. Esos reintentos los hace solo refresh(), en segundo plano.
    public static function search($query)
    {
        if (empty(trim($query))) return ['found' => false];

        $cacheKey = self::cacheKey($query);
        $cached = FileCache::get($cacheKey);
        if ($cached !== null) {
            return self::finalize($cacheKey, $cached);
        }

        return self::fetchAndCache($query, $cacheKey);
    }

    // Usado solo por bin/refresh_movies.php (job en segundo plano, vía cron o el
    // botón de Administración). A diferencia de search(), sí reintenta OMDb/la
    // traducción para entradas que están en caché pero incompletas (p.ej. porque
    // la cuota diaria de MyMemory estaba agotada la última vez).
    public static function refresh($query)
    {
        if (empty(trim($query))) return ['found' => false];

        $cacheKey = self::cacheKey($query);
        $cached = FileCache::get($cacheKey);
        if ($cached === null) {
            return self::fetchAndCache($query, $cacheKey);
        }
        if (FileCache::isComplete($cached) || empty($cached['imdb_id'])) {
            return self::finalize($cacheKey, $cached);
        }

        $enriched = self::enrichFromOmdb($cached);
        FileCache::set($cacheKey, $enriched);
        return self::finalize($cacheKey, $enriched);
    }

    private static function cacheKey($query)
    {
        return md5('ca|' . strtolower(self::cleanQuery($query)));
    }

    private static function fetchAndCache($query, $cacheKey)
    {
        $result = self::searchImdb($query);
        if ($result['found']) {
            $result = self::enrichFromOmdb($result);
        }
        $result = self::withLocalPoster($cacheKey, $result);
        FileCache::set($cacheKey, $result);
        return $result;
    }

    // Migra el póster a poster.php si hace falta y persiste el cambio.
    private static function finalize($cacheKey, $result)
    {
        $migrated = self::withLocalPoster($cacheKey, $result);
        if ($migrated !== $result) FileCache::set($cacheKey, $migrated);
        return $migrated;
    }

    // Sustituye la URL remota del póster por un proxy local (poster.php) para que
    // el navegador la pida a nuestro servidor y quede cacheada en disco (ver PosterCache).
    // El original se guarda en poster_source: poster.php lo necesita para descargar
    // la imagen la primera vez (o de nuevo si se vacía la caché de carátulas).
    private static function withLocalPoster($cacheKey, $result)
    {
        if (!empty($result['poster']) && strpos($result['poster'], 'poster.php?') !== 0) {
            $result['poster_source'] = $result['poster'];
            $result['poster'] = 'poster.php?key=' . $cacheKey;
        }
        return $result;
    }

    private static function cleanQuery($name)
    {
        $q = $name;
        if (preg_match('/\.[a-z0-9]{2,4}$/i', $q)) $q = preg_replace('/\.[a-z0-9]{2,4}$/i', '', $q);
        $q = preg_replace('/\[.*?\]/', ' ', $q);
        $q = preg_replace('/\(.*?\)/', ' ', $q);
        $q = preg_replace('/\b(1080p|720p|480p|4k|2160p|hdrip|brrip|dvdrip|webrip|webdl|hdtv|bluray|ac3|dts|x264|x265|hevc|aac|flac|mp3|subspanish|spanish|espanol|latino|dual|extended|unrated|directors.?cut|remux|bdrip|hdrip)\b/i', ' ', $q);
        $q = preg_replace('/[._\-]+/u', ' ', $q);
        $q = preg_replace('/[^\p{L}\p{N}\s]/u', '', $q);
        $q = preg_replace('/\s+/', ' ', trim($q));
        return $q;
    }

    private static function searchImdb($query)
    {
        $cleanQuery = self::cleanQuery($query);
        if (empty($cleanQuery)) return ['found' => false];

        $ctx = stream_context_create([
            'http' => ['timeout' => 5, 'follow_location' => true, 'ignore_errors' => true,
                'header' => "Accept: application/json\r\nUser-Agent: Mozilla/5.0\r\n"],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]
        ]);

        $encodedQuery = rawurlencode($cleanQuery);
        $firstLetter = strtolower($cleanQuery[0]);
        if (!preg_match('/[a-z0-9]/', $firstLetter)) $firstLetter = 'a';

        $url = "https://v2.sg.media-imdb.com/suggestion/{$firstLetter}/{$encodedQuery}.json";
        $response = @file_get_contents($url, false, $ctx);

        if ($response === false) return ['found' => false];
        $data = json_decode($response, true);
        if (!isset($data['d']) || empty($data['d'])) return ['found' => false];

        $movie = null;
        foreach ($data['d'] as $item) {
            if (isset($item['q']) && in_array($item['q'], ['feature', 'movie', 'tvMovie'])) {
                $movie = $item; break;
            }
        }
        if (!$movie && !empty($data['d'])) $movie = $data['d'][0];

        return [
            'found' => true,
            'title' => $movie['l'] ?? '',
            'year' => $movie['y'] ?? null,
            'poster' => $movie['i']['imageUrl'] ?? null,
            'imdb_id' => $movie['id'] ?? null,
            'imdb_url' => isset($movie['id']) ? 'https://www.imdb.com/title/' . $movie['id'] : null
        ];
    }

    private static function enrichFromOmdb($result)
    {
        if (empty($result['imdb_id'])) return $result;

        $ctx = stream_context_create([
            'http' => ['timeout' => 5, 'follow_location' => true, 'ignore_errors' => true,
                'header' => "Accept: application/json\r\nUser-Agent: Mozilla/5.0\r\n"],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]
        ]);

        if (OMDB_API_KEY === '') return $result;
        $omdbUrl = "https://www.omdbapi.com/?i=" . rawurlencode($result['imdb_id']) . "&apikey=" . rawurlencode(OMDB_API_KEY) . "&plot=full";
        $omdbResponse = @file_get_contents($omdbUrl, false, $ctx);
        if ($omdbResponse !== false) {
            $omdbData = json_decode($omdbResponse, true);
            if (!empty($omdbData['Response']) && $omdbData['Response'] === 'True') {
                $result['plot'] = $omdbData['Plot'] ?? '';
                $result['rating'] = $omdbData['imdbRating'] ?? '';
                $result['genre'] = $omdbData['Genre'] ?? '';
                $result['runtime'] = $omdbData['Runtime'] ?? '';
                $result['year'] = $omdbData['Year'] ?? $result['year'];
                if (empty($result['poster']) && !empty($omdbData['Poster']) && $omdbData['Poster'] !== 'N/A') {
                    $result['poster'] = $omdbData['Poster'];
                }

                if (!empty($result['plot'])) {
                    $result['plot_lang'] = 'en';
                    $plotToTranslate = substr($result['plot'], 0, 500);
                    $translated = self::translateToCatalan($plotToTranslate, $ctx);
                    if ($translated !== null) {
                        $result['plot'] = $translated;
                        $result['plot_lang'] = 'ca';
                    }
                }
                $cacheKey = md5('ca|' . strtolower(self::cleanQuery($result['title'])));
                FileCache::set($cacheKey, $result);
            }
        }
        return $result;
    }

    // Traduce al català con el endpoint no oficial de Google Translate primero
    // (sin API key, sin cuota diaria visible para este volumen de uso: unas
    // pocas películas nuevas al día). MyMemory queda como reserva por si Google
    // fallara — su cuota gratis (~5.000 palabras/día) se agotaba casi a diario
    // usándolo como único traductor, así que ya no es la vía principal.
    private static function translateToCatalan($text, $ctx)
    {
        $translated = self::translateViaGoogle($text, $ctx);
        if ($translated !== null) return $translated;
        return self::translateViaMyMemory($text, $ctx);
    }

    private static function translateViaGoogle($text, $ctx)
    {
        $url = 'https://translate.googleapis.com/translate_a/single?client=gtx&sl=en&tl=ca&dt=t&q=' . rawurlencode($text);
        $response = @file_get_contents($url, false, $ctx);
        if ($response === false) return null;

        $data = json_decode($response, true);
        if (!is_array($data) || empty($data[0]) || !is_array($data[0])) return null;

        $translated = '';
        foreach ($data[0] as $segment) {
            if (isset($segment[0])) $translated .= $segment[0];
        }
        return $translated !== '' ? $translated : null;
    }

    private static function translateViaMyMemory($text, $ctx)
    {
        $mmUrl = 'https://api.mymemory.translated.net/get?q=' . rawurlencode($text) . '&langpair=en|ca';
        $mmResponse = @file_get_contents($mmUrl, false, $ctx);
        if ($mmResponse === false) return null;

        $mmData = json_decode($mmResponse, true);
        $translated = $mmData['responseData']['translatedText'] ?? '';
        $quotaExceeded = (isset($mmData['responseStatus']) && (int) $mmData['responseStatus'] !== 200)
            || stripos($translated, 'MYMEMORY WARNING') !== false;
        return ($translated !== '' && !$quotaExceeded) ? $translated : null;
    }
}
