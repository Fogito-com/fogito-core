<?php
namespace Fogito;

class App
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
     * Events Manager
     *
     * @var null
     * @access protected
     */
    protected $eventsManager;

    /**
     * __construct
     *
     * @return null
     */
    protected $modulesPath;

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
     * Sets the events manager
     *
     * @param $eventsManager
     */
    public function setEventsManager($eventsManager)
    {
        if (!is_object($eventsManager)) {
            throw new Exception('Invalid parameter type.');
        }

        $this->eventsManager = $eventsManager;
    }

    /**
     * Returns the internal event manager
     *
     * @return null
     */
    public function getEventsManager()
    {
        return $this->eventsManager;
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

    public function setModulesPath($value)
    {
        $this->modulesPath = (string) $value;
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
            $exception = new Exception('Router service was not registered');

            if (is_object($this->eventsManager)) {
                if ($this->eventsManager->fire('dispatch:beforeException', $this, $exception) === false) {
                    return false;
                }
            }

            throw $exception;
        }

        $router->handle($uri);
        $this->set('router', $router);

        $this->modulesPath .= "/".$router->getModuleName();
        if (!\file_exists($this->modulesPath."/Module.php")) {
            $exception = new Exception('Module.php in "' . $this->modulesPath . '" not found', Exception::ERROR_NOT_FOUND_MODULE);

            if (is_object($this->eventsManager)) {
                if ($this->eventsManager->fire('dispatch:beforeException', $this, $exception) === false) {
                    return false;
                }
            }

            throw $exception;
        }

        include $this->modulesPath."/Module.php";
        $moduleNamespace = "\\Modules\\Module";

        if (!\class_exists($moduleNamespace)) {
            $exception = new Exception('"' . $moduleNamespace . '" class not found in ' . $this->modulesPath . '/Module.php', Exception::ERROR_NOT_FOUND_MODULE);

            if (is_object($this->eventsManager)) {
                if ($this->eventsManager->fire('dispatch:beforeException', $this, $exception) === false) {
                    return false;
                }
            }

            throw $exception;
        }

        $moduleClass = new $moduleNamespace();
        if (is_object($moduleClass) && !\method_exists($moduleClass, 'register')) {
            $exception = new Exception('"register" method not found in ' . $moduleNamespace . ' class');

            if (is_object($this->eventsManager)) {
                if ($this->eventsManager->fire('dispatch:beforeException', $this, $exception) === false) {
                    return false;
                }
            }

            throw $exception;
        }

        $moduleClass->register($this);

        $controllerName      = \ucfirst($router->getControllerName()) . $this->controllerSuffix;
        $controllerNamespace = "\\" . $this->defaultNamespace . "\\" . $controllerName;

        if (!\class_exists($controllerNamespace)) {
            $exception = new Exception('"' . $controllerNamespace . '" class not found', Exception::ERROR_NOT_FOUND_CONTROLLER);

            if (is_object($this->eventsManager)) {
                if ($this->eventsManager->fire('dispatch:beforeException', $this, $exception) === false) {
                    return false;
                }
            }

            throw $exception;
        }

        $actionName = $router->getActionName() . $this->actionSuffix;
        if (is_object($controllerNamespace) && !\method_exists($controllerNamespace, $actionName)) {
            $exception = new Exception('"' . $actionName . '" method not found in ' . $controllerNamespace . '', Exception::ERROR_NOT_FOUND_ACTION);

            if (is_object($this->eventsManager)) {
                if ($this->eventsManager->fire('dispatch:beforeException', $this, $exception) === false) {
                    return false;
                }
            }

            throw $exception;
        }

        if (is_object($this->eventsManager)) {
            $this->eventsManager->fire('dispatch:beforeExecuteRoute', $this);
        }

        $controller = new $controllerNamespace($this);
        $handler    = \call_user_func_array([$controller, $actionName], $router->getParams());

        return $handler;
    }
}
