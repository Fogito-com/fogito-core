<?php
return [
    '/:module/:controller/:action/:params' => [
        'module'     => 1,
        'controller' => 2,
        'action'     => 3,
        'params'     => 4,
    ],
    '/:module/:controller/:action'         => [
        'module'     => 1,
        'controller' => 2,
        'action'     => 3,
    ],
    '/:module/:controller'                 => [
        'module'     => 1,
        'controller' => 2,
        'action'     => 'index',
    ],
    '/:module'                             => [
        'module'     => 1,
        'controller' => 'index',
        'action'     => 'index',
    ],
];
