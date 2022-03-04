<?php
namespace Middlewares;

class Auth implements \Fogito\MiddlewareInterface
{    
    /**
     * beforeExecuteRoute
     *
     * @param  mixed $app
     * @return void
     */
    public function beforeExecuteRoute($app)
    {
        $module     = $app->router->getModuleName();
        $controller = $app->router->getControllerName();
        $action     = $app->router->getActionName();
        $params     = $app->router->getParams();
    }
}
