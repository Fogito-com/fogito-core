<?php
namespace Fogito\Lib;

use Fogito\Http\Request;
use Fogito\Http\Response;
use Fogito\Lib\Lang;
use Fogito\Models\CoreSettings;

class Auth
{
    protected static $_token;
    protected static $_tokenUser;
    protected static $_tokenDisabled=false;
    protected static $_data;
    protected static $_permissions = [];
    protected static $_pricing = [];
    protected static $_cacheDuration = 30; // Seconds
    protected static $_error = false; // ["description" => "", "code" => 1001]

    public function __construct()
    {
        self::init();
    }

    public static function init()
    {
        $lang = isset($_COOKIE['lang']) ? trim((string) $_COOKIE['lang']) : trim((string) Request::get('lang'));
        if (preg_match('/[a-z]{2}/i', trim($lang)))
            Lang::setLang($lang);

        list($_token, $_tokenUser) = self::getParams();
        self::setToken($_token);
        self::setTokenUser($_tokenUser);

        $response       = false;
        if($_token && strlen($_token) > 0)
            $response       = Cache::get(self::getCacheKey());
        if(!$response)
        {
            $response = CoreSettings::curl(CoreSettings::getServer()."/settings", [
                "token"         => $_token,
                "parameters"    => [
                    "account", "permissions", "company", "pricing", "translations"
                ]
            ]);
        }
        if(is_string($response))
            $response = json_decode($response, true);
        if (is_array($response))
        {
            if(($response["status"] ?? null) == "success" && $_data=($response["data"] ?? null))
            {
                CoreSettings::setData($_data);

                if(!empty($_data["account_error"]))
                    self::setError($_data["account_error"]["error_code"] ?? 1001, $_data["account_error"]["description"] ?? "");

                if(!empty($_data["account"]))
                    self::setData($_data["account"]);
                if(!empty($_data["permissions"]))
                    self::setPermissions($_data["permissions"]);
                if(!empty($_data["company"]))
                    Company::setData($_data["company"]);
                if(!empty($_data["pricing"]))
                    self::setPricing($_data["pricing"]);
                if(!empty($_data["translations"]))
                    Lang::setData($_data["translations"]);

                Cache::set(self::getCacheKey(), json_encode($response), self::getCacheDuration());
            }
            else
            {
                self::setError((int)($response["error_code"] ?? 1000), (string)($response["description"] ?? Lang::get("ConnectionError", "Connection Error")));

                Cache::set(self::getCacheKey(), json_encode($response), 4);
            }
        }
        else
        {
            self::setError(1000, Lang::get("ConnectionError", "Connection Error"));
        }

        if(self::getData())
        {
            if (!defined("TOKEN"))
                define("TOKEN", (string) self::getToken());
            if (!defined("BUSINESS_TYPE"))
                define("BUSINESS_TYPE", (int) (Company::getData()->business_model ?? 0));
            if (!defined("COMPANY_ID"))
                define("COMPANY_ID", (string) Company::getId());
        }
        else
        {
            if (!defined("TOKEN"))
                define("TOKEN", false);
            if (!defined("BUSINESS_TYPE"))
                define("BUSINESS_TYPE", false);
            if (!defined("COMPANY_ID"))
                define("COMPANY_ID", false);
        }
    }

    public static function isAuth()
    {
        return self::$_data;
    }

    public static function setError($code=1001, $description="")
    {
        return self::$_error = ["code" => $code, "description" => $description];
    }

    public static function getError()
    {
        return self::$_error;
    }

    public static function setData($_data)
    {
        self::$_data = \json_decode((string)\json_encode($_data));
    }

    public static function getData()
    {
        return self::$_data;
    }

    public static function getId()
    {
        if (isset(self::$_data)) {
            return self::$_data->id ?? null;
        }
        return null;
    }

    public static function getType()
    {
        if (isset(self::$_data)) {
            return self::$_data->type ?? null;
        }
        return null;
    }

    public static function getLevel()
    {
        if (isset(self::$_data)) {
            return self::$_data->level ?? null;
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

    public static function disableToken()
    {
        return self::$_tokenDisabled=true;
    }

    public static function enableToken()
    {
        return self::$_tokenDisabled=false;
    }

    public static function tokenAllowed()
    {
        return self::$_tokenDisabled ? false: true;
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

    public static function checkPermission($key, $selected=false)
    {
        $permissions = self::getPermissions();

        $allow = false;
        if (!empty($permissions[$key]['allow']))
        {
            if($selected){
                if(in_array($selected, (array)($permissions[$key]["selected"] ?? [])))
                    $allow = true;
            }else if(!empty($permissions[$key]["selected"])){
                $allow = $permissions[$key];
            }else{
                $allow = true;
            }
        }
        return $allow;
    }

    public static function validatePermission($key, $selected=false)
    {
        $allow = self::checkPermission($key, $selected);
        if(!$allow)
            Response::error(Lang::get("PageNotAllowed"));
        return $allow;
    }


    public static function setPricing($pricing=[])
    {
        return self::$_pricing = $pricing;
    }

    public static function getPricing($key=false)
    {
        if($key)
            return self::$_pricing[$key] ?? false;
        return self::$_pricing;
    }

    public static function getAvatar($data, $type=false)
    {
        $avatars = [];
        $url = "https://app.fogito.com/api/core";
        if ($data && !empty($data->avatar) && !empty($data->avatar->avatars)){
            $avatars = (array)$data->avatar->avatars;
        }else{
            $avatars = [
                "tiny"    => $url . "/assets/images/noavatar.png",
                "small"   => $url . "/assets/images/noavatar.png",
                "medium"  => $url . "/assets/images/noavatar.png",
                "large"   => $url . "/assets/images/noavatar.png",
                "nophoto" => true,
                "id"      => false,
            ];
        }

        if($type){
            if(!empty($avatars[$type]))
                return $avatars[$type];
            return $url . "/assets/images/noavatar.png";
        }else{
            return $avatars;
        }
    }

    /**
     * @return false || true || array
     */
    public static function isAllowed($permission, $selected=false)
    {
        if(empty(self::$_permissions[$permission]) || empty(self::$_permissions[$permission]["allow"]))
            return false;
        if($selected && !in_array($selected, (array)(self::$_permissions[$permission]["selected"] ?? [])))
            return false;
        return self::$_permissions[$permission];
    }


    /**
     * start CACHING
     */
    public static function setCacheDuration($seconds=60)
    {
        self::$_cacheDuration = $seconds;
    }

    public static function getCacheDuration()
    {
        return self::$_cacheDuration;
    }

    public static function getParams()
    {
        $_token = false;
        $_tokenUser = false;
        if(isset($_COOKIE["ut"]) && strlen((string) $_COOKIE["ut"]) > 1){
            $_token      = (string) $_COOKIE["ut"];
            $_tokenUser  = isset($_COOKIE["token_user"]) ? (string) $_COOKIE["token_user"] : "";
        }else if(Request::get('token')){
            $_token      = trim((string) Request::get('token'));
            $_tokenUser  = trim((string) Request::get('token_user'));
        }
        return [$_token, $_tokenUser];
    }

    public static function getCacheKey()
    {
        list($_token, $_tokenUser) = self::getParams();
        return md5($_tokenUser."-". Lang::getLang()."-".$_token."-".Request::getServer("HTTP_ORIGIN"));
    }

    public static function clearCache()
    {
        Cache::set(self::getCacheKey(), false, 0);
    }
    /**
     * end CACHING
     */
}
