<?php
namespace Fogito;

use Fogito\Events\Event;

class Middleware
{
    /**
     * beforeExecuteRoute
     *
     * @param  mixed $event
     * @param  mixed $app
     * @return void
     */
    public function beforeExecuteRoute(Event $event, App $app)
    {}

    /**
     * beforeException
     *
     * @param  mixed $event
     * @param  mixed $app
     * @param  mixed $exception
     * @return void
     */
    public function beforeException(Event $event, App $app, Exception $exception)
    {}
}
