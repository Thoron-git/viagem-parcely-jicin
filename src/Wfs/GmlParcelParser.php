<?php

namespace App\Wfs;

class GmlParcelParser
{
    private const CP_NS = 'http://inspire.ec.europa.eu/schemas/cp/4.0';
    private const GML_NS = 'http://www.opengis.net/gml/3.2';

    public function parse(string $gmlXml): array
    {
        $xml = new \SimpleXMLElement($gmlXml);
        $xml->registerXPathNamespace('cp', self::CP_NS);
        $xml->registerXPathNamespace('gml', self::GML_NS);

        $parcels = [];

        foreach ($xml->xpath('//cp:CadastralParcel') as $feature) {
            $feature->registerXPathNamespace('gml', self::GML_NS);

            $gmlId = (string) $feature->attributes(self::GML_NS)->id;

            $cp = $feature->children(self::CP_NS);
            $label = (string) $cp->label;
            $areaValue = (string) $cp->areaValue;
            $nationalReference = (string) $cp->nationalCadastralReference;

            $posListNodes = $feature->xpath('.//gml:posList');
            $posList = (string) $posListNodes[0];

            [$kuCode] = explode('-', $nationalReference, 2);

            $coordinates = \App\Geo\PosListConverter::toCoordinates($posList);

            $parcels[] = [
                'gml_id' => $gmlId,
                'ku_code' => $kuCode,
                'parcel_number' => $label,
                'national_reference' => $nationalReference,
                'area_m2' => $areaValue !== '' ? (int) round((float) $areaValue) : null,
                'geometry_wkt' => \App\Geo\PosListConverter::coordinatesToWkt($coordinates),
                'coordinates' => $coordinates,
            ];
        }

        return $parcels;
    }
}
