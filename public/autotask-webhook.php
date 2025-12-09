<?php
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Sync\Clients\AutotaskClient;
use Sync\Clients\GHLClient;
use Sync\Services\Mapper;
use Sync\Services\SyncService;
use Sync\Utils\Logger;

Dotenv::createImmutable(__DIR__ . '/..')->load();

$log = new Logger($_ENV['LOG_PATH'] ?? './sync.log');

$at = new AutotaskClient(
    $_ENV['AT_BASE_URI'],
    $_ENV['AT_USERNAME'],
    $_ENV['AT_SECRET'],
    $_ENV['AT_INTEGRATION_CODE']
);

$ghl = new GHLClient($_ENV['GHL_BASE_URI'], $_ENV['GHL_TOKEN']);

$service = new SyncService(
    $at,
    $ghl,
    new Mapper(),
    $log,
    $_ENV['GHL_LOCATION_ID'],
    filter_var($_ENV['DRY_RUN'] ?? 'false', FILTER_VALIDATE_BOOL)
);

$payload = json_decode(file_get_contents('php://input'), true) ?? [];
$entityType = $payload['entityType'] ?? null;
$entityId   = $payload['entityId'] ?? null;

if (!$entityType || !$entityId) {
    http_response_code(400);
    echo json_encode(['error'=>'Missing entityType/entityId']);
    exit;
}

try {
    if ($entityType === 'Company') {
        $co = $at->getCompany((int)$entityId);
        $service->upsertCompany($co);
    } elseif ($entityType === 'Contact') {
        $ct = $at->getContact((int)$entityId);
        $service->upsertContact($ct);
    } else {
        $log->warn("Unhandled entityType", ['entityType'=>$entityType]);
    }

    http_response_code(200);
    echo json_encode(['ok'=>true]);
} catch (Throwable $e) {
    $log->error("Webhook sync failed", ['err'=>$e->getMessage(), 'payload'=>$payload]);
    http_response_code(500);
    echo json_encode(['error'=>'sync failed']);
}
