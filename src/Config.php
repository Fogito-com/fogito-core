<?php

namespace Fogito;

use ArrayAccess;
use Countable;
use Fogito\Http\Request;

/**
 * Fogito\Config
 *
 * Fogito\Config is designed to simplify the access to, and the use of, configuration data within applications.
 * It provides a nested object property based user interface for accessing this configuration data within
 * application code.
 *
 *<code>
 *  $config = new Fogito\Config(array(
 *      "database" => array(
 *          "adapter" => "Mysql",
 *          "host" => "localhost",
 *          "username" => "scott",
 *          "password" => "cheetah",
 *          "dbname" => "test_db"
 *      ),
 *      "fogito" => array(
 *          "controllersDir" => "../app/controllers/",
 *          "modelsDir" => "../app/models/",
 *          "viewsDir" => "../app/views/"
 *      )
 * ));
 *</code>
 */
class Config implements ArrayAccess, Countable
{
    /**
     * Storage
     *
     * @var array
     * @access private
     */

    public static $_prodDomain = 'app.{domain}';
    public static $_betaDomain = 'beta.{domain}';
    public static $_devDomain = 'dev.{domain}';

    public static $_servicePaths = [
        "s2s"        => "/s2s",
        "files"      => "/files",
        "core"       => "/core",
        "accounting" => "/invoices",
    ];


    public static function getDomain()
    {
        $arr = array_slice(explode('.', Request::getServer('HTTP_HOST')), 1);
        $mainDomain = implode('.', $arr);
        if (Request::envMode() === 'development')
        {
            $fullDomain = self::$_devDomain;
        }
        else if (Request::envMode() === 'beta')
        {
            $fullDomain = self::$_betaDomain;
        }
        else
        {
            $fullDomain = self::$_prodDomain;
        }
        return str_replace('{domain}', $mainDomain, $fullDomain);
    }

    public static function getUrl($server = "s2s", $options = [])
    {
        $protocol = $options["protocol"] === "https" ? "https" : "http";
        $path = self::$_servicePaths[$server];
        return $protocol . '://' . self::getDomain() . '/api' . $path;
    }

    public static function getData($key = false)
    {
        if ($key)
        {
            if (App::$di->config->{$key})
                return App::$di->config->{$key};
            return false;
        }
        return App::$di->config;
    }

    private $_storage = array();

    /**
     * \Fogito\Config constructor
     *
     * @param array $arrayConfig
     * @throws Exception
     */
    public function __construct($prodConfig, $devConfig = false)
    {
        $config = Request::isDevMode() && $devConfig ? $devConfig : $prodConfig;
        if (is_array($config) === false)
            throw new Exception('The configuration must be an Array');

        foreach ($config as $key => $value)
            if (is_array($value) === true)
            {
                $this->_storage[$key] = new self($value);
            }
            else
            {
                $this->_storage[$key] = $value;
            }
    }

    /**
     * Allows to check whether an attribute is defined using the array-syntax
     *
     *<code>
     * var_dump(isset($config['database']));
     *</code>
     *
     * @param scalar $index
     * @return boolean
     * @throws Exception
     */
    public function offsetExists($index)
    {
        if (is_scalar($index) === false)
        {
            throw new Exception('Invalid parameter type.');
        }
        return isset($this->_storage[$index]);
    }

    /**
     * Gets an attribute from the configuration, if the attribute isn't defined returns null
     * If the value is exactly null or is not defined the default value will be used instead
     *
     *<code>
     * echo $config->get('controllersDir', '../app/controllers/');
     *</code>
     *
     * @param scalar $index
     * @param mixed $defaultValue
     * @return mixed
     * @throws Exception
     */
    public function get($index, $defaultValue = null)
    {
        if (is_scalar($index) === false)
        {
            throw new Exception('Invalid parameter type.');
        }
        return (isset($this->_storage[$index]) === true ? $this->_storage[$index] : $defaultValue);
    }

