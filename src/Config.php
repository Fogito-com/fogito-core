<?php
namespace Fogito;

use ArrayAccess;
use Countable;
use Fogito\Exception;
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
    public static $_prodServerUrls = [
        "s2s"           => "http://s2s.fogito.com",
        "files"         => "http://files.fogito.com",
        "core"          => "http://core.fogito.com",
        "accounting"    => "http://invoices.fogito.com",
    ];
    public static $_devServerUrls = [
        "s2s"           => "http://tests2s.fogito.com",
        "files"         => "http://testfiles.fogito.com",
        "core"          => "http://testcore.fogito.com",
        "accounting"    => "http://testinvoices.fogito.com",
    ];


    public static function getUrl($server="s2s", $options=[])
    {
        $url = self::$_prodServerUrls[$server];
        if(Request::isDevMode())
            $url = self::$_devServerUrls[$server];
        if($options["protocol"] === "https")
            $url = str_replace("http://", "https://", $url);
        return $url;
    }

    public static function getData($key=false)
    {
        if($key && App::$di->config->{$key})
            return App::$di->config->{$key};
        return App::$di->config;
    }

    private $_storage = array();

    /**
     * \Fogito\Config constructor
     *
     * @param array $arrayConfig
     * @throws Exception
     */
    public function __construct($prodConfig, $devConfig=false)
    {
        $config = Request::isDevMode() && $devConfig ? $devConfig: $prodConfig;
        if (is_array($config) === false)
            throw new Exception('The configuration must be an Array');

        foreach ($config as $key => $value)
            if (is_array($value) === true) {
                $this->_storage[$key] = new self($value);
            } else {
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
        if (is_scalar($index) === false) {
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
        if (is_scalar($index) === false) {
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
        if (is_scalar($index) === false) {
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
        if (is_scalar($index) === false) {
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
    public function merge($prodConfig, $devConfig=false)
    {
        $config = Request::isDevMode() && $devConfig ? $devConfig: $prodConfig;
        if (is_object($config) === true && $config instanceof Config === true) {
            $config = $config->toArray(false);
        } elseif (is_array($config) === false) {
            throw new Exception('Configuration must be an object or array');
        }

        foreach ($config as $key => $value) {
            //The key is already defined in the object, we have to merge it
            if (isset($this->_storage[$key]) === true) {
                if ($this->$key instanceof Config === true &&
                    $value instanceof Config === true) {
                    $this->$key->merge($value);
                } else {
                    $this->$key = $value;
                }
            } else {
                if ($value instanceof Config === true) {
                    $this->$key = new self($value->toArray());
                } else {
                    $this->$key = $value;
                }
            }
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

        if ($recursive === true) {
            foreach ($this->_storage as $key => $value) {
                if ($value instanceof Config === true) {
                    $array[$key] = $value->toArray($recursive);
                } else {
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
        return count($this->_storage);
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
