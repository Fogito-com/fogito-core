<?php
namespace Fogito;

use Fogito\Exception;
use Fogito\MiddlewareInterface;

class Application
{
    /**
     * app
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
     * router
     *
     * @var mixed
     */
    protected $router;

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
     * @param  mixed $modules
     * @param Router $router
     * @return void
     */
    public function __construct($modules = [], Router $router)
    {
        if (!$modules) {
            throw new Exception('Modules is not registered');
        }
        $this->modules = $modules;
        $this->router  = $router;

        self::$di = $this;
    }

    /**
     * __get
     *
     * @param  string $property
     * @return void
     */
    public function __get($property)
    {
        if (!\property_exists($this, $property)) {
            $this->{$property} = null;
        }
        return $this->{$property};
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
    }

    /**
     * setActionSuffix
     *
     * @param  null|string $value
     * @return void
     */
    public function setActionSuffix($value)
    {
        $this->actionSuffix = (string) $value;
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
    }

    /**
     * set
     *
     * @param  string $property
     * @param  function $callback
     * @return void
     */
    public function set($property, $callback)
    {
        $this->{$property} = $callback;
    }

    /**
     * get
     *
     * @param  string $property
     * @return void
     */
    public function get($property)
    {
        return $this->{$property};
    }

    /**
     * addEvent
     *
     * @param  MiddlewareInterface|function $callback
     * @return void
     */
    public function addEvent($callback)
    {
        $this->eventListeners[] = $callback;
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
            $this->{$key} = $value;
        }
    }

    /**
     * run
     *
     * @return void
     */
    public function run()
    {
        $route = $this->router->getRoute();
        if ((\is_string($route['handle']) && \function_exists($route['handle'])) || \is_callable($route['handle'])) {
            return $route['handle']($this->router->getParams());
        } elseif (isset($this->modules[$this->router->getModuleName()])) {
            $module = $this->modules[$this->router->getModuleName()];
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

            $moduleDir           = \dirname($module['path']);
            $controllerName      = \ucfirst($this->router->getControllerName()) . $this->controllerSuffix;
            $controllerNamespace = "\\" . $this->defaultNamespace . "\\" . $controllerName;
            if (!\class_exists($controllerNamespace)) {
                throw new Exception('"' . $controllerNamespace . '" class not found', Exception::ERROR_NOT_FOUND_CONTROLLER);
            }

            $controller = new $controllerNamespace();
            $actionName = $this->router->getActionName() . $this->actionSuffix;

            if (!\method_exists($controller, $actionName)) {
                throw new Exception('"' . $actionName . '" method not found in ' . $controllerNamespace . '', Exception::ERROR_NOT_FOUND_ACTION);
            }

            foreach ($this->eventListeners as $callback) {
                if ($callback instanceof MiddlewareInterface) {
                    $callback->beforeExecuteRoute($this);
                } elseif (is_callable($callback)) {
                    \call_user_func($callback, $this);
                }
            }
            return $controller->{$actionName}($this->router->getParams());
        } else {
            throw new Exception('Module "' . $this->router->getModuleName() . '" not found', Exception::ERROR_NOT_FOUND_MODULE);
        }
    }
}
