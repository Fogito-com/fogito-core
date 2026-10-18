<?php
namespace Fogito\Models;

use Fogito\Config;

class CoreUsers extends \Fogito\Db\RemoteModelManager
{
    const STATUS_MODERATE = 1;
    const STATUS_ACTIVE   = 2;
    const STATUS_INACTIVE = 3;

    const TYPE_USER      = 'user';
    const TYPE_MODERATOR = 'moderator';

    const LEVEL_OPERATOR       = 'operator';
    const LEVEL_SUPERVISOR     = 'supervisor';
    const LEVEL_ADMINISTRATION = 'administration';

    const GENDER_MALE   = 'male';
    const GENDER_FEMALE = 'female';
    
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

    public static function getSource()
    {
        return "users";
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
        return $data[0] ?? false;
    }

    public static function findAndSet($list, $params=[])
    {
        $keyFrom    = !empty($params["key_from"]) ? $params["key_from"]: "user";
        $keyTo      = !empty($params["key_to"]) ? $params["key_to"]: "user";
        $columns    = !empty($params["columns"]) ? $params["columns"]: ["id", "fullname", "avatar_tiny"];

        $ids = [];
        foreach ($list as $value)
            if (isset($value[$keyFrom]))
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
                if (isset($value["id"]))
                    $dataById[(string)$value["id"]] = $value;
        }
        // ########## end Fetch ############


        $data = [];
        foreach ($list as $value)
        {
            $sourceId = $value[$keyFrom] ?? null;
            $value[$keyTo] = $sourceId !== null && isset($dataById[$sourceId]) ? $dataById[$sourceId] : false;
            $data[] = $value;
        }

        return $data;
    }

    public static function mergePositions($list)
    {
        $data = [];
        foreach ($list as $value)
        {
            $value["position"] = implode(", ", array_map(function ($position){ return $position["title"] ?? "";}, (array)($value["positions"] ?? [])));
            $data[] = $value;
        }
        return $data;
    }

}
