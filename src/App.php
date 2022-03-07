<?php
namespace Fogito;

use Fogito\Exception;
use Fogito\MiddlewareInterface;
use Fogito\Router;
use Fogito\Injectable;

class App extends Injectable
{    
    /**
     * di
     *
     * @var mixed
     */
    public static $di;
        
    /**
     * modules
     *
     * @var array
     */
    protected $modules = [];

    /**
     * services
     *
     * @var array
     */
    protected $services = [];

    /**
     * eventListeners
     *
     * @var array
     */
    protected $eventListeners = [];

    /**
     * defaultNamespace
     *
     * @var mixed
     */
    protected $defaultNamespace;

    /**
     * controllerSuffix
     *
     * @var string
     */
    protected $controllerSuffix = 'Controller';

    /**
     * actionSuffix
     *
     * @var string
     */
    protected $actionSuffix = 'Action';

    /**
     * __construct
     *
     * @return void
     */
    public function __construct()
    {
        self::$di = $this;
    }

    /**
     * initialize
     *
     * @param  mixed $callback
     * @return void
     */
    public function initialize($callback)
    {
        \call_user_func($callback);
    }

    /**
     * setControllerSuffix
     *
     * @param  null|string $value
     * @return void
     */
    public function setControllerSuffix($value)
    {
        $this->controllerSuffix = (string) $value;
        return $this;
    }

    /**
     * setActionSuffix
     *
     * @param  mixed $value
     * @return void
     */
    public function setActionSuffix($value)
    {
        $this->actionSuffix = (string) $value;
        return $this;
    }

    /**
     * setDefaultNamespace
     *
     * @param  string $namespace
     * @return void
     */
    public function setDefaultNamespace(string $namespace)
    {
        $this->defaultNamespace = $namespace;
        return $this;
    }

    /**
     * addEvent
     *
     * @param  function $callback
     * @return void
     */
    public function addEvent($callback)
    {
        $this->eventListeners[] = $callback;
        return $this;
    }

    /**
     * addMiddleware
     *
     * @param  mixed $callbacks
     * @return void
     */
    public function addMiddleware($callback)
    {
        if (!$callback instanceof MiddlewareInterface) {
            throw new Exception('Invalid middleware type.');
        }

        $this->addEvent($callback);
        return $this;
    }

    /**
     * registerServices
     *
     * @param  array $services
     * @return void
     */
    public function registerServices($services = [])
    {
        foreach ($services as $key => $value) {
            $this->set($key, $value);
        }
        return $this;
    }

    /**
     * setService
     *
     * @param  mixed $property
     * @param  mixed $callback
     * @return void
     */
    public function set($property, $callback)
    {
        $this->services[$property] = $callback;
        return $this;
    }

    /**
     * getService
     *
     * @param  mixed $property
     * @return void
     */
    public function get($property)
    {
        $service = $this->services[$property];
        if (\is_callable($service)) {
            $service = \call_user_func($service, $this);
        }
        return $service;
    }

    /**
     * __get
     *
     * @param  mixed $property
     * @return void
     */
    public function __get($property)
    {
        if (!\property_exists($this, $property)) {
            if (\array_key_exists($property, $this->services)) {
                return $this->get($property);
            }
            throw new Exception("Property {$property} does not exists");
        }
        return $this->{$property};
    }

    /**
     * registerModules
     *
     * @param  mixed $modules
     * @return void
     */
    public function registerModules($modules = [])
    {
        $this->modules = $modules;
        return $this;
    }

    /**
     * handle
     *
     * @param  mixed $uri
     * @return void
     */
    public function handle($uri = null)
    {
        $router = $this->get('router');
        if (!$router instanceof Router) {
            throw new Exception('Router service was not registered');
        }

        $router->handle($uri);
        $this->set('router', $router);

        if ($this->modules) {
            if (isset($this->modules[$router->getModuleName()])) {
                $module = $this->modules[$router->getModuleName()];
                if (!\file_exists($module['path'])) {
                    throw new Exception('Module "' . $module['path'] . '" not found', Exception::ERROR_NOT_FOUND_MODULE);
                }

                include $module['path'];
                $moduleNamespace = "\\" . $module['className'];

                if (!\class_exists($moduleNamespace)) {
                    throw new Exception('"' . $moduleNamespace . '" class not found in ' . $module['path'] . '', Exception::ERROR_NOT_FOUND_MODULE);
                }

                $moduleClass = new $moduleNamespace();
                if (!\method_exists($moduleClass, 'register')) {
                    throw new Exception('"register" method not found in ' . $moduleNamespace . ' class');
                }

                $moduleClass->register($this);
            }
        }

        $controllerName      = \ucfirst($router->getControllerName()) . $this->controllerSuffix;
        $controllerNamespace = "\\" . $this->defaultNamespace . "\\" . $controllerName;

        if (!\class_exists($controllerNamespace)) {
            throw new Exception('"' . $controllerNamespace . '" class not found', Exception::ERROR_NOT_FOUND_CONTROLLER);
        }

        $actionName = $router->getActionName() . $this->actionSuffix;
        if (!\method_exists($controllerNamespace, $actionName)) {
            throw new Exception('"' . $actionName . '" method not found in ' . $controllerNamespace . '', Exception::ERROR_NOT_FOUND_ACTION);
        }

        foreach ($this->eventListeners as $callback) {
            if ($callback instanceof MiddlewareInterface) {
                $callback->beforeExecuteRoute($this);
            } elseif (is_callable($callback)) {
                \call_user_func($callback, $this);
            }
        }

        $controller = new $controllerNamespace($this);
        $handler = \call_user_func_array([$controller, $actionName], $router->getParams());

        return $handler;
    }
}
