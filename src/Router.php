<?php
namespace Fogito;

use Fogito\Router\Group;
use Fogito\Router\Route;

class Router
{
    /**
     * URI source: _url
     *
     * @var int
     */
    const URI_SOURCE_GET_URL = 0;

    /**
     * URI source: REQUEST_URI
     *
     * @var int
     */
    const URI_SOURCE_SERVER_REQUEST_URI = 1;

    /**
     * URI source
     *
     * @var null|int
     * @access protected
     */
    protected $_uriSource;

    /**
     * Namespace
     *
     * @var null|string
     * @access protected
     */
    protected $_namespace;

    /**
     * Module
     *
     * @var null|string
     * @access protected
     */
    protected $_module;

    /**
     * Controller
     *
     * @var null|string
     * @access protected
     */
    protected $_controller;

    /**
     * Action
     *
     * @var null|string
     * @access protected
     */
    protected $_action;

    /**
     * Params
     *
     * @var null|array
     * @access protected
     */
    protected $_params;

    /**
     * Routes
     *
     * @var null|array
     * @access protected
     */
    protected $_routes;

    /**
     * Matched route
     *
     * @var null|\Fogito\Router\Route
     * @access protected
     */
    protected $_matchedRoute;

    /**
     * Matches
     *
     * @var null|array
     * @access protected
     */
    protected $_matches;

    /**
     * Was matched?
     *
     * @var boolean
     * @access protected
     */
    protected $_wasMatched = false;

    /**
     * Default namespace
     *
     * @var null|string
     * @access protected
     */
    protected $_defaultNamespace;

    /**
     * Default module
     *
     * @var null|string
     * @access protected
     */
    protected $_defaultModule;

    /**
     * Default controller
     *
     * @var null|string
     * @access protected
     */
    protected $_defaultController;

    /**
     * Default access
     *
     * @var null|string
     * @access protected
     */
    protected $_defaultAction;

    /**
     * Default params
     *
     * @var null|array
     * @access protected
     */
    protected $_defaultParams;

    /**
     * Remove extra slashes?
     *
     * @var null|boolean
     * @access protected
     */
    protected $_removeExtraSlashes;

    /**
     * NotFound-Paths
     *
     * @var null|array
     * @access protected
     */
    protected $_notFoundPaths;

    /**
     * Is exact controller name?
     *
     * @var boolean
     * @access protected
     */
    protected $_isExactControllerName = false;

