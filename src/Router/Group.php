<?php
namespace Fogito\Router;

use Fogito\Exception;
use Fogito\Router\Route;

class Group
{
    /**
     * Prefix
     *
     * @var null|string
     * @access protected
    */
    protected $_prefix;

    /**
     * Hostname
     *
     * @var null|string
     * @access protected
    */
    protected $_hostname;

    /**
     * Paths
     *
     * @var null|array|string
     * @access protected
    */
    protected $_paths;

    /**
     * Routes
     *
     * @var null|array
     * @access protected
    */
    protected $_routes;

    /**
     * Before Match
     *
     * @var null|string
     * @access protected
    */
    protected $_beforeMatch;

    /**
     * \Fogito\Mvc\Router\Group constructor
     *
     * @param array|null $paths
     * @throws Exception
     */
    public function __construct($paths = null)
    {
        if (is_array($paths) === true || is_string($paths) === true) {
            $this->_paths = $paths;
        } else {
            throw new Exception('Invalid parameter type.');
        }

        if (method_exists($this, 'initialize') === true) {
            $this->initialize($paths);
        }
    }

    /**
     * Set a hostname restriction for all the routes in the group
     *
     * @param string $hostname
     * @return \Fogito\Mvc\Router\Group
     * @throws Exception
     */
    public function setHostname($hostname)
    {
        if (is_string($hostname) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_hostname = $hostname;

        return $this;
    }

    /**
     * Returns the hostname restriction
     *
     * @return string|null
     */
    public function getHostname()
    {
        return $this->_hostname;
    }

    /**
     * Set a common uri prefix for all the routes in this group
     *
     * @param string $prefix
     * @return \Fogito\Mvc\Router\Group
     */
    public function setPrefix($prefix)
    {
        if (is_string($prefix) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_prefix = $prefix;

        return $this;
    }

    /**
     * Returns the common prefix for all the routes
     *
     * @return string|null
     */
    public function getPrefix()
    {
        return $this->_prefix;
    }

    /**
     * Set a before-match condition for the whole group
     *
     * @param string $prefix
     * @return \Fogito\Mvc\Router\Group
     * @throws Exception
     */
    public function beforeMatch($beforeMatch)
    {
        if (is_string($beforeMatch) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_beforeMatch = $beforeMatch;

        return $this;
    }

    /**
     * Returns the before-match condition if any
     *
     * @return string|null
     */
    public function getBeforeMatch()
    {
        return $this->_beforeMatch;
    }

    /**
     * Set common paths for all the routes in the group
     *
     * @param array $paths
     * @return \Fogito\Mvc\Router\Group
     * @throws Exception
     */
    public function setPaths($paths)
    {
        if (is_array($paths) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $this->_paths = $paths;
    }

    /**
     * Returns the common paths defined for this group
     *
     * @return array|string|null
     */
    public function getPaths()
    {
        return $this->_paths;
    }

    /**
     * Returns the routes added to the group
     *
     * @return \Fogito\Mvc\Router\Route[]|null
     */
    public function getRoutes()
    {
        return $this->_routes;
    }

    /**
     * Adds a route applying the common attributes
     *
     * @param string $patten
     * @param array $paths
     * @param array $httpMethods
     * @return \Fogito\Mvc\Router\Route
     * @throws Exception
     */
    protected function _addRoute($pattern, $paths, $httpMethods)
    {
        if (is_string($pattern) === false ||
            is_array($paths) === false ||
            is_array($httpMethods) === false) {
            throw new Exception('Invalid parameter type.');
        }

        //Add the prefix to the pattern
        $pattern = (string)$this->_prefix.$pattern;

        //Check if the paths need to be merged with current paths
        if (is_array($this->_paths) === true) {
            if (is_array($paths) === true) {
                //Merge the paths with the defualt paths
                $paths = array_merge($this->_paths, $paths);
            } else {
                $paths = $this->_paths;
            }
        }

        //Every route is internally stored as a Fogito\Mvc\Router\Route
        $route = new Route($pattern, $paths, $httpMethods);
        $this->_routes[] = $route;
        return $route;
    }

    /**
     * Adds a route to the router on any HTTP method
     *
     *<code>
     * $router->add('/about', 'About::index');
     *</code>
     *
     * @param string $pattern
     * @param string|array|null $paths
     * @param string|null $httpMethods
     * @return \Fogito\Mvc\Router\Route
     */
    public function add($pattern, $paths = null, $httpMethods = null)
    {
        return $this->_addRoute($pattern, $paths, $httpMethods);
    }

    /**
     * Adds a route to the router that only match if the HTTP method is GET
     *
     * @param string $pattern
     * @param string|array|null $paths
     * @return \Fogito\Mvc\Router\Route
     */
    public function addGet($pattern, $paths = null)
    {
        return $this->_addRoute($pattern, $paths, 'GET');
    }

    /**
     * Adds a route to the router that only match if the HTTP method is POST
     *
     * @param string $pattern
     * @param string|array|null $paths
     * @return \Fogito\Mvc\Router\Route
     */
    public function addPost($pattern, $paths = null)
    {
        return $this->_addRoute($pattern, $paths, 'POST');
    }

    /**
     * Adds a route to the router that only match if the HTTP method is PUT
     *
     * @param string $pattern
     * @param string|array|null $paths
     * @return \Fogito\Mvc\Router\Route
     */
    public function addPut($pattern, $paths = null)
    {
        return $this->_addRoute($pattern, $paths, 'PUT');
    }

    /**
     * Adds a route to the router that only match if the HTTP method is PATCH
     *
     * @param string $pattern
     * @param string|array|null $paths
     * @return \Fogito\Mvc\Router\Route
     */
    public function addPatch($pattern, $paths = null)
    {
        return $this->_addRoute($pattern, $paths, 'PATCH');
    }

    /**
     * Adds a route to the router that only match if the HTTP method is DELETE
     *
     * @param string $pattern
     * @param string|array|null $paths
     * @return \Fogito\Mvc\Router\Route
     */
    public function addDelete($pattern, $paths = null)
    {
        return $this->_addRoute($pattern, $paths, 'DELETE');
    }

    /**
     * Add a route to the router that only match if the HTTP method is OPTIONS
     *
     * @param string $pattern
     * @param string|array|null $paths
     * @return \Fogito\Mvc\Router\Route
     */
    public function addOptions($pattern, $paths = null)
    {
        return $this->_addRoute($pattern, $paths, 'OPTIONS');
    }

    /**
     * Adds a route to the router that only match if the HTTP method is HEAD
     *
     * @param string $pattern
     * @param string|array|null $paths
     * @return \Fogito\Mvc\Router\Route
     */
    public function addHead($pattern, $paths = null)
    {
        return $this->_addRoute($pattern, $paths, 'HEAD');
    }

    /**
     * Removes all the pre-defined routes
     */
    public function clear()
    {
        $this->_routes = array();
    }
}
