<?php
namespace Fogito;

interface RouterInterface
{
    /**
     * __construct
     *
     * @param  string $url
     * @return void
     */
    public function __construct(string $url);

    /**
     * add
     *
     * @param  mixed $pattern
     * @param  mixed $handle
     * @return void
     */
    public function add($pattern, $handle);

    /**
     * setPattern
     *
     * @param  mixed $key
     * @param  mixed $regex
     * @return void
     */
    public function setPattern($key, $regex);

    /**
     * execute
     *
     * @return void
     */
    public function execute();

    /**
     * setDefaultModule
     *
     * @param  string $module
     * @return void
     */
    public function setDefaultModule(string $module);

    /**
     * setDefaultController
     *
     * @param  string $controller
     * @return void
     */
    public function setDefaultController(string $controller);

    /**
     * setDefaultAction
     *
     * @param  string $action
     * @return void
     */
    public function setDefaultAction(string $action);

    /**
     * getRoute
     *
     * @return mixed
     */
    public function getRoute();

    /**
     * getModuleName
     *
     * @return string
     */
    public function getModuleName();

    /**
     * getControllerName
     *
     * @return string
     */
    public function getControllerName();

    /**
     * getActionName
     *
     * @return string
     */
    public function getActionName();

    /**
     * getParams
     *
     * @return array
     */
    public function getParams();

    /**
     * getUrl
     *
     * @return string
     */
    public function getUrl();
}
