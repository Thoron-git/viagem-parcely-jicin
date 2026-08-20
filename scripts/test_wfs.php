<?php

require_once __DIR__ . '/../src/Geo/PosListConverter.php';
require_once __DIR__ . '/../src/Geo/DouglasPeucker.php';
require_once __DIR__ . '/../src/Wfs/WfsClient.php';
require_once __DIR__ . '/../src/Wfs/GmlParcelParser.php';
require_once __DIR__ . '/../src/Db/Database.php';
require_once __DIR__ . '/../src/Db/ParcelRepository.php';

use App\Wfs\WfsClient;
use App\Wfs\GmlParcelParser;
use App\Db\Database;
use App\Db\ParcelRepository;

$config = require __DIR__ . '/../config/config.php';

$client = new WfsClient($config['wfs']['cadastral_parcel_endpoint']);
$xml = $client->getFeatures([50.434, 15.350, 50.437, 15.356]);

$parser = new GmlParcelParser();
$parcels = $parser->parse($xml);

$pdo = Database::connect($config['db']);
$repository = new ParcelRepository($pdo);
$repository->upsertMany($parcels);

echo "Uloženo " . count($parcels) . " parcel.\n";

