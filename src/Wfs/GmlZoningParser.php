<?php

namespace App\Wfs;

use App\Geo\PosListConverter;

class GmlZoningParser
{
    private const GML_NS = 'http://www.opengis.net/gml/3.2';

    public function parseCoordinates(string $gmlXml): array
    {
        $xml = new \SimpleXMLElement($gmlXml);
        $xml->registerXPathNamespace('gml', self::GML_NS);

        $posListNodes = $xml->xpath('//gml:posList');
        $posList = (string) $posListNodes[0];

        return PosListConverter::toCoordinates($posList);
    }
}
