<?php
namespace Fogito\Lib;

use Fogito\Exception;
use Fogito\Http\Request;
use Fogito\Lib\Cache;
use Fogito\Models\CoreSettings;
use Fogito\Lib\Lang;

class Auth
{
    protected static $_token;
    protected static $_tokenUser;
    protected static $_data;
    protected static $_permissions = [];
    protected static $_cacheDuration = 60; // Seconds

    public function __construct()
    {
        Auth::init();
    }

    public static function init()
    {
        $lang = isset($_COOKIE['lang']) ? (string) trim($_COOKIE['lang']) : (string) trim(Request::get('lang'));
        if (preg_match('/[a-z]{2}/i', trim($lang)))
            Lang::setLang($lang);

        list($_token, $_tokenUser) = Auth::getParams();
        Auth::setToken($_token);
        Auth::setTokenUser($_tokenUser);

        $response       = false;
        if($_token && strlen($_token) > 0)
            $response       = Cache::get(Auth::getCacheKey());
        if(!$response)
            $response = CoreSettings::request(null, []);
        if (is_object($response) && $_data=$response->data)
        {
            CoreSettings::setData($_data);

            if($_data->account_error)
                throw new Exception($_data->account_error->description, $_data->account_error->error_code);

            if($_data->account)
                Auth::setData($_data->account);
            if($_data->permissions)
                Auth::setPermissions($_data->permissions);
            if($_data->company)
                Company::setData($_data->company);
            if($_data->translations)
                Lang::setData($_data->translations);

            Cache::set(Auth::getCacheKey(), $response, Auth::getCacheDuration());
        }
        else
        {
            throw new Exception(Lang::get("AuthExpired", "Authentication expired"), 1001);
        }

        if(Auth::getData())
        {
            define("TOKEN", (string) Auth::getToken());
            define("BUSINESS_TYPE", (int) @Company::getData()->business_model);
            define("COMPANY_ID", (string) @Company::getId());
        }
        else
        {
            define("TOKEN", false);
        }
    }

    public static function isAuth()
    {
        return self::$_data;
    }

    public static function setData($_data)
    {
        self::$_data = $_data;
    }

    public static function getData()
    {
        return self::$_data;
    }

    public static function get($key)
    {
        if (\is_string(trim($key))) {
            return Helpers::getArrayByKey($key, (array) self::$_data, (array) self::$_data);
        }
        return null;
    }

    public static function getId()
    {
        if (isset(self::$_data)) {
            return self::$_data->id;
        }
        return null;
    }

    public static function getType()
    {
        if (isset(self::$_data)) {
            return self::$_data->type;
        }
        return null;
    }

    public static function getLevel()
    {
        if (isset(self::$_data)) {
            return self::$_data->level;
        }
        return null;
    }

    public static function getToken()
    {
        return self::$_token;
    }

    public static function setToken($_token)
    {
        self::$_token = $_token;
    }

    public static function getTokenUser()
    {
        return self::$_tokenUser;
    }

    public static function setTokenUser($_tokenUser)
    {
        self::$_tokenUser = $_tokenUser;
    }

    public static function getPermissions()
    {
        return self::$_permissions;
    }

    public static function setPermissions($_permissions = [])
    {
        self::$_permissions = $_permissions;
    }


    /**
     * start CACHING
     */
    public static function setCacheDuration($seconds=60)
    {
        Auth::$_cacheDuration = $seconds;
    }

    public static function getCacheDuration()
    {
        return Auth::$_cacheDuration;
    }

    public static function getParams()
    {
        $_token = false;
        $_tokenUser = false;
        if(strlen(@$_COOKIE["ut"]) > 0){
            $_token      = (string)@$_COOKIE["ut"];
            $_tokenUser  = (string)@$_COOKIE["token_user"];
        }else if(Request::get('token')){
            $_token      = (string)trim(Request::get('token'));
            $_tokenUser  = (string)trim(Request::get('token_user'));
        }
        return [$_token, $_tokenUser];
    }

    public static function getCacheKey()
    {
        list($_token, $_tokenUser) = Auth::getParams();
        return md5($_tokenUser."-". Lang::getLang()."-".$_token."-".Request::getServer("HTTP_ORIGIN"));
    }

    public static function clearCache()
    {
        Cache::set(Auth::getCacheKey(), false, 0);
    }
    /**
     * end CACHING
     */
}
