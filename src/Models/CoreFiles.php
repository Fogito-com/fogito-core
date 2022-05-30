<?php
namespace Fogito\Models;

use Fogito\Config;

class CoreFiles extends \Fogito\Db\RemoteModelManager
{
    /**
     * getSource
     *
     * @return void
     */
    public static function getServer()
    {
        return Config::getUrl("files");
    }

    public static function getSource()
    {
        return "s2s";
    }

    public static function fetch()
    {
        return self::request(null, []);
    }

    public static function checkTempFile($id)
    {
        $data = [
            "data"  => [
                "temp_id"         => $id,
            ]
        ];

        $result = self::curl(Config::getUrl("files") . "/s2s/checktempfile", $data);

        if ($result)
            return \json_decode($result, true);
        return false;
    }

    public static function checkTempFiles($ids=[])
    {
        $data = [
            "data"  => [
                "temp_ids"         => $ids,
            ]
        ];

        $result = self::curl(Config::getUrl("files") . "/s2s/checktempfile/multiple", $data);

        if ($result)
            return \json_decode($result, true);
        return false;
    }





    public static function moveFile($id, $params=[])
    {
        $data = [
            "data"  => [
                "temp_id"       => $id,
                "params"        => $params
            ]
        ];

        $result = self::curl(Config::getUrl("files") . "/s2s/move", $data);

        if ($result)
            return \json_decode($result, true);
        return false;
    }

    public static function moveFiles($ids=[], $params=[])
    {
        $data = [
            "data"  => [
                "temp_ids"      => $ids,
                "params"        => $params
            ]
        ];

        $result = self::curl(Config::getUrl("files") . "/s2s/move/multiple", $data);

        if ($result)
            return \json_decode($result, true);
        return false;
    }
}
