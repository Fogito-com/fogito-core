<?php
namespace Middlewares;

use Fogito\App;
use Fogito\Events\Event;
use Fogito\Exception;
use Fogito\Lib\Auth;
use Fogito\Lib\Lang;
use Fogito\Http\Request;
use Fogito\Http\Response;
use Models\LogsAccess;

class Api
{
    /**
     * beforeExecuteRoute
     *
     * @param  Event $event
     * @param  App $app
     * @return void
     */
    public function beforeExecuteRoute(Event $event, App $app)
    {
        try {
            $module     = $app->router->getModuleName();
            $controller = $app->router->getControllerName();
            $action     = $app->router->getActionName();

            Auth::init();
            if (!\in_array($module, ['auth']) && !Auth::getData())
            {
                //Response::error(Auth::$)
            }


            $i = new LogsAccess();
            if (Auth::isAuth())
                $i->user_id = Auth::getId();
            $i->query = \array_slice(Request::get(), 0, 100, true);
            $i->set($i);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), $e->getCode());
        }

    }

    /**
     * beforeException
     *
     * @param  Event $event
     * @param  App $app
     * @param  Exception $exception
     * @return void
     */
    public function beforeException(Event $event, App $app, Exception $exception)
    {
        switch ($exception->getCode()) {
            case Exception::ERROR_NOT_FOUND_ACTION:
                Response::error(Lang::get('ActionNotFound', 'Action not found'), $exception->getCode());

            case Exception::ERROR_NOT_FOUND_CONTROLLER:
                Response::error(Lang::get('ControllerNotFound', 'Controller not found'), $exception->getCode());

            case Exception::ERROR_NOT_FOUND_MODULE:
                Response::error(Lang::get('ModuleNotFound', 'Module not found'), $exception->getCode());
        }
    }
}
