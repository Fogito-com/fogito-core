<?php
namespace Fogito\Models;

use Fogito\Config;

class CoreCustomModel extends \Fogito\Db\RemoteModelManager
{
    public static $source = '';

    /**
     * __construct
     *
     * @param  mixed $properties
     * @return void
     */
    public function __construct($properties = [])
    {
        foreach ($properties as $key => $value) {
            $this->{$key} = $value;
        }
    }

    public static function getServer()
    {
        return Config::getUrl("s2s");
    }

    public static function setSource($source)
    {
        return self::$source = $source;
    }

    public static function getSource()
    {
        return self::$source;
    }

    /**
     * filterInsertData
     *
     * @param  mixed $properties
     * @return void
     */
    public static function filterInsertData($properties = [])
    {
        return $properties;
    }

    public static function filterUpdateData($properties = [])
    {
        return $properties;
    }



    public static function createToken($userId, $expiration=0)
    {
        $data = [
            "data" => [
                "user_id"         => $userId,
                "expiration"      => $expiration,
            ]
        ];

        $result = self::curl(Config::getUrl("s2s") . "/createtoken", $data);

        if ($result)
            return json_decode($result);
        return false;
    }


    public static function singatureUpdate($id, $fileId, $options=[])
    {
        self::init("signatureupdate");
        return self::request(
            [
                "id"        => $id,
                "file_id"   => $fileId,
            ],
            $options
        );
    }

    public static function singatureDelete($id, $options=[])
    {
        self::init("signaturedelete");
        return self::request(
            [
                "id"        => $id,
            ],
            $options
        );
    }




    public static function findFirstAndSet($user, $params=[])
    {
        $data = self::findAndSet([$user], $params);
        return $data[0];
    }

    public static function findAndSet($list, $params=[])
    {
        $keyFrom    = $params["key_from"] ? $params["key_from"]: "user";
        $keyTo      = $params["key_to"] ? $params["key_to"]: "user";
        $columns    = $params["columns"] && count($params["columns"]) > 0 ? $params["columns"]: ["id", "fullname", "avatar_tiny"];

        $ids = [];
        foreach ($list as $value)
            $ids[] = (string)$value[$keyFrom];


        // ########## start Fetch ############
        $dataById = [];
        if(count($ids) > 0)
        {

            $query = CoreUsers::find([
                [
                    'id'    => [
                        '$in'   => $ids,
                    ]
                ],
                "columns" => $columns,
            ]);
            foreach ($query as $value)
                $dataById[(string)$value["id"]] = $value;
        }
        // ########## end Fetch ############


        $data = [];
        foreach ($list as $value)
        {
            $value[$keyTo] = $dataById[$value[$keyFrom]] ? $dataById[$value[$keyFrom]] : false;
            $data[] = $value;
        }

        return $data;
    }

    public static function mergePositions($list)
    {
        $data = [];
        foreach ($list as $value)
        {
            $value["position"] = implode(", ", array_map(function ($position){ return $position["title"];}, $value["positions"]));
            $data[] = $value;
        }
        return $data;
    }

}
