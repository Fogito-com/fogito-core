<?php
namespace Fogito\Db;

use Fogito\App;
use Fogito\Http\Request;
use Fogito\Http\Response;
use Fogito\Lib\Auth;
use Fogito\Lib\Lang;

class RemoteModelManager
{
    public static $url = "https://s2s.fogito.com";

    public static $action = "";

    public static function init($action)
    {
        self::$action = $action;
        self::$url   = static::getServer()."/".static::getSource()."/".$action;
    }


    public static function filterSendParams($data=[])
    {
        $s2s = App::$di->config->s2s;
        if (\is_object($s2s)) {
            $data = \array_merge($data, $s2s->toArray());
        }
        $mergeData = [
            "lang"          => Lang::getLang(),
            "token_user"    => Auth::getData() ? (string)Auth::getId(): "",
            "http_origin"   => Request::getServer("HTTP_ORIGIN")
        ];
        if(strlen(Auth::getToken()) > 10)
            $mergeData["token"] = Auth::getToken();
        if(strlen(Auth::getTokenUser()) > 10)
            $mergeData["token_user"] = Auth::getTokenUser();
        return array_merge($data, $mergeData);
    }

    public static function request($params = [], $options = false)
    {
        $postData = [
            "data"          => $params,
        ];

        $result = self::curl(self::$url, $postData);
        $result = \json_decode($result, true);
        if($result && $result["status"] === "success")
        {
            if($options["result"])
                return $result;
            return $result["data"];
        }
        elseif($result && $result["status"] === "error")
        {
            $error = $result;
        }
        else
        {
            $error = [
                "status"        => "error",
                "description"   => "Connection Error",
                "error_code"    => 1004
            ];
        }
        if($error)
            Response::error($error["description"].", ".self::$url, $error["error_code"]);


        if($error && $options && $options["debug"] == true)
            Response::error($error["description"], $error["error_code"]);
        if($error && $options && $options["result"] == true)
            return  $error;
        return false;
    }

    public static function  curl($url, $data)
    {
        $data = self::filterSendParams($data);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);

        if(curl_errno($ch))
            $result = json_encode([
                "status"        => "error",
                "description"   => "Connection Error",
                "error_code"    => 1003
            ]);
        return $result;
    }

    public static function find($filter, $options=false)
    {
        self::init("find");
        if((!$filter["filter"] || count($filter["filter"])) && count($filter[0]) > 0)
            $filter["filter"] = $filter[0];
        return self::request($filter, $options);
    }



    /**
    UsersRemote::insert(
    $insertData,
    [
    “debug”: true/false, // true olanda error olan kimi dayandirir skripti ozu response qaytarir apide,
    “result”: true/false, // true olanda serverde qayidan erroru qaytarir. false olanda serverde error qayitsa response sadece false olur. esas find, findFirst ucun ele qurulub
    ]
    )
     */
    public static function insert($params, $options=["result" => true])
    {
        self::init("insert");
        return self::request(
            [
                "insert"    => $params
            ],
            $options
        );
    }

    public static function findFirst($filter, $options=false)
    {
        self::init("findfirst");
        if((!$filter["filter"] || count($filter["filter"])) && count($filter[0]) > 0)
            $filter["filter"] = $filter[0];
        return self::request($filter, $options);
    }

    public static function findById($id, $options=false)
    {
        self::init("findfirst");
        return self::request([
            "filter" => [
                "id" => (string)$id
            ]
        ], $options);
    }

    public static function update($filter, $data, $options=false)
    {
        self::init("update");
        return self::request(
            [
                "filter" => $filter,
                "update"   => $data,
            ],
            $options ? $options: ["result" => true]
        );
    }

    public static function delete($filter, $options=false)
    {
        self::init("delete");
        return self::request(["filter" => $filter], $options);
    }

    /**
     * @param $data = [
     *      "id"    => "string",
     *      "file_id"    => "string"
     * ]
     * @param bool[] $options default: ["result" => true]
     * @return array|false|mixed
     *
     */
    public static function avatarupdate($userId, $fileId, $options=["result" => true])
    {
        self::init("avatarupdate");
        return self::request(
            [
                "id"   => $userId,
                "file_id"   => $fileId,
            ],
            $options
        );
    }




    /**
     * @param $data = [
     *      "id"    => "string",
     * ]
     * @param bool[] $options
     * @return array|false|mixed
     */
    public static function avatardelete($userId, $options=["result" => true])
    {
        self::init("avatardelete");
        return self::request(
            [
                "id" => $userId
            ],
            $options
        );
    }

    public static function count($filter, $options=false)
    {
        self::init("count");
        return self::request($filter, $options);
    }




    public static function getPermissions($filter, $options=false)
    {
        self::init("permissions");
        return self::request($filter, $options);
    }

}
