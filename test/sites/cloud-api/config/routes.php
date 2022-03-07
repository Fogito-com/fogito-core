<?php
return [
    '/:controller/:action/:params' => [
        'controller' => 1,
        'action'     => 2,
        'params'     => 3,
    ],
    '/:controller/:action'         => [
        'controller' => 1,
        'action'     => 2,
    ],
    '/:controller'                 => [
        'controller' => 1,
    ],
];
