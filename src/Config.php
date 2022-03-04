<?php
namespace Fogito;

class Config
{
    public static $data = [];

    /**
     * setData
     *
     * @param  mixed $data
     * @return void
     */
    public static function setData($data)
    {
        self::$data = $data;
    }

    /**
     * getData
     *
     * @return void
     */
    public static function getData()
    {
        return self::$data;
    }

    /**
     * get
     *
     * @param  mixed $key
     * @return void
     */
    public static function get($key = null)
    {
        if ($key !== null) {
            return self::getArrayByKey($key, self::$data, self::$data);
        }
        return self::$data;
    }

    /**
     * getArrayByKey
     *
     * @param  mixed $key
     * @param  mixed $data
     * @param  mixed $default
     * @return void
     */
    private static function getArrayByKey($key = null, $data = [], $default = null)
    {
        if (is_null($key)) {
            return $data;
        }

        if (array_key_exists($key, $data)) {
            return $data[$key];
        }

        if (strpos($key, '.') === false) {
            return $default;
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($data) || !array_key_exists($segment, $data)) {
                return $data;
            }

            $data = &$data[$segment];
        }

        return $data;
    }
}
