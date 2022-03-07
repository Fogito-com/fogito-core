<?php
error_reporting(false);
ini_set('display_errors', false);
define('APP_PATH', __DIR__);

require __DIR__ . '/../../core.php';
require __DIR__ . '/../../vendor/autoload.php';

use Fogito\Config;
use Fogito\Loader;
use Fogito\Router;
use Lib\App;
use Lib\Response;

try {
    $loader = new Loader();
    $loader->registerNamespaces([
        'Lib'         => ROOT_PATH . '/lib',
        'Models'      => ROOT_PATH . '/models',
        'Middlewares' => ROOT_PATH . '/middlewares',
        'Controllers' => APP_PATH . '/controllers',
    ]);
    $loader->register();

    $app = new App();
    $app->set('config', function () {
        $rootConfig = require ROOT_PATH . '/config/config.php';
        $appConfig  = require APP_PATH . '/config/config.php';
        $config     = new Config($rootConfig);
        $config->merge($appConfig);
        return $config;
    });

    $app->set('router', function () {
        $router = new Router(false);
        $router->removeExtraSlashes(true);
        $router->setDefaultController('upload');
        $router->setDefaultAction('index');
        $routes = require APP_PATH . '/config/routes.php';
        foreach ($routes as $key => $value) {
            $router->add($key, $value);
        }
        return $router;
    });

    $app->setDefaultNamespace('Controllers');
    $app->setControllerSuffix(null);
    $app->setActionSuffix(null);
    $app->addEvent(new \Middlewares\Cloud);
    $app->handle();

} catch (\Fogito\Exception $e) {
    Response::setJsonContent(\array_merge([
        Response::KEY_STATUS  => Response::STATUS_ERROR,
        Response::KEY_CODE    => $e->getCode(),
        Response::KEY_MESSAGE => $e->getMessage(),
    ], $e->getData()));
    Response::send();
} catch (\Exception $e) {
    Response::setJsonContent([
        Response::KEY_STATUS  => Response::STATUS_ERROR,
        Response::KEY_CODE    => $e->getCode(),
        Response::KEY_MESSAGE => $e->getMessage(),
    ]);
    Response::send();
}
