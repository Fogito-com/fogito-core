<?php
namespace Settings;

use Fogito\Loader;
use Middlewares\Api;

class Module extends \Fogito\Module
{
    public function register($app)
    {
        $loader = new Loader();
        $loader->registerNamespaces([
            __NAMESPACE__ . '\Controllers' => __DIR__ . '/controllers',
        ]);
        $loader->register();

        $app->setDefaultNamespace(__NAMESPACE__ . '\Controllers');
        $app->addMiddleware(new Api);
    }
}
