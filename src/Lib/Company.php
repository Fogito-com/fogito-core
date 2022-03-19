<?php
/**
 * @author Tural Ilyasov <senior2ral@gmail.com>
 * @link https://github.com/Fogito-com/fogito-core
 * @version 1.0.2
 * @package Fogito-Core
*/
namespace Fogito\Lib;

use Fogito\Lib\Helpers;

class Company
{
    protected static $_data;

    /**
     * Set data
     *
     * @param \Fogito\Models\Companies $data
     * @return void
     */
    public static function setData($data)
    {
        if (!$data instanceof \Fogito\Models\Companies) {
            throw new \Exception('Invalid parameter type: ' . get_called_class());
        }
        
        self::$_data = $data;
    }

    /**
     * Get data
     *
     * @return \Fogito\Models\Companies
     */
    public static function getData()
    {
        return self::$_data;
    }

    /**
     * Get ID
     *
     * @return null|string
     */
    public static function getId()
    {
        if (isset(self::$_data)) {
            return self::$_data->getId();
        }

        return null;
    }
    
    /**
     * get
     *
     * @param  string $key
     * @return null|mixed
     */
    public static function get($key)
    {
        if (\is_string(trim($key))) {
            return Helpers::getArrayByKey($key, (array) self::$_data, (array) self::$_data);
        }
        
        return null;
    }
}
