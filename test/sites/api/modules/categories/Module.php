<?php
namespace Categories;

use Fogito\Loader;
use Middlewares\Auth;

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
        $app->addEvent(new Auth);
    }
}
