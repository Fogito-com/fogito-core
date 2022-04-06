<?php
namespace Fogito\Models;

use Fogito\Config;

class CoreSettings extends \Fogito\Db\RemoteModelManager
{

    protected static $_data = [];

    /**
     * getSource
     *
     * @return void
     */
    public static function getServer()
    {
        return Config::$_serverUrls["s2s"];
    }

    public static function getSource()
    {
        return "users";
    }

    public static function fetch()
    {
        return self::curl(null, []);
    }

    public static function getData()
    {
        return self::$_data;
    }

    public static function setData($data)
    {
        self::$_data = $data;
    }
}
