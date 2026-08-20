<?php

require_once __DIR__ . '/../src/Geo/PosListConverter.php';
require_once __DIR__ . '/../src/Geo/DouglasPeucker.php';
require_once __DIR__ . '/../src/Wfs/WfsClient.php';
require_once __DIR__ . '/../src/Wfs/GmlParcelParser.php';
require_once __DIR__ . '/../src/Wfs/BboxTiler.php';
require_once __DIR__ . '/../src/Db/Database.php';
require_once __DIR__ . '/../src/Db/ParcelRepository.php';
require_once __DIR__ . '/../src/Db/KuCacheStatusRepository.php';

use App\Wfs\WfsClient;
use App\Wfs\GmlParcelParser;
use App\Wfs\BboxTiler;
use App\Db\Database;
use App\Db\ParcelRepository;
use App\Db\KuCacheStatusRepository;

$config = require __DIR__ . '/../config/config.php';

$client = new WfsClient($config['wfs']['cadastral_parcel_endpoint']);
$parser = new GmlParcelParser();
$tiler = new BboxTiler();

$pdo = Database::connect($config['db']);
$parcelRepository = new ParcelRepository($pdo);
$statusRepository = new KuCacheStatusRepository($pdo);

foreach ($config['cadastral_territories'] as $kuCode => $territory) {
    [$minLat, $minLon, $maxLat, $maxLon] = $territory['bbox'];
    $tiles = $tiler->tiles($minLat, $minLon, $maxLat, $maxLon);

    echo "=== {$territory['name']} ({$kuCode}): " . count($tiles) . " dlaždic ===\n";
    $totalSaved = 0;

    foreach ($tiles as $i => [$tMinLat, $tMinLon, $tMaxLat, $tMaxLon]) {
        $xml = $client->getFeatures([$tMinLat, $tMinLon, $tMaxLat, $tMaxLon], 2000);
        $parcels = $parser->parse($xml);
        $parcels = array_filter($parcels, fn($p) => $p['ku_code'] === (string) $kuCode);


        if (count($parcels) > 0) {
            $parcelRepository->upsertMany($parcels);
        }

        $totalSaved += count($parcels);
        echo "  dlaždice " . ($i + 1) . "/" . count($tiles) . ": " . count($parcels) . " parcel\n";
    }

    $statusRepository->markSynced($kuCode, $territory['name'], $totalSaved);
    echo "Hotovo: {$totalSaved} parcel pro {$territory['name']}.\n\n";
}

echo "Synchronizace dokoncena.\n";
