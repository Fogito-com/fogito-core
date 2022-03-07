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
define('STORAGE_PATH', __DIR__ . '/storage');

define('ENV', 'development');

function __fatalErrorHandler()
{
    $error = error_get_last();

    if ($error !== null && in_array($error['type'],
        array(E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING,
            E_COMPILE_ERROR, E_COMPILE_WARNING, E_RECOVERABLE_ERROR))) {

        $message = $error['message'] . ' ' . $error['file'] . ':' . $error['line'];
        echo json_encode([
            'status'  => 'error',
            'code'    => 1003,
            'message' => $message,
        ]);
        die;
    }
}

register_shutdown_function('__fatalErrorHandler');
