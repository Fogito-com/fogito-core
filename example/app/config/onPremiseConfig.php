<?php
return [
    'app_url'                       => 'https://app.fogito.com',
    'prod_url'                      => 'https://app.fogito.com',
    'databases'                     => [
        'default' => [
            'host'     => '127.0.0.1',
            'port'     => 27017,
            //'username' => null,
            //'password' => null,
            'dbname'   => 'fogitoplus',
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
    'skip_filter_business_type'     => true,
    // boolean
    's2s'                           => [
        'app_id'       => 205,
        'server_token' => 'pr0DF0G1tOPlusMq3qw9kKj32hs9l2lkK90dMhzBNK2jF0K2Ld5S2hD02kdskl23Kksdj22309Fsak49fJasjMn',
        'timezone'     => 101,
    ],

    'recaptcha'         => [
        'required'   => true,
        'site_key'   => '6LcxICcjAAAAAKqrJPRIXHsfqs3aNDLv5QXiNjO_',
        'secret_key' => '6LcxICcjAAAAAHJXNTVjCyEmw4zPlQmk9DTaiPoM',
    ],
    'api_domain'        => 'http://fogitoplus.fogito.com',
    'accounting_domain' => 'http://invoices.fogito.com'
];
