<?php
return [
    '/:module/:controller/{category_id:([a-z0-9]{24}+)}' => [
        'module'     => 1,
        'controller' => 2,
    ],
    '/:module/:controller/:action/:params'               => [
        'module'     => 1,
        'controller' => 2,
        'action'     => 3,
        'params'     => 4,
    ],
    '/:module/:controller/:action'                       => [
        'module'     => 1,
        'controller' => 2,
        'action'     => 3,
    ],
    '/:module/:controller'                               => [
        'module'     => 1,
        'controller' => 2,
    ],
    '/:module'                                           => [
        'module' => 1,
    ],
];
