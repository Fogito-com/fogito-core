<?php
namespace Fogito;

use Fogito\AppInterface;
use Fogito\MiddlewareInterface;

class Middleware implements MiddlewareInterface
{
    /**
     * beforeExecuteRoute
     *
     * @param  mixed $app
     * @return void
     */
    public function beforeExecuteRoute(AppInterface $app)
    {}
}
