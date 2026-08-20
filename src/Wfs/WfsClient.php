<?php

namespace App\Wfs;

class WfsClient
{
    public function __construct(private string $endpoint)
    {
    }

    public function getFeatures(array $bbox, int $count = 1000, int $startIndex = 0): string
    {
        [$minLat, $minLon, $maxLat, $maxLon] = $bbox;

        return $this->request([
            'SERVICE' => 'WFS',
            'VERSION' => '2.0.0',
            'REQUEST' => 'GetFeature',
            'TYPENAMES' => 'cp:CadastralParcel',
            'SRSNAME' => 'urn:ogc:def:crs:EPSG::4326',
            'BBOX' => "$minLat,$minLon,$maxLat,$maxLon",
            'COUNT' => $count,
            'STARTINDEX' => $startIndex,
        ]);
    }

    public function getFeatureById(string $id): string
    {
        return $this->request([
            'SERVICE' => 'WFS',
            'VERSION' => '2.0.0',
            'REQUEST' => 'GetFeature',
            'STOREDQUERY_ID' => 'urn:ogc:def:query:OGC-WFS::GetFeatureById',
            'ID' => $id,
            'SRSNAME' => 'urn:ogc:def:crs:EPSG::4326',
        ]);
    }

    private function request(array $params): string
    {
        $url = $this->endpoint . '?' . http_build_query($params);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            throw new \RuntimeException("WFS požadavek selhal: $error");
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode !== 200) {
            throw new \RuntimeException("WFS vrátilo neočekávaný HTTP status: $httpCode");
        }

        return $response;
    }
}
