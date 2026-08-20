<?php

namespace App\Geo;

class DouglasPeucker
{
    /**
     * @param array<int, array{0: float, 1: float}> $points
     * @return array<int, array{0: float, 1: float}>
     */
    public static function simplify(array $points, float $tolerance): array
    {
        if (count($points) < 3) {
            return $points;
        }

        $first = $points[0];
        $last = $points[count($points) - 1];

        $maxDistance = 0.0;
        $maxIndex = 0;

        for ($i = 1; $i < count($points) - 1; $i++) {
            $distance = self::perpendicularDistance($points[$i], $first, $last);

            if ($distance > $maxDistance) {
                $maxDistance = $distance;
                $maxIndex = $i;
            }
        }

        if ($maxDistance <= $tolerance) {
            return [$first, $last];
        }

        $left = self::simplify(array_slice($points, 0, $maxIndex + 1), $tolerance);
        $right = self::simplify(array_slice($points, $maxIndex), $tolerance);

        array_pop($left); // posledni bod $left je stejny jako prvni bod $right

        return array_merge($left, $right);
    }

    private static function perpendicularDistance(array $point, array $lineStart, array $lineEnd): float
    {
        $dx = $lineEnd[0] - $lineStart[0];
        $dy = $lineEnd[1] - $lineStart[1];

        $segmentLength = sqrt($dx * $dx + $dy * $dy);

        if ($segmentLength == 0.0) {
            $pdx = $point[0] - $lineStart[0];
            $pdy = $point[1] - $lineStart[1];
            return sqrt($pdx * $pdx + $pdy * $pdy);
        }

        $numerator = abs($dx * ($lineStart[1] - $point[1]) - ($lineStart[0] - $point[0]) * $dy);

        return $numerator / $segmentLength;
    }
}
