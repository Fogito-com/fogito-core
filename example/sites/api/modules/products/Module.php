<?php
namespace Modules;

use Fogito\Events\Manager as EventsManager;
use Fogito\Loader;
use Fogito\Lib\Auth;
use Middlewares\Api;

class Module extends \Fogito\Module
{
    public function register($app)
    {
        $loader = new Loader();
        $loader->registerNamespaces([
            'Controllers' => __DIR__ . '/controllers',
        ]);
        $loader->register();

        $app->setDefaultNamespace('Controllers');

        $eventsManager = new EventsManager();
        $eventsManager->attach('dispatch', new Auth);
        $eventsManager->attach('dispatch', new Api);
        $app->setEventsManager($eventsManager);
    }
}
