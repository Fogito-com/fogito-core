<?php
return [
    'databases' => [
        'default' => [
            'host'     => '127.0.0.1',
            'port'     => 27017,
            'username' => null,
            'password' => null,
            'dbname'   => 'fogito',
        ],
    ],
    'skipped_filtering_collections' => [ // Will skip filtering collection in DBManager
        "companies", "logs_access"
    ],
    's2s'   => [
        'app_id'       => 215,
        'server_token' => 'TestServerKey',
        'timezone'     => 101,
    ],
];
