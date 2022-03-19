<?php
namespace Products;

use Fogito\Events\Manager as EventsManager;
use Fogito\Loader;
use Fogito\Middlewares\Auth;

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

        $eventsManager = new EventsManager();
        $eventsManager->attach('dispatch', new Auth);
        $app->setEventsManager($eventsManager);
    }
}
