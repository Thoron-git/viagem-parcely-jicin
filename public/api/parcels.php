<?php

require_once __DIR__ . '/../../src/Db/Database.php';
require_once __DIR__ . '/../../src/Db/ParcelRepository.php';

use App\Db\Database;
use App\Db\ParcelRepository;

header('Content-Type: application/json');

$config = require __DIR__ . '/../../config/config.php';

$minLat = (float) ($_GET['minLat'] ?? 0);
$minLon = (float) ($_GET['minLon'] ?? 0);
$maxLat = (float) ($_GET['maxLat'] ?? 0);
$maxLon = (float) ($_GET['maxLon'] ?? 0);
$zoom = (int) ($_GET['zoom'] ?? 16);
$simplified = $zoom < 18;

$pdo = Database::connect($config['db']);
$repository = new ParcelRepository($pdo);
$rows = $repository->findInBbox($minLat, $minLon, $maxLat, $maxLon, $simplified);

$features = [];
foreach ($rows as $row) {
    $features[] = [
        'type' => 'Feature',
        'properties' => [
            'gml_id' => $row['gml_id'],
            'parcel_number' => $row['parcel_number'],
            'area_m2' => $row['area_m2'] !== null ? (int) $row['area_m2'] : null,
            'national_reference' => $row['national_reference'],
        ],
        'geometry' => json_decode($row['geometry_geojson'], true),
    ];
}

echo json_encode([
    'type' => 'FeatureCollection',
    'features' => $features,
]);
