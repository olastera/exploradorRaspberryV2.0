<?php
require_once __DIR__ . '/config/bootstrap.php';
use App\Imdb\ImdbSearch;

header('Content-Type: application/json');
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
if (empty($query)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing query parameter q']);
    exit;
}

$result = ImdbSearch::search($query);
$browserTtl = \App\Imdb\FileCache::isComplete($result) ? 2592000 : 3600;
header('Cache-Control: public, max-age=' . $browserTtl);
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
