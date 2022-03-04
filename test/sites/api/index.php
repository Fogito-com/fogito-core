<?php
error_reporting(E_ALL);
ini_set('display_errors', true);

define('ROOT_PATH', __DIR__ . '/../..');
require ROOT_PATH . '/vendor/autoload.php';

use Fogito\Application;
use Fogito\Config;
use Fogito\Loader;
use Fogito\Request;
use Fogito\Response;
use Fogito\Router;

define('COMPANY_ID', 2);
define('APP_ID', 265);

try {
    $loader = new Loader();
    $loader->registerNamespaces([
        'Lib'         => ROOT_PATH . '/lib',
        'Models'      => ROOT_PATH . '/models',
        'Middlewares' => ROOT_PATH . '/middlewares',
    ]);
    $loader->register();

    $url    = isset($_GET['_url']) ? urldecode($_GET['_url']) : '/';
    $router = new Router($url);
    $router->setDefaultModule('index');
    $router->setDefaultController('index');
    $router->setDefaultAction('index');
    $router->setPattern(':id', '(?P<id>[a-z0-9]{24}+)');
    $routes = require ROOT_PATH . '/config/routes.php';
    foreach ($routes as $key => $value) {
        $router->add($key, $value);
    }
    $router->execute();

    $request = new Request();
    $request->execute();

    Response::setFormat(Response::FORMAT_JSON);
    Response::setHeaders([
        'Access-Control-Allow-Headers'     => 'Content-Type, Accept',
        'Access-Control-Allow-Methods'     => 'GET, POST',
        'Access-Control-Allow-Credentials' => 'true',
    ]);

    Config::setData([
        's2s'   => [
            'api_url'     => '',
            'credentials' => [
                'server_token' => '',
                'app_id'       => APP_ID,
                'timezone'     => 101,
                'lang'         => 'en',
            ],
        ],
        'mongo' => [
            'server' => [
                'host'     => '127.0.0.1',
                'port'     => 27017,
                'username' => null,
                'password' => null,
            ],
            'dbname' => 'gpp',
        ],
    ]);

    $modules = [
        'categories' => [
            'className' => 'Categories\Module',
            'path'      => __DIR__ . '/modules/categories/Module.php',
        ],
        'services'   => [
            'className' => 'Services\Module',
            'path'      => __DIR__ . '/modules/services/Module.php',
        ],
    ];
    $app = new Application($modules, $router);
    $app->setControllerSuffix('Controller');
    $app->setActionSuffix(null);
    $app->run();

} catch (\Fogito\Exception $e) {
    Response::setJsonContent($e->getAppError());
    Response::send();
} catch (\Exception $e) {
    Response::setJsonContent([
        Response::$keyStatus  => Response::STATUS_ERROR,
        Response::$keyCode    => $e->getCode(),
        Response::$keyMessage => $e->getMessage(),
    ]);
    Response::send();
}
