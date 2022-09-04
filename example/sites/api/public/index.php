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
use Fogito\Http\Response;

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
        $prodRootConfig = require ROOT_PATH . '/app/config/prodConfig.php';
        $prodAppConfig  = require APP_PATH . '/config/prodConfig.php';
        $devRootConfig = require ROOT_PATH . '/app/config/devConfig.php';
        $devAppConfig  = require APP_PATH . '/config/devConfig.php';
        $config         = new Config($prodRootConfig);
        $config->merge($prodAppConfig);
        return $config;
    });

    $app->set('router', function () {
        $router = new Router(false);
        $router->removeExtraSlashes(true);
        $router->setDefaultModule('default');
        $router->setDefaultController('index');
        $router->setDefaultAction('index');
        $routes = require APP_PATH . '/config/routes.php';
        foreach ($routes as $key => $value) {
            $router->add($key, $value);
        }
        return $router;
    });

    $app->setModulesPath(APP_PATH.'/modules');
    $app->setControllerSuffix('Controller');
    $app->setActionSuffix(null);
    $app->handle();

} catch (\Fogito\Exception $e) {
    Response::error($e->getMessage(), $e->getCode());
} catch (\Exception $e) {
    Response::error($e->getMessage(), $e->getCode());
}
