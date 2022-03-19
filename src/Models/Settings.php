<?php
/**
 * @author Tural Ilyasov <senior2ral@gmail.com>
 * @link https://github.com/Fogito-com/fogito-core
 * @version 1.0.2
 * @package Fogito-Core
*/
namespace Fogito\Models;

class Settings extends \Fogito\Db\RemoteModelManager
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

    /**
     * init
     *
     * @return void
     */
    public static function init()
    {
        $response = self::request(null, []);
        if ($response['status'] == self::STATUS_SUCCESS) {
            return $response['data'];
        }
        return false;
    }

    /**
     * getData
     *
     * @return void
     */
    public static function getData()
    {
        return self::$_data;
    }

    /**
     * setData
     *
     * @param  mixed $data
     * @return void
     */
    public static function setData($data)
    {
        self::$_data = $data;
    }
}
