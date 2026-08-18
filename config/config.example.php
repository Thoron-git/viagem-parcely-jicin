<?php

return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'viagem_parcely',
        'user' => 'root',
        'password' => '',
    ],
    'wfs' => [
        'cadastral_parcel_endpoint' => 'https://services.cuzk.gov.cz/wfs/inspire-cp-wfs.asp',
    ],
    'cadastral_territories' => [
        '659541' => 'Jičín',
        '740225' => 'Robousy',
        '725838' => 'Popovice u Jičína',
        '776530' => 'Valdice',
    ],
    'cache_ttl_seconds' => 60 * 60 * 24 * 30,
];
