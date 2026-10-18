<?php
namespace Fogito\Lib;

class Company
{
    protected static $_data;

    public static function setData($data)
    {
        $data = json_decode((string)json_encode($data));
        self::$_data = $data;
    }

    public static function getData()
    {
        return self::$_data;
    }

    public static function getId()
    {
        if (self::$_data && !empty(self::$_data->id))
            return self::$_data->id;

        return null;
    }
}
