<?php
namespace App\Imdb;

class ImdbSearch
{
    public static function search($query)
    {
        if (empty(trim($query))) return ['found' => false];

        $cacheKey = md5('ca|' . strtolower(self::cleanQuery($query)));

        $cached = FileCache::get($cacheKey);
        if ($cached !== null) {
            if (FileCache::isComplete($cached) || empty($cached['imdb_id'])) {
                $migrated = self::withLocalPoster($cacheKey, $cached);
                if ($migrated !== $cached) FileCache::set($cacheKey, $migrated);
                return $migrated;
            }
            $enriched = self::withLocalPoster($cacheKey, self::enrichFromOmdb($cached));
            FileCache::set($cacheKey, $enriched);
            return $enriched;
        }

        $result = self::searchImdb($query);
        if ($result['found']) {
            $result = self::enrichFromOmdb($result);
        }
        $result = self::withLocalPoster($cacheKey, $result);
        FileCache::set($cacheKey, $result);
        return $result;
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
                    $mmUrl = "https://api.mymemory.translated.net/get?q=" . rawurlencode($plotToTranslate) . "&langpair=en|ca";
                    $mmResponse = @file_get_contents($mmUrl, false, $ctx);
                    if ($mmResponse !== false) {
                        $mmData = json_decode($mmResponse, true);
                        $translated = $mmData['responseData']['translatedText'] ?? '';
                        $quotaExceeded = (isset($mmData['responseStatus']) && (int) $mmData['responseStatus'] !== 200)
                            || stripos($translated, 'MYMEMORY WARNING') !== false;
                        if ($translated !== '' && !$quotaExceeded) {
                            $result['plot'] = $translated;
                            $result['plot_lang'] = 'ca';
                        }
                    }
                }
                $cacheKey = md5('ca|' . strtolower(self::cleanQuery($result['title'])));
                FileCache::set($cacheKey, $result);
            }
        }
        return $result;
    }
}
