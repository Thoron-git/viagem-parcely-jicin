<?php

namespace App\Geo;

class PosListConverter
{
    /** @return array<int, array{0: float, 1: float}> pole dvojic [lon, lat] */
    public static function toCoordinates(string $posList): array
    {
        $numbers = preg_split('/\s+/', trim($posList));
        $coordinates = [];

        // posList je v poradi "lat lon" (EPSG:4326), chceme "lon lat" - proto prohazujeme.
        for ($i = 0; $i < count($numbers); $i += 2) {
            $lat = (float) $numbers[$i];
            $lon = (float) $numbers[$i + 1];
            $coordinates[] = [$lon, $lat];
        }

        return $coordinates;
    }

    public static function toWkt(string $posList): string
    {
        $points = array_map(
            fn(array $point) => $point[0] . ' ' . $point[1],
            self::toCoordinates($posList)
        );

        return self::coordinatesToWkt(self::toCoordinates($posList));
    }

    public static function coordinatesToWkt(array $coordinates): string
    {
        $points = array_map(
            fn(array $point) => $point[0] . ' ' . $point[1],
            $coordinates
        );

        return 'POLYGON((' . implode(', ', $points) . '))';
    }
}
