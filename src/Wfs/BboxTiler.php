<?php

namespace App\Wfs;

class BboxTiler
{
    public function __construct(private float $tileSizeDegrees = 0.005)
    {
    }

    /** @return array<int, array{0: float, 1: float, 2: float, 3: float}> */
    public function tiles(float $minLat, float $minLon, float $maxLat, float $maxLon): array
    {
        $tiles = [];

        for ($lat = $minLat; $lat < $maxLat; $lat += $this->tileSizeDegrees) {
            for ($lon = $minLon; $lon < $maxLon; $lon += $this->tileSizeDegrees) {
                $tiles[] = [
                    $lat,
                    $lon,
                    min($lat + $this->tileSizeDegrees, $maxLat),
                    min($lon + $this->tileSizeDegrees, $maxLon),
                ];
            }
        }

        return $tiles;
    }
}
