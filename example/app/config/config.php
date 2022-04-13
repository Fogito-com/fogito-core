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
        'app_id'       => 205,
        'server_token' => 'F0G1tOPlusMq3qw9kKj32hs9l2lkK90dMhzBN7cbmKL2lJ3223kdkPAaQ3RGVX3fqd23klMk93Hd9We3Lk4',
        'timezone'     => 101,
    ],
];
