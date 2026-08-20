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
        '659541' => ['name' => 'Jičín', 'bbox' => [50.418351, 15.328421, 50.459792, 15.402589]],
        '740225' => ['name' => 'Robousy', 'bbox' => [50.409878, 15.381310, 50.444400, 15.427923]],
        '725838' => ['name' => 'Popovice u Jičína', 'bbox' => [50.402635, 15.353114, 50.425858, 15.399353]],
        '776530' => ['name' => 'Valdice', 'bbox' => [50.449331, 15.378328, 50.459783, 15.405398]],
    ],

    'cache_ttl_seconds' => 60 * 60 * 24 * 30,
];
