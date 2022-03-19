<?php
/**
 * @author Tural Ilyasov <senior2ral@gmail.com>
 * @link https://github.com/Fogito-com/fogito-core
 * @version 1.0.2
 * @package Fogito-Core
*/
namespace Fogito;

use Fogito\App;
use Fogito\Events\Event;
use Fogito\Exception;

interface MiddlewareInterface
{
    /**
     * beforeExecuteRoute
     *
     * @param  mixed $event
     * @param  mixed $app
     * @return void
     */
    public function beforeExecuteRoute(Event $event, App $app);

    /**
     * beforeException
     *
     * @param  mixed $event
     * @param  mixed $app
     * @param  mixed $exception
     * @return void
     */
    public function beforeException(Event $event, App $app, Exception $exception);
}
