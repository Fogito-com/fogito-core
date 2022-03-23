<?php
error_reporting(1);
ini_set('display_errors', true);
define('APP_PATH', __DIR__."/..");

require __DIR__ . '/../../../core.php';
require __DIR__ . '/../../../vendor/autoload.php';

use Fogito\App;
use Fogito\Config;
use Fogito\Loader;
use Fogito\Router;
use Lib\Response;

try {
    $loader = new Loader();
    $loader->registerNamespaces([
        'Lib'         => ROOT_PATH . '/app/lib',
        'Models'      => ROOT_PATH . '/app/models',
        'Middlewares' => ROOT_PATH . '/app/middlewares',
    ]);
    $loader->register();

    $app = new App();
    $app->set('config', function () {
        $rootConfig = require ROOT_PATH . '/app/config/config.php';
        $appConfig  = require APP_PATH . '/config/config.php';
        $config     = new Config($rootConfig);
        $config->merge($appConfig);
        return $config;
    });

    $app->set('router', function () {
        $router = new Router(false);
        $router->removeExtraSlashes(true);
        $router->setDefaultModule('auth');
        $router->setDefaultController('login');
        $router->setDefaultAction('index');
        $routes = require APP_PATH . '/config/routes.php';
        foreach ($routes as $key => $value) {
            $router->add($key, $value);
        }
        return $router;
    });

    $modules = require APP_PATH . '/config/modules.php';
    $app->registerModules($modules);
    $app->setControllerSuffix('Controller');
    $app->setActionSuffix(null);
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
