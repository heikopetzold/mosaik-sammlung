<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Classes\MosaicRepository;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function badRequest(string $message): void
{
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => $message,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function notFound(string $message = 'Not found'): void
{
    http_response_code(404);
    echo json_encode([
        'ok' => false,
        'error' => $message,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function parseConditionImages($value): array
{
    if ($value === null) {
        return [];
    }
    $s = trim((string) $value);
    if ($s === '') {
        return [];
    }
    if (str_starts_with($s, '[')) {
        $decoded = json_decode($s, true);
        if (is_array($decoded)) {
            return array_values(array_filter(array_map('strval', $decoded), static fn($p) => $p !== ''));
        }
        return [];
    }
    return [$s];
}

function normalizeMosaic(array $row): array
{
    // Keep original columns but provide a normalized array too.
    $row['image_path_current_condition_images'] = parseConditionImages($row['image_path_current_condition'] ?? null);
    return $row;
}

$repo = new MosaicRepository();

$uuid = isset($_GET['uuid']) ? trim((string) $_GET['uuid']) : '';

if ($uuid !== '') {
    try {
        $row = $repo->findByUuid($uuid);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'ok' => false,
            'error' => 'Database error',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
    if (!$row) {
        notFound('Mosaic not found');
    }

    echo json_encode([
        'ok' => true,
        'data' => normalizeMosaic($row),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// List endpoint with optional filters.
$allowed = [
    'type',
    'category',
    'title',
    'issue_number',
    'main_serie',
    'serie',
    'availability',
    'item_condition',
    'release_year',
];

$filters = [];
foreach ($allowed as $key) {
    if (!array_key_exists($key, $_GET)) {
        continue;
    }
    $v = $_GET[$key];
    if ($v === null) {
        continue;
    }
    $v = is_string($v) ? trim($v) : $v;
    if ($v === '') {
        continue;
    }
    $filters[$key] = $v;
}

// Guard: at least one filter should be present for list queries (prevents dumping full DB accidentally).
if (empty($filters)) {
    badRequest('Provide either uuid or at least one filter parameter.');
}

try {
    $rows = $repo->getFiltered($filters, 'ASC');
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Database error',
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'ok' => true,
    'count' => count($rows),
    'data' => array_map('normalizeMosaic', $rows),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
