<?php
namespace Fogito\Models;

use Fogito\Config;

class CoreTimezones extends \Fogito\Db\RemoteModelManager
{
    public static function getServer()
    {
        return Config::getUrl("s2s");
    }


    public static function fetch()
    {
        $data = [
            "data" => []
        ];

        $result = self::curl(Config::getUrl("s2s") . "/default/timezones", $data);

        if ($result)
            $result = json_decode($result, true);
        if($result && $result["status"] === "success")
            return $result["data"];
        return false;
    }
}
