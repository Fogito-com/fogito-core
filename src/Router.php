<?php
namespace Fogito;

use Fogito\Exception;
use Fogito\RouterInterface;

class Router
{
    /**
     * url
     *
     * @var mixed
     */
    protected $url;

    /**
     * module
     *
     * @var mixed
     */
    protected $module;

    /**
     * defaultModule
     *
     * @var mixed
     */
    protected $defaultModule;

    /**
     * controller
     *
     * @var mixed
     */
    protected $controller;

    /**
     * defaultController
     *
     * @var mixed
     */
    protected $defaultController;

    /**
     * action
     *
     * @var mixed
     */
    protected $action;

    /**
     * defaultAction
     *
     * @var mixed
     */
    protected $defaultAction;

    /**
     * route
     *
     * @var mixed
     */
    protected $route;

    /**
     * routes
     *
     * @var array
     */
    protected $routes = [];

    /**
     * params
     *
     * @var array
     */
    protected $params = [];

    const PATTERN_MODULE     = '(?P<module>[\w0-9\_\-]+)';
    const PATTERN_CONTROLLER = '(?P<controller>[\w0-9\_\-]+)';
    const PATTERN_ACTION     = '(?P<action>[\w0-9\.\_]+)';
    const PATTERN_PARAMS     = '(?P<params>.*)';

    /**
     * patterns
     *
     * @var array
     */
    protected $patterns = [
        ":module"     => self::PATTERN_MODULE,
        ":controller" => self::PATTERN_CONTROLLER,
        ":action"     => self::PATTERN_ACTION,
        ":params"     => self::PATTERN_PARAMS,
    ];

    /**
     * __construct
     *
     * @param  mixed $url
     * @return void
     */
    public function __construct(string $url)
    {
        $this->url = rtrim(strip_tags($url), "/");
    }

    /**
     * add
     *
     * @param  mixed $pattern
     * @param  mixed $handle
     * @return void
     */
    public function add($pattern, $handle)
    {
        array_push($this->routes, [
            'pattern' => $pattern,
            'handle'  => $handle,
        ]);
    }

    /**
     * setPattern
     *
     * @param  mixed $key
     * @param  mixed $regex
     * @return void
     */
    public function setPattern($key, $regex)
    {
        $this->patterns[$key] = $regex;
    }

    /**
     * execute
     *
     * @return void
     */
    public function execute()
    {
        foreach ($this->routes as $route) {
            $patterns = \explode('/', $route['pattern']);
            $regex    = [];
            foreach ($patterns as $value) {
                $value = strtr($value, $this->patterns);
                if (\preg_match('#\{(.+)\:(.*?)\}$#u', $value, $match)) {
                    $value = \preg_replace('#\{(.+)\:(.*?)\}$#u', '(?P<$1>$2)', $value);
                }
                $regex[] = $value;
            }

            $pattern = \implode('/', $regex);
            if (preg_match("#^" . $pattern . "$#u", $this->getUrl(), $matches)) {
                unset($matches[0]);
                $params = [];
                foreach ($matches as $key => $value) {
                    if (!\is_int($key)) {
                        $params[$key] = $value;
                    } else {
                        if (\is_array($route['handle'])) {
                            $key = \array_search($key, $route['handle']);
                            if ($key) {
                                $params[$key] = $value;
                            }
                        }
                    }
                }

                if (isset($params['module'])) {
                    $this->module = $params['module'];
                }
                if (isset($params['controller'])) {
                    $this->controller = $params['controller'];
                }
                if (isset($params['action'])) {
                    $this->action = $params['action'];
                }

                foreach ($params as $key => $value) {
                    if (!\in_array($key, ['module', 'controller', 'action', 'params'])) {
                        $this->params[$key] = $value;
                    }
                }

                if (!$this->params && isset($params['params'])) {
                    $this->params = (array) $params['params'];
                }

                $this->route = $route;
                break;
            }
        }

        if (!$this->route) {
            throw new Exception('Route not found');
        }
    }

    /**
     * setDefaultModule
     *
     * @param  mixed $module
     * @return void
     */
    public function setDefaultModule(string $module)
    {
        $this->defaultModule = $this->module = $module;
    }

    /**
     * setDefaultController
     *
     * @param  mixed $controller
     * @return void
     */
    public function setDefaultController(string $controller)
    {
        $this->defaultController = $this->controller = $controller;
    }

    /**
     * setDefaultAction
     *
     * @param  mixed $action
     * @return void
     */
    public function setDefaultAction(string $action)
    {
        $this->defaultAction = $this->action = $action;
    }

    /**
     * getRoute
     *
     * @return void
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * getModuleName
     *
     * @return void
     */
    public function getModuleName()
    {
        return $this->module;
    }

    /**
     * getControllerName
     *
     * @return void
     */
    public function getControllerName()
    {
        return $this->controller;
    }

    /**
     * getActionName
     *
     * @return void
     */
    public function getActionName()
    {
        return $this->action;
    }

    /**
     * getParams
     *
     * @return void
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * getUrl
     *
     * @return void
     */
    public function getUrl()
    {
        return $this->url;
    }
}
