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

$since = (new DateTime('now', new DateTimeZone('UTC')))
    ->modify('-1 day')
    ->format(DateTime::ATOM);

$service->reconcileSince($since);
echo "Reconcile complete\n";
