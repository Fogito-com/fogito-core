<?php
/**
 * @author Tural Ilyasov <senior2ral@gmail.com>
 * @link https://github.com/Fogito-com/fogito-core
 * @version 1.0.2
 * @package Fogito-Core
*/
namespace Fogito;

interface AppInterface
{    
    /**
     * initialize
     *
     * @param  mixed $callback
     * @return void
     */
    public function initialize($callback);

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
     * @param  mixed $value
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
     * addEvent
     *
     * @param  function $callback
     * @return void
     */
    public function addEvent($callback);

    /**
     * addMiddleware
     *
     * @param  mixed $callbacks
     * @return void
     */
    public function addMiddleware($callback);

    /**
     * registerServices
     *
     * @param  array $services
     * @return void
     */
    public function registerServices($services = []);

    /**
     * setService
     *
     * @param  mixed $property
     * @param  mixed $callback
     * @return void
     */
    public function set($property, $callback);

    /**
     * getService
     *
     * @param  mixed $property
     * @return void
     */
    public function get($property);

    /**
     * registerModules
     *
     * @param  mixed $modules
     * @return void
     */
    public function registerModules($modules = []);

    /**
     * handle
     *
     * @param  mixed $uri
     * @return void
     */
    public function handle($uri = null);
}
