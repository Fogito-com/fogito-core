<?php
namespace Fogito;

interface MiddlewareInterface
{    
    /**
     * beforeExecuteRoute
     *
     * @param  mixed $app
     * @return void
     */
    public function beforeExecuteRoute(Application $app);
}
