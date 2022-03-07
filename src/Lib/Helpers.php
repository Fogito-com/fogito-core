<?php
namespace Fogito\Lib;

class Helpers
{    
    /**
     * arrayToXml
     *
     * @param  mixed $arr
     * @param  mixed $xml
     * @return void
     */
    public static function arrayToXml(array $arr, \SimpleXMLElement $xml)
    {
        foreach ($arr as $k => $v) {
            is_array($v)
            ? self::arrayToXml($v, $xml->addChild($k))
            : $xml->addChild($k, $v);
        }
        return $xml;
    }
    
    /**
     * arrayToObject
     *
     * @param  mixed $d
     * @return void
     */
    public static function arrayToObject($d)
    {
        if (is_array($d)) {
            /*
             * Return array converted to object
             * Using __FUNCTION__ (Magic constant)
             * for recursive call
             */
            return (object) array_map(__FUNCTION__, $d);
        } else {
            // Return object
            return $d;
        }
    }
    
    /**
     * objectToArray
     *
     * @param  mixed $d
     * @return void
     */
    public static function objectToArray($d)
    {
        if (is_object($d)) {
            // Gets the properties of the given object
            // with get_object_vars function
            $d = get_object_vars($d);
        }

        if (is_array($d)) {
            /*
             * Return array converted to object
             * Using __FUNCTION__ (Magic constant)
             * for recursive call
             */
            return array_map(__FUNCTION__, $d);
        } else {
            // Return array
            return $d;
        }
    }
    
    /**
     * getArrayByKey
     *
     * @param  mixed $key
     * @param  mixed $data
     * @param  mixed $default
     * @return void
     */
    public static function getArrayByKey($key = null, $data = [], $default = null)
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
