<?php
/**
 * @author Tural Ilyasov <senior2ral@gmail.com>
 * @link https://github.com/Fogito-com/fogito-core
 * @version 1.0.2
 * @package Fogito-Core
*/
namespace Fogito\Lib;

class Helpers
{    
    /**
     * arrayToXml
     *
     * @param  array $arr
     * @param  \SimpleXMLElement $xml
     * @return string
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
     * @param  array $d
     * @return object
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
     * @param  object $d
     * @return array
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
     * @param  string $key
     * @param  array $data
     * @param  null|array $default
     * @return mixed
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
