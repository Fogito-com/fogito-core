<?php
namespace Fogito\Models;

use Fogito\Config;

class CoreTranslations extends \Fogito\Db\RemoteModelManager
{
    public static function getServer()
    {
        return Config::getUrl("s2s");
    }


    public static function fetch($lang)
    {
        $data = [
            "data" => [
                "lang"         => $lang,
            ]
        ];

        $result = self::curl(Config::getUrl("s2s") . "/default/translations", $data);

        if (is_string($result))
            $result = json_decode($result, true);
        if(is_array($result) && ($result["status"] ?? null) === "success")
            return $result["data"] ?? false;
        return false;
    }
}