    /**
     * \Fogito\Router constructor
     *
     * @param boolean|null $defaultRoutes
     * @throws Exception
     */
    public function __construct($defaultRoutes = null)
    {
        $this->_defaultParams = array();

        if (is_null($defaultRoutes)) {
            $defaultRoutes = true;
        } elseif (is_bool($defaultRoutes) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $routes = array();

        if ($defaultRoutes === true) {
            $routes[] = new Route('#^/([a-zA-Z0-9\\_\\-]+)[/]{0,1}$#', array('module' => 1));
            $routes[] = new Route('#^/([a-zA-Z0-9\\_\\-]+)/([a-zA-Z0-9\\_\\-]+)[/]{0,1}$#', array('module' => 1, 'controller' => 2));
            $routes[] = new Route('#^/([a-zA-Z0-9\\_\\-]+)/([a-zA-Z0-9\\_\\-]+)/([a-zA-Z0-9\\.\\_]+)[/]{0,1}$#', array('module' => 1, 'controller' => 2, 'action' => 3));
            $routes[] = new Route('#^/([a-zA-Z0-9\\_\\-]+)/([a-zA-Z0-9\\.\\_]+)(/.*)*$#',
                array('module' => 1, 'controller' => 2, 'action' => 3, 'params' => 4)
            );
        }

        $this->_params = array();
        $this->_routes = $routes;
    }

    /**
     * Get rewrite info. This info is read from $_GET['_url']. This returns '/' if the rewrite information cannot be read
     *
     * @return string
     */
    public function getRewriteUri()
    {
        //The developer can change the URI source
        if (isset($this->_uriSource) === false ||
            $this->_uriSource === 0) {
            //By default we use $_GET['url'] to obtain the rewrite information
            if (isset($_GET['_url']) === true) {
                if (empty($_GET['_url']) === false) {
                    return $_GET['_url'];
                }
            }
        } else {
            //Otherwise use the standard $_SERVER['REQUEST_URI']
            if (isset($_SERVER['REQUEST_URI']) === true) {
                $urlParts = explode('?', $_SERVER['REQUEST_URI']);
                if (empty($urlParts[0]) === false) {
                    return $urlParts[0];
                }
            }
        }

        return '/';
    }

    /**
     * Sets the URI source. One of the URI_SOURCE_* constants
     *
     *<code>
     *  $router->setUriSource(Router::URI_SOURCE_SERVER_REQUEST_URI);
     *</code>
     *
     * @param int $uriSource
     * @return \Fogito\Router
     * @throws Exception
     */
    public function setUriSource($uriSource)
    {
        if (is_int($uriSource) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_uriSource = $uriSource;

        return $this;
    }

    /**
     * Set whether router must remove the extra slashes in the handled routes
     *
     * @param boolean $remove
     * @return \Fogito\Router
     * @throws Exception
     */
    public function removeExtraSlashes($remove)
    {
        if (is_bool($remove) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_removeExtraSlashes = $remove;

        return $this;
    }

    /**
     * Sets the name of the default namespace
     *
     * @param string $namespaceName
     * @return \Fogito\Router
     * @throws Exception
     */
    public function setDefaultNamespace($namespaceName)
    {
        if (is_string($namespaceName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_defaultNamespace = $namespaceName;

        return $this;
    }

    /**
     * Sets the name of the default module
     *
     * @param string $moduleName
     * @return \Fogito\Router
     * @throws Exception
     */
    public function setDefaultModule($moduleName)
    {
        if (is_string($moduleName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_defaultModule = $moduleName;

        return $this;
    }

    /**
     * Sets the default controller name
     *
     * @param string $controllerName
     * @return \Fogito\Router
     * @throws Exception
     */
    public function setDefaultController($controllerName)
    {
        if (is_string($controllerName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_defaultController = $controllerName;

        return $this;
    }

    /**
     * Sets the default action name
     *
     * @param string $actionName
     * @return \Fogito\Router
     * @throws Exception
     */
    public function setDefaultAction($actionName)
    {
        if (is_string($actionName) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_defaultAction = $actionName;

        return $this;
    }

    /**
     * Sets an array of default paths. If a route is missing a path the router will use the defined here
     * This method must not be used to set a 404 route
     *
     *<code>
     * $router->setDefaults(array(
     *      'module' => 'common',
     *      'action' => 'index'
     * ));
     *</code>
     *
     * @param array $defaults
     * @return \Fogito\Router
     * @throws Exception
     */
    public function setDefaults($defaults)
    {
        if (is_array($defaults) === false) {
            throw new Exception('Defaults must be an array');
        }

        //Set a default namespace
        if (isset($defaults['namespace']) === true) {
            $this->_defaultNamespace = $defaults['namespace'];
        }

        //Set a default module
        if (isset($defaults['module']) === true) {
            $this->_defaultModule = $defaults['module'];
        }

        //Set a default controller
        if (isset($defaults['controller']) === true) {
            $this->_defaultController = $defaults['controller'];
        }

        //Set a default action
        if (isset($defaults['action']) === true) {
            $this->_defaultAction = $defaults['action'];
        }

        //Set default parameters
        if (isset($defaults['params']) === true) {
            $this->_defaultParams = $defaults['params'];
        }

        return $this;
    }

    /**
     * Removes slashes at the end of a string
     */
    private static function staticRemoveExtraSlashes($str)
    {
        if (is_string($str) === false) {
            return '';
        }

        if ($str === '/') {
            return $str;
        }

        return rtrim($str, '/');
    }

    /**
     * Handles routing information received from the rewrite engine
     *
     *<code>
     * //Read the info from the rewrite engine
     * $router->handle();
     *
     * //Manually passing an URL
     * $router->handle('/posts/edit/1');
     *</code>
     *
     * @param string|null $uri
     * @throws Exception
     */
    public function handle($uri = null)
    {
        if (is_null($uri) === true) {
            $uri = $this->getRewriteUri();
        } elseif (is_string($uri) === false) {
            throw new Exception('Invalid parameter type.');
        }

        //Remove extra slashes in the route
        if ($this->_removeExtraSlashes === true) {
            $uri = self::staticRemoveExtraSlashes($uri);
        }

        //Runtime variables
        $request         = null;
        $currentHostName = null;
        $routeFound      = false;
        $matches         = null;
        $parts           = array();
        $params          = array();

        //Set status properties
        $this->_wasMatched   = false;
        $this->_matchedRoute = null;

        $routes = (is_array($this->_routes) === true ? $this->_routes : array());

        //Routes are traversed in reversed order
        foreach ($routes as $route) {
            //Look for HTTP method constraints
            $methods = $route->getHttpMethods();
            if (is_null($methods) === false) {
                //Retrieve the request service from the container
                if (is_null($request) === true) {
                    $request = App::$di->get('request');
                    //@note no interface or object validation
                }

                //Check if the current method is allowed by the route
                if ($request->isMethod($methods) === false) {
                    continue;
                }
            }

            //Look for hostname constraints
            $hostname = $route->getHostname();
            if (is_null($hostname) === false) {
                //Retrieve the request service from the container
                if (is_null($request) === true) {
                    $request = App::$di->get('request');
                }

                //Check if the current hostname is the same as the route
                if (is_null($currentHostName) === true) {
                    $currentHostName = $request->getHttpHost();
                }

                //No HTTP_HOST, maybe in CLI mode?
                if (is_null($currentHostName) === true) {
                    continue;
                }

                //Check if the hostname restriction is the same as the current in the route
                if (strpos($hostname, '(') !== false) {
                    if (strpos($hostname, '#') === false) {
                        $hostname = '#^' . $hostname . '$#';
                    }

                    $matched = (preg_match($hostname, $currentHostName) == 0 ? false : true);
                } else {
                    $matched = ($currentHostName === $hostname ? true : false);
                }

                if ($matched === false) {
                    continue;
                }
            }

            //If the route has parentheses use preg_match
            $pattern = $route->getCompiledPattern();
            if (strpos($pattern, '^') !== false) {
                $routeFound = (preg_match($pattern, $uri, $matches) == 0 ? false : true);
            } else {
                $routeFound = ($pattern === $uri ? true : false);
            }

            //Check for beforeMatch conditions
            if ($routeFound === true) {
                $beforeMatch = $route->getBeforeMatch();
                if (is_null($beforeMatch) === false) {
                    //Check first if the callback is callable
                    if (is_callable($beforeMatch) === false) {
                        throw new Exception('Before-Match callback is not callable in matched route');
                    }

                    //Call the function
                    $routeFound = call_user_func_array($beforeMatch, array($uri, $route, $this));
                }
            }

            //Apply converters
            if ($routeFound === true) {
                //Start from the default paths
                $paths = $route->getPaths();
                $parts = $paths;

                //Check if the matches has variables
                if (is_array($matches) === true) {
                    //Get the route converters if any
                    $converters = $route->getConverters();
                    foreach ($paths as $part => $position) {
                        if (is_string($part) === false || $part[0] !== chr(0)) {
                            if (isset($matches[$position])) {
                                $matchPosition = $matches[$position];

                                //Check if the part has a converter
                                if (isset($converters[$part])) {
                                    $converter    = $converters[$part];
                                    $parts[$part] = call_user_func_array($converter, $matchPosition);
                                    continue;
                                }

                                //Update the parts if there is no coverter
                                $parts[$part] = $matchPosition;
                            } else {
                                //Apply the converters anyway
                                if (isset($converters[$part])) {
                                    $converter    = $converters[$part];
                                    $parts[$part] = call_user_func_array($converter, array($position));
                                }
                            }
                        }
                    }

                    //Update the matches generated by preg_match
                    $this->_matches = $matches;
                }
                $this->_matchedRoute = $route;
                break;
            }
        }

        //Update the wasMatched property indicating if the route was matched
        $this->_wasMatched = ($routeFound === true ? true : false);

        //The route wasn't found, try to use the not-found paths
        if ($routeFound !== true) {
            if (is_null($this->_notFoundPaths) === false) {
                $parts      = $this->_notFoundPaths;
                $routeFound = true;
            }
        }

        //Check route
        if ($routeFound === true) {
            //Check for a namespace
            if (isset($parts['namespace']) === true) {
                if (is_numeric($parts['namespace']) === false) {
                    $this->_namespace = $parts['namespace'];
                }
                unset($parts['namespace']);
            } else {
                $this->_namespace = $this->_defaultNamespace;
            }

            //Check for a module
            if (isset($parts['module']) === true) {
                if (is_numeric($parts['module']) === false) {
                    $this->_module = $parts['module'];
                    unset($parts['module']);
                } else {
                    $this->_module = $this->_defaultModule;
                }
            }

            //Check for exact controller name
            $exactStrIdentifer = chr(0) . 'exact';
            if (isset($parts[$exactStrIdentifer]) === true) {
                $this->_isExactControllerName = $parts[$exactStrIdentifer];
                unset($parts[$exactStrIdentifer]);
            } else {
                $this->_isExactControllerName = false;
            }

            //Check for a controller
            if (isset($parts['controller']) === true) {
                if (is_numeric($parts['controller']) === false) {
                    $this->_controller = $parts['controller'];
                }
                unset($parts['controller']);
            } else {
                $this->_controller = $this->_defaultController;
            }

            //Check for an action
            if (isset($parts['action']) === true) {
                if (is_numeric($parts['action']) === false) {
                    $this->_action = $parts['action'];
                }
                unset($parts['action']);
            } else {
                $this->_action = $this->_defaultAction;
            }

            //Check for parameters
            $params = array();
            if (isset($parts['params']) === true) {
                $paramStr = (string) substr($parts['params'], 1, 0);
                if (empty($paramStr) === false) {
                    $params = explode($paramStr, '/');
                }

                unset($parts['params']);
            }

            if (empty($params) === false) {
                $params = array_merge($params, $parts);
            } else {
                $params = $parts;
            }

            $this->_params = $params;
        } else {
            //Use default values if the route hasn't matched
            $this->_namespace  = $this->_defaultNamespace;
            $this->_module     = $this->_defaultModule;
            $this->_controller = $this->_defaultController;
            $this->_action     = $this->_defaultAction;
            $this->_params     = $this->_defaultParams;
        }
    }

    /**
     * Adds a route to the router without any HTTP constraint
     *
     *<code>
     * $router->add('/about', 'About::index');
     *</code>
     *
     * @param string $pattern
     * @param string|array|null $paths
     * @param string|null $httpMethods
     * @return \Fogito\Router\Route
     */
    public function add($pattern, $paths = null, $httpMethods = null)
    {
        //Every route is internally stored as a Fogito\Router\Route
        $route = new Route($pattern, $paths, $httpMethods);
        $this->_routes[] = $route;
        return $route;
    }

    /**
     * Adds a route to the router that only match if the HTTP method is GET
     *
     * @param string $pattern
     * @param string|array|null $paths
     * @return \Fogito\Router\Route
     */
    public function addGet($pattern, $paths = null)
    {
        return $this->add($pattern, $paths, 'GET');
    }

    /**
     * Adds a route to the router that only match if the HTTP method is POST
     *
     * @param string $pattern
     * @param string|array|null $paths
     * @return \Fogito\Router\Route
     */
    public function addPost($pattern, $paths = null)
    {
        return $this->add($pattern, $paths, 'POST');
    }

    /**
     * Adds a route to the router that only match if the HTTP method is PUT
     *
     * @param string $pattern
     * @param string|array|null $paths
     * @return \Fogito\Router\Route
     */
    public function addPut($pattern, $paths = null)
    {
        return $this->add($pattern, $paths, 'PUT');
    }

    /**
     * Adds a route to the router that only match if the HTTP method is PATCH
     *
     * @param string $pattern
     * @param string|array|null $paths
     * @return \Fogito\Router\Route
     */
    public function addPatch($pattern, $paths = null)
    {
        return $this->add($pattern, $paths, 'PATCH');
    }

    /**
     * Adds a route to the router that only match if the HTTP method is DELETE
     *
     * @param string $pattern
     * @param string|array|null $paths
     * @return \Fogito\Router\Route
     */
    public function addDelete($pattern, $paths = null)
    {
        return $this->add($pattern, $paths, 'DELETE');
    }

    /**
     * Add a route to the router that only match if the HTTP method is OPTIONS
     *
     * @param string $pattern
     * @param string|null|array $paths
     * @return \Fogito\Router\Route
     */
    public function addOptions($pattern, $paths = null)
    {
        return $this->add($pattern, $paths, 'OPTIONS');
    }

    /**
     * Adds a route to the router that only match if the HTTP method is HEAD
     *
     * @param string $pattern
     * @param string|array|null $paths
     * @return \Fogito\Router\Route
     */
    public function addHead($pattern, $paths = null)
    {
        return $this->add($pattern, $paths, 'HEAD');
    }

    /**
     * Mounts a group of routes in the router
     *
     * @param \Fogito\Router\Group $group
     * @return \Fogito\Router
     * @throws Exception
     */
    public function mount($group)
    {
        if (is_object($group) === false ||
            $group instanceof Group === false) {
            throw new Exception('The group of routes is not valid');
        }

        $groupRoutes = $group->getRoutes();
        if (empty($groupRoutes) === true) {
            throw new Exception('The group of routes does not contain any routes');
        }

        //Get the before-match condition
        $beforeMatch = $group->getBeforeMatch();
        if (is_null($beforeMatch) === false) {
            foreach ($groupRoutes as $route) {
                $route->beforeMatch($beforeMatch);
            }
        }

        //Get the hostname restrictions
        $hostname = $group->getHostname();
        if (is_null($hostname) === false) {
            foreach ($groupRoutes as $route) {
                $route->setHostname($hostname);
            }
        }

        //Set data
        if (is_array($this->_routes) === true) {
            $this->_routes = array_merge($this->_routes, $groupRoutes);
        } else {
            $this->_routes = $groupRoutes;
        }

        return $this;
    }

    /**
     * Set a group of paths to be returned when none of the defined routes are matched
     *
     * @param array|string $paths
     * @return \Fogito\Router
     * @throws Exception
     */
    public function notFound($paths)
    {
        if (is_array($paths) === false && is_string($paths) === false) {
            throw new Exception('The not-found paths must be an array or string');
        }

        $this->_notFoundPaths = $paths;

        return $this;
    }

    /**
     * Removes all the pre-defined routes
     */
    public function clear()
    {
        $this->_routes = array();
    }

    /**
     * Returns the processed namespace name
     *
     * @return string|null
     */
    public function getNamespaceName()
    {
        return $this->_namespace;
    }

    /**
     * Returns the processed module name
     *
     * @return string|null
     */
    public function getModuleName()
    {
        return $this->_module;
    }

    /**
     * Returns the processed controller name
     *
     * @return string|null
     */
    public function getControllerName()
    {
        return $this->_controller;
    }

    /**
     * Returns the processed action name
     *
     * @return string|null
     */
    public function getActionName()
    {
        return $this->_action;
    }

    /**
     * Returns the processed parameters
     *
     * @return array|null
     */
    public function getParams()
    {
        return $this->_params;
    }

    /**
     * Returns the route that matchs the handled URI
     *
     * @return \Fogito\Router\Route|null
     */
    public function getMatchedRoute()
    {
        return $this->_matchedRoute;
    }

    /**
     * Returns the sub expressions in the regular expression matched
     *
     * @return array|null
     */
    public function getMatches()
    {
        $this->_matches;
    }

    /**
     * Checks if the router macthes any of the defined routes
     *
     * @return boolean
     */
    public function wasMatched()
    {
        return $this->_wasMatched;
    }

    /**
     * Returns all the routes defined in the router
     *
     * @return \Fogito\Router\Route[]|null
     */
    public function getRoutes()
    {
        return $this->_routes;
    }

    /**
     * Returns a route object by its id
     *
     * @param int $id
     * @return \Fogito\Router\Route|boolean
     * @throws Exception
     */
    public function getRouteById($id)
    {
        if (is_integer($id) === false) {
            throw new Exception('Invalid parameter type.');
        }

        foreach ($this->_routes as $route) {
            if ($route->getRouteId() === $id) {
                return $route;
            }
        }

        return false;
    }

    /**
     * Returns a route object by its name
     *
     * @param string $name
     * @return \Fogito\Router\Route
     * @throws Exception
     */
    public function getRouteByName($name)
    {
        if (is_string($name) === false) {
            throw new Exception('Invalid parameter type.');
        }

        foreach ($this->_routes as $route) {
            if ($route->getName() === $name) {
                return $route;
            }
        }

        return false;
    }

    /**
     * Returns whether controller name should not be mangled
     *
     * @return boolean
     */
    public function isExactControllerName()
    {
        return $this->_isExactControllerName;
    }
}
