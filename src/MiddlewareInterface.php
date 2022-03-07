<?php
namespace Fogito;

use Fogito\AppInterface;

interface MiddlewareInterface
{
    /**
     * beforeExecuteRoute
     *
     * @param  mixed $app
     * @return void
     */
    public function beforeExecuteRoute(AppInterface $app);
}
