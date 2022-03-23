<?php
define('URL', 'https://example.com');
define('API_URL', 'https://api.example.com');
define('PANEL_API_URL', 'https://panel-api.example.com');
define('PANEL_URL', 'https://panel.example.com');
define('APP_URL', 'https://app.example.com');
define('CLOUD_API_URL', 'https://cloud-api.example.com');
define('CLOUD_URL', 'https://cloud.example.com');
define('STATIC_URL', 'https://static.example.com');

define('ROOT_PATH', __DIR__);
define('STORAGE_PATH', __DIR__ . '/app/storage');

define('ENV', 'development');

function __fatalErrorHandler()
{
    $error = error_get_last();

    if ($error !== null && in_array($error['type'],
        array(E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING,
            E_COMPILE_ERROR, E_COMPILE_WARNING, E_RECOVERABLE_ERROR))) {

        \Lib\Response::setJsonContent([
            \Lib\Response::KEY_STATUS  => \Lib\Response::STATUS_ERROR,
            \Lib\Response::KEY_CODE    => \Lib\Response::CODE_ERROR,
            \Lib\Response::KEY_MESSAGE => $error['message'] . ' ' . $error['file'] . ':' . $error['line'],
        ]);
        \Lib\Response::send();
    }
}

register_shutdown_function('__fatalErrorHandler');
