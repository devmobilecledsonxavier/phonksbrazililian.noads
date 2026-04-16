<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Método não permitido. Use GET.'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Reject cross-origin requests when origin or referer headers are present and do not match the current host.
 */
function isTrustedRequest(): bool
{
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($host === '') {
        return true;
    }

    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin !== '') {
        $originHost = parse_url($origin, PHP_URL_HOST);
        if (!is_string($originHost) || strcasecmp($originHost, $host) !== 0) {
            return false;
        }
    }

    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if ($referer !== '') {
        $refererHost = parse_url($referer, PHP_URL_HOST);
        if (!is_string($refererHost) || strcasecmp($refererHost, $host) !== 0) {
            return false;
        }
    }

    return true;
}

if (!isTrustedRequest()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Requisição bloqueada por política de origem.'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function fail(string $message, int $statusCode = 500): never
{
    http_response_code($statusCode);
    echo json_encode([
        'success' => false,
        'error' => $message
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function isValidYouTubeId(string $videoId): bool
{
    return (bool) preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId);
}

$databasePath = dirname(__DIR__) . '/src/database.json';

if (!is_file($databasePath)) {
    fail('Banco de dados musical não encontrado.', 500);
}

$rawJson = file_get_contents($databasePath);
if ($rawJson === false) {
    fail('Não foi possível ler o banco de dados musical.', 500);
}

try {
    /** @var array<string, mixed> $database */
    $database = json_decode($rawJson, true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $exception) {
    fail('JSON inválido no banco de dados musical.', 500);
}

$artists = $database['artists'] ?? null;
if (!is_array($artists)) {
    fail('Estrutura do banco de dados inválida: artists ausente.', 500);
}

$groupedArtists = [];
$flattenedSongs = [];
$artistCount = 0;

foreach ($artists as $artistIndex => $artistData) {
    if (!is_array($artistData)) {
        fail("Artista inválido na posição {$artistIndex}.", 500);
    }

    $artistName = trim((string) ($artistData['name'] ?? ''));
    $artistId = trim((string) ($artistData['id'] ?? ''));
    $songs = $artistData['songs'] ?? null;

    if ($artistName === '' || $artistId === '' || !is_array($songs)) {
        fail("Estrutura inválida para o artista na posição {$artistIndex}.", 500);
    }

    $normalizedSongs = [];

    foreach ($songs as $songIndex => $songData) {
        if (!is_array($songData)) {
            fail("Música inválida em {$artistName} na posição {$songIndex}.", 500);
        }

        $title = trim((string) ($songData['title'] ?? ''));
        $youtubeId = trim((string) ($songData['youtubeId'] ?? ''));

        if ($title === '' || !isValidYouTubeId($youtubeId)) {
            fail("Dados inválidos em {$artistName} -> música {$songIndex}.", 500);
        }

        $song = [
            'title' => $title,
            'artist' => $artistName,
            'artistId' => $artistId,
            'youtubeId' => $youtubeId,
        ];

        $normalizedSongs[] = $song;
        $flattenedSongs[] = $song;
    }

    $groupedArtists[] = [
        'id' => $artistId,
        'name' => $artistName,
        'songs' => $normalizedSongs,
    ];

    $artistCount++;
}

$response = [
    'success' => true,
    'meta' => [
        'version' => (string) ($database['version'] ?? 'noAds 1.1'),
        'artistCount' => $artistCount,
        'songCount' => count($flattenedSongs),
    ],
    'artists' => $groupedArtists,
    'songs' => $flattenedSongs,
];

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
