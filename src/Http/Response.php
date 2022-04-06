<?php
namespace Fogito\Http;

use Fogito\Http\Request;

class Response
{
    public static function setHeaderJson()
    {
        header('Content-Type: application/json');
    }

    public static function setAllowOrigins()
    {
        header("Access-Control-Allow-Origin: ".Request::getServer("HTTP_ORIGIN"));
        header("Access-Control-Allow-Headers: Content-Type, Accept");
        header("Access-Control-Allow-Methods: GET, POST");
        header("Access-Control-Allow-Credentials: true");
    }

    public static function success($data, $description="")
    {
        self::setHeaderJson();
        self::setAllowOrigins();
        $response = json_encode([
            "status"        => "success",
            "description"   => $description,
            "data"          => $data
        ]);
        $response = str_replace("fls01.kurcrm.com", "fls01.fogito.com", $response);
        exit($response);
    }

    public static function error($error, $code=2001, $data=false)
    {
        self::setHeaderJson();
        if((int)$code !== 999)
            self::setAllowOrigins();
        $response = [
            "status"        => "error",
            "error_code"    => $code,
            "description"   => $error
        ];
        if($data)
            $response["data"] = $data;
        $response = json_encode($response);
        $response = str_replace("fls01.kurcrm.com", "fls01.fogito.com", $response);
        exit($response);
    }

    public static function custom($response, $isJson=true)
    {
        if($isJson)
            self::setHeaderJson();
        self::setAllowOrigins();
        $response = json_encode($response);
        $response = str_replace("fls01.kurcrm.com", "fls01.fogito.com", $response);
        exit($response);
    }
}