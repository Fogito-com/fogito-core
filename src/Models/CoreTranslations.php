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

        if ($result)
            $result = json_decode($result, true);
        if($result && $result["status"] === "success")
            return $result["data"];
        return false;
    }
}
