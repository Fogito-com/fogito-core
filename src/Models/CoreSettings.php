<?php
/**
 * @author Tural Ilyasov <senior2ral@gmail.com>
 * @link https://github.com/Fogito-com/fogito-core
 * @version 1.0.2
 * @package Fogito-Core
*/
namespace Fogito\Models;

class CoreSettings extends \Fogito\Db\RemoteModelManager
{
    protected static $_data = [];

    /**
     * getSource
     *
     * @return void
     */
    public static function getSource()
    {
        return 'settings';
    }

    public static function fetch()
    {
        return self::request(null, []);
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
