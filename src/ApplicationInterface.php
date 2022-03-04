<?php
namespace Fogito;

interface ApplicationInterface
{
    /**
     * __construct
     *
     * @param  mixed $modules
     * @param Router $router
     * @return void
     */
    public function __construct($modules = [], Router $router);

    /**
     * __get
     *
     * @param  string $property
     * @return void
     */
    public function __get($property);

    /**
     * setControllerSuffix
     *
     * @param  null|string $value
     * @return void
     */
    public function setControllerSuffix($value);

    /**
     * setActionSuffix
     *
     * @param  null|string $value
     * @return void
     */
    public function setActionSuffix($value);

    /**
     * setDefaultNamespace
     *
     * @param  string $namespace
     * @return void
     */
    public function setDefaultNamespace(string $namespace);

    /**
     * set
     *
     * @param  string $property
     * @param  function $callback
     * @return void
     */
    public function set($property, $callback);

    /**
     * get
     *
     * @param  string $property
     * @return void
     */
    public function get($property);

    /**
     * addEvent
     *
     * @param  MiddlewareInterface|function $callback
     * @return void
     */
    public function addEvent($callback);

    /**
     * registerServices
     *
     * @param  array $services
     * @return void
     */
    public function registerServices($services = []);

    /**
     * run
     *
     * @return void
     */
    public function run();
}
