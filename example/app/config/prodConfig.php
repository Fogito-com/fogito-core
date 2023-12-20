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

    'cache_server'                  => 'server1',
    'cache_servers'                 => [
        'server1' => [
            'type' => 'memcache',
            'host' => 'localhost',
            'port' => 11211
        ],
        'server2'    => [
            'type'     => 'redis',
            'host'     => '127.0.0.1',
            'port'     => 6379,
            'username' => false,
            'password' => false
        ]
    ],

    'skipped_filtering_collections' => [ // Will skip filtering collection in DBManager
        "companies",
        "logs_access"
    ],
    'skip_filter_business_type' => false, // boolean
    'skip_filter_company_id' => false, // boolean
    's2s'   => [
        'app_id'       => 215,
        'server_token' => 'TestServerKey',
        'timezone'     => 101,
    ],
];
