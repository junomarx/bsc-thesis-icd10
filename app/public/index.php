<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Icd10Prototype\Bootstrap;
use Icd10Prototype\Http\ApiResult;
use Icd10Prototype\Http\JsonResponse;

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($path === '/api/health') {
    JsonResponse::send(200, ['status' => 'ok']);
}

try {
    $app = Bootstrap::fromEnvironment();
} catch (\Throwable $exception) {
    JsonResponse::send(500, ['error' => 'baseline_unavailable', 'message' => $exception->getMessage()]);
}

$result = match (true) {
    $method === 'GET' && $path === '/api/cases' => $app->caseController->list(),
    $method === 'GET' && (bool) preg_match('#^/api/cases/([^/]+)$#', $path, $m) => $app->caseController->show($m[1]),
    $method === 'POST' && (bool) preg_match('#^/api/cases/([^/]+)/evaluate$#', $path, $m) => $app->evaluationController->evaluate($m[1], decodeJsonBody()),
    default => new ApiResult(404, ['error' => 'not_found']),
};

JsonResponse::send($result->status, $result->body);

function decodeJsonBody(): mixed
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return null;
    }

    try {
        return json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
    } catch (\JsonException) {
        return null;
    }
}
