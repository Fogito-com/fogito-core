<?php
namespace Lib;

class Auth
{
    public static $token = null;
    public static $data  = [];
    
    /**
     * initialize
     *
     * @return void
     */
    public static function initialize()
    {
    }
    
    /**
     * getId
     *
     * @return void
     */
    public static function getId()
    {
        if (self::$data instanceof \Lib\ModelManager) {
            return self::$data->getId();
        }
        return null;
    }
    
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
     * getToken
     *
     * @return void
     */
    public static function getToken()
    {
        return self::$token;
    }
    
    /**
     * setToken
     *
     * @param  mixed $token
     * @return void
     */
    public static function setToken($token)
    {
        self::$token = $token;
    }
}