    /**
     * Gets an attribute using the array-syntax
     *
     *<code>
     * print_r($config['database']);
     *</code>
     *
     * @param scalar $index
     * @return mixed
     * @throws Exception
     */
    public function offsetGet($index)
    {
        return $this->get($index);
    }

    /**
     * Sets an attribute using the array-syntax
     *
     *<code>
     * $config['database'] = array('type' => 'Sqlite');
     *</code>
     *
     * @param scalar $index
     * @param mixed $value
     * @throws Exception
     */
    public function offsetSet($index, $value)
    {
        if (is_scalar($index) === false)
        {
            throw new Exception('Invalid parameter type.');
        }

        $this->_storage[$index] = $value;
    }

    /**
     * Unsets an attribute using the array-syntax
     *
     *<code>
     * unset($config['database']);
     *</code>
     *
     * @param scalar $index
     * @throws Exception
     */
    public function offsetUnset($index)
    {
        if (is_scalar($index) === false)
        {
            throw new Exception('Invalid parameter type.');
        }

        unset($this->_storage[$index]);
    }

    /**
     * Merges a configuration into the current one
     *
     * @brief void \Fogito\Config::merge(array|object $with)
     *
     *<code>
     *  $appConfig = new \Fogito\Config(array('database' => array('host' => 'localhost')));
     *  $globalConfig->merge($config2);
     *</code>
     *
     * @param \Fogito\Config|array $config
     * @throws Exception Exception
     */
    public function merge($prodConfig, $devConfig = false)
    {
        $config = Request::isDevMode() && $devConfig ? $devConfig : $prodConfig;
        if (is_object($config) === true && $config instanceof Config === true)
        {
            $config = $config->toArray(false);
        }
        elseif (is_array($config) === false)
        {
            throw new Exception('Configuration must be an object or array');
        }

        foreach ($config as $key => $value)
            if (is_array($value) === true)
            {
                $this->_storage[$key] = new self($value);
            }
            else
            {
                $this->_storage[$key] = $value;
            }
    }

    /**
     * Converts recursively the object to an array
     *
     * @brief array \Fogito\Config::toArray(bool $recursive = true);
     *
     *<code>
     *  print_r($config->toArray());
     *</code>
     *
     * @param boolean $recursive
     * @return array
     */
    public function toArray($recursive = true)
    {
        $array = $this->_storage;

        if ($recursive === true)
        {
            foreach ($this->_storage as $key => $value)
            {
                if ($value instanceof Config === true)
                {
                    $array[$key] = $value->toArray($recursive);
                }
                else
                {
                    $array[$key] = $value;
                }
            }
        }

        return $array;
    }

    /**
     * Counts configuration elements
     *
     * @return int
     */
    public function count()
    {
        return count((array)$this->_storage);
    }

    /**
     * Restore data after unserialize()
     */
    public function __wakeup()
    {
    }

    /**
     * Restores the state of a \Fogito\Config object
     *
     * @param array $data
     * @return \Fogito\Config
     */
    public static function __set_state($data)
    {
        //@warning this function is not compatible with a direct var_export
        return new Config($data);
    }

    /**
     * Get element
     *
     * @param scalar $index
     * @return mixed
     * @throws Exception
     */
    public function __get($index)
    {
        return $this->get($index);
    }

    /**
     * Set element
     *
     * @param scalar $index
     * @param mixed $value
     * @throws Exception
     */
    public function __set($index, $value)
    {
        $this->offsetSet($index, $value);
    }

    /**
     * Isset element?
     *
     * @param scalar $index
     * @return boolean
     * @throws Exception
     */
    public function __isset($index)
    {
        return $this->offsetExists($index);
    }

    /**
     * Unset element
     *
     * @WARNING This function is not implemented in the original
     * Fogito API.
     *
     * @param scalar $index
     * @throws Exception
     */
    public function __unset($index)
    {
        $this->offsetUnset($index);
    }
}
