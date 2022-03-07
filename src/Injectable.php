<?php
namespace Fogito;

use Fogito\Exception;

abstract class Injectable
{
    public static $di;

    /**
     * setDI
     *
     * @param  mixed $di
     * @return void
     */
    public function setDI($di)
    {
        self::$di = $di;
    }

    /**
     * getDI
     *
     * @return void
     */
    public function getDI()
    {
        return self::$di;
    }

    /**
     * has
     *
     * @param  mixed $name
     * @return void
     */
    public function has($name)
    {
        if (is_string($name) === false) {
            throw new Exception('The service alias must be a string');
        }

        return isset(self::$di->services[$name]);
    }

    /**
     * __get
     *
     * @param  mixed $property
     * @return void
     */
    public function __get($property)
    {
        if (!\property_exists(self::$di, $property)) {
            if (\array_key_exists($property, self::$di->services)) {
                return self::$di->get($property);
            }
            throw new Exception("Property {$property} does not exists");
        }
        return $this->{$property};
    }

    /**
     * __call
     *
     * @param  mixed $method
     * @param  mixed $arguments
     * @return void
     */
    public function __call($method, $arguments = null)
    {
        if ($method == 'get') {
            if (\array_key_exists($method, self::$di->services)) {
                if (!empty($arguments)) {
                    return self::$di->get($method, $arguments);
                }
            }
            return self::$di->get($method);
        }

        if ($method == 'set') {
            if (isset($arguments)) {
                self::$di->set($method, $arguments);
                return null;
            }
        }

        throw new Exception('Call to undefined method or service \'' . $method . "'");
    }
}
