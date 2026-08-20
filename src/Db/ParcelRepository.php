<?php

namespace App\Db;

use App\Geo\DouglasPeucker;
use App\Geo\PosListConverter;

class ParcelRepository
{
    private const SIMPLIFY_TOLERANCE_DEGREES = 0.00002;

    public function __construct(private \PDO $pdo) {}

    public function upsertMany(array $parcels): void
    {
        $sql = 'INSERT INTO parcels
                    (gml_id, ku_code, parcel_number, national_reference, area_m2, geom_full, geom_simplified, fetched_at)
                VALUES
                    (:gml_id, :ku_code, :parcel_number, :national_reference, :area_m2,
                     ST_GeomFromText(:geom_full, 4326), ST_GeomFromText(:geom_simplified, 4326), :fetched_at)
                ON DUPLICATE KEY UPDATE
                    parcel_number = VALUES(parcel_number),
                    national_reference = VALUES(national_reference),
                    area_m2 = VALUES(area_m2),
                    geom_full = VALUES(geom_full),
                    geom_simplified = VALUES(geom_simplified),
                    fetched_at = VALUES(fetched_at)';

        $statement = $this->pdo->prepare($sql);
        $now = date('Y-m-d H:i:s');

        foreach ($parcels as $parcel) {
            $simplifiedCoordinates = DouglasPeucker::simplify($parcel['coordinates'], self::SIMPLIFY_TOLERANCE_DEGREES);
            $simplifiedWkt = PosListConverter::coordinatesToWkt($simplifiedCoordinates);

            $statement->execute([
                'gml_id' => $parcel['gml_id'],
                'ku_code' => $parcel['ku_code'],
                'parcel_number' => $parcel['parcel_number'],
                'national_reference' => $parcel['national_reference'],
                'area_m2' => $parcel['area_m2'],
                'geom_full' => $parcel['geometry_wkt'],
                'geom_simplified' => $simplifiedWkt,
                'fetched_at' => $now,
            ]);
        }
    }

    public function findInBbox(float $minLat, float $minLon, float $maxLat, float $maxLon, bool $simplified = false): array
    {
        $bboxWkt = sprintf(
            'POLYGON((%f %f, %f %f, %f %f, %f %f, %f %f))',
            $minLon,
            $minLat,
            $maxLon,
            $minLat,
            $maxLon,
            $maxLat,
            $minLon,
            $maxLat,
            $minLon,
            $minLat
        );

        $geomColumn = $simplified ? 'geom_simplified' : 'geom_full';

        $sql = "SELECT gml_id, parcel_number, area_m2, national_reference,
                   ST_AsGeoJSON({$geomColumn}) AS geometry_geojson
            FROM parcels
            WHERE ST_Intersects({$geomColumn}, ST_GeomFromText(:bbox_wkt, 4326))";

        $statement = $this->pdo->prepare($sql);
        $statement->execute(['bbox_wkt' => $bboxWkt]);

        return $statement->fetchAll();
    }
}
