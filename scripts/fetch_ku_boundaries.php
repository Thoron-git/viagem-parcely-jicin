<?php

require_once __DIR__ . '/../src/Geo/PosListConverter.php';
require_once __DIR__ . '/../src/Wfs/WfsClient.php';
require_once __DIR__ . '/../src/Wfs/GmlZoningParser.php';

use App\Wfs\WfsClient;
use App\Wfs\GmlZoningParser;

$config = require __DIR__ . '/../config/config.php';

$client = new WfsClient($config['wfs']['cadastral_parcel_endpoint']);
$parser = new GmlZoningParser();

$features = [];

foreach ($config['cadastral_territories'] as $kuCode => $territory) {
    $xml = $client->getFeatureById('CZ.' . $kuCode);
    $coordinates = $parser->parseCoordinates($xml);

    $features[] = [
        'type' => 'Feature',
        'properties' => ['ku_code' => (string) $kuCode, 'name' => $territory['name']],
        'geometry' => [
            'type' => 'Polygon',
            'coordinates' => [$coordinates],
        ],
    ];

    echo "Stazeno: {$territory['name']}\n";
}

$geojson = [
    'type' => 'FeatureCollection',
    'features' => $features,
];

file_put_contents(__DIR__ . '/../public/data/ku_boundaries.geojson', json_encode($geojson));

echo "Ulozeno do public/data/ku_boundaries.geojson\n";
