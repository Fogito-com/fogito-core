<?php

namespace Fogito\Db;

use Fogito\App;
use Fogito\Http\Request;
use Fogito\Http\Response;
use Fogito\Lib\Auth;
use Fogito\Lib\Lang;

#[\AllowDynamicProperties]
class RemoteModelManager
{
    public static $url = "";

    public static $action = "";

    /** Opt-in for the current request only. The callback receives already-filtered data. */
    private static $requestTransport = null;

    public static function setRequestTransport(?callable $transport): ?callable
    {
        $previous = self::$requestTransport;
        self::$requestTransport = $transport;
        return $previous;
    }

    public static function init($action)
    {
        self::$action = $action;
        self::$url = static::getServer() . "/" . static::getSource() . "/" . $action;
    }


    public static function filterSendParams($data = [])
    {
        $s2s = App::$di->config->s2s;
        if (\is_object($s2s))
        {
            $data = \array_merge($s2s->toArray(), $data);
        }
        $mergeData = [
            "lang"        => Lang::getLang(),
            "token_user"  => Auth::getData() ? (string)Auth::getId() : "",
            "http_origin" => Request::getServer("HTTP_ORIGIN"),
            "request_uri" => Request::getServer("REQUEST_URI")
        ];
        if (strlen((string)Auth::getToken()) > 0 && Auth::tokenAllowed())
            $mergeData["token"] = Auth::getToken();
        if (strlen((string)Auth::getTokenUser()) > 0 && Auth::tokenAllowed())
            $mergeData["token_user"] = Auth::getTokenUser();
        $remoteServerToken = Request::get("server_token");
        if (is_scalar($remoteServerToken) && strlen((string)$remoteServerToken) > 0)
            $mergeData["remote_server_token"] = (string)$remoteServerToken;
        return array_merge($data, $mergeData);
    }

    public static function request($params = [], $options = false)
    {
        $options = (array)$options;
        $postData = [
            "data" => $params,
        ];

        $raw_result = self::curl(self::$url, $postData);
        $rawText = is_string($raw_result) ? $raw_result : (is_scalar($raw_result) ? (string)$raw_result : '');
        $result = \json_decode($rawText, true);
        if (is_array($result) && ($result["status"] ?? null) === "success")
        {
            if (!empty($options["result"]))
                return $result;
            return $result["data"] ?? null;
        }
        elseif (is_array($result) && ($result["status"] ?? null) === "error")
        {
            $error = $result;
        }
        else
        {
            $error = [
                "status"      => "error",
                "description" => "Connection Error: " . $rawText,
                "error_code"  => 1004
            ];
        }

        if ($error && !empty($options["debug"]))
            Response::error($error["description"] ?? 'Connection Error', $error["error_code"] ?? 1004);
        if ($error && !empty($options["result"]))
            return $error;
        return false;
    }

    public static function curl($url, $data)
    {
        $data = self::filterSendParams($data);

        if (self::$requestTransport !== null)
        {
            return \call_user_func(self::$requestTransport, $url, $data);
        }

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

        if (curl_errno($ch))
            $result = json_encode([
                "status"      => "error",
                "description" => "Connection Error",
                "error_code"  => 1003
            ]);
        return $result;
    }

    public static function find($filter, $options = false)
    {
        self::init("find");
        $filter = (array)$filter;
        if (count((array)($filter[0] ?? [])) > 0)
        {
            $filter["filter"] = $filter[0];
            unset($filter[0]);
        }
        return self::request($filter, $options);
    }


    /**
     * UsersRemote::insert(
     * $insertData,
     * [
     * “debug”: true/false, // true olanda error olan kimi dayandirir skripti ozu response qaytarir apide,
     * “result”: true/false, // true olanda serverde qayidan erroru qaytarir. false olanda serverde error qayitsa response sadece false olur. esas find, findFirst ucun ele qurulub
     * ]
     * )
     */
    public static function insert($params, $options = ["result" => true])
    {
        self::init("insert");
        return self::request(
            [
                "insert" => $params
            ],
            $options
        );
    }

    /**
     * UsersRemote::validate(
     * $insertData,
     * [
     * “debug”: true/false, // true olanda error olan kimi dayandirir skripti ozu response qaytarir apide,
     * “result”: true/false, // true olanda serverde qayidan erroru qaytarir. false olanda serverde error qayitsa response sadece false olur. esas find, findFirst ucun ele qurulub
     * ]
     * )
     */
    public static function validate($params, $options = ["result" => true])
    {
        self::init("validate");
        return self::request(
            [
                "validate" => $params
            ],
            $options
        );
    }

    public static function findFirst($filter, $options = false)
    {
        self::init("findfirst");
        $filter = (array)$filter;
        if (count((array)($filter[0] ?? [])) > 0)
        {
            $filter["filter"] = $filter[0];
            unset($filter[0]);
        }
        return self::request($filter, $options);
    }

    public static function findById($id, $options = false)
    {
        self::init("findfirst");
        return self::request([
            "filter" => [
                "id" => (string)$id
            ]
        ], $options);
    }

    public static function update($filter, $data, $options = false)
    {
        self::init("update");
        return self::request(
            [
                "filter" => $filter,
                "update" => $data,
            ],
            $options ? $options : ["result" => true]
        );
    }

    public static function delete($filter, $options = false)
    {
        self::init("delete");
        return self::request(["filter" => $filter], $options ? $options : ["result" => true]);
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
    public static function avatarupdate($userId, $fileId, $options = ["result" => true])
    {
        self::init("avatarupdate");
        return self::request(
            [
                "id"      => $userId,
                "file_id" => $fileId,
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
    public static function avatardelete($userId, $options = ["result" => true])
    {
        self::init("avatardelete");
        return self::request(
            [
                "id" => $userId
            ],
            $options
        );
    }

    public static function count($filter, $options = false)
    {
        self::init("count");
        $filter = (array)$filter;
        if (count((array)($filter[0] ?? [])) > 0)
            $filter["filter"] = $filter[0];
        return self::request($filter, $options);
    }


    public static function getPermissions($filter, $options = false)
    {
        self::init("permissions");
        return self::request($filter, $options);
    }

}
