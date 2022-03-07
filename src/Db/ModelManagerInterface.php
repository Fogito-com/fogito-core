<?php
namespace Fogito\Db;

interface ModelManagerInterface
{
    /**
     * find
     *
     * @param  mixed $parameters
     * @return void
     */
    public static function find($parameters = []);

    /**
     * findFirst
     *
     * @param  mixed $parameters
     * @return void
     */
    public static function findFirst($parameters = []);

    /**
     * findById
     *
     * @param  mixed $id
     * @return void
     */
    public static function findById($id);

    /**
     * count
     *
     * @param  mixed $parameters
     * @return void
     */
    public static function count($parameters = []);

    /**
     * update
     *
     * @param  mixed $filter
     * @param  mixed $set
     * @param  mixed $options
     * @return bool
     */
    public static function update($filter = [], $set = [], $options = ['multi' => true, 'upsert' => false]): bool;

    /**
     * insert
     *
     * @param  mixed $parameters
     * @return void
     */
    public static function insert($parameters = []);

    /**
     * increment
     *
     * @param  mixed $filter
     * @param  mixed $inc
     * @param  mixed $options
     * @return bool
     */
    public static function increment($filter = [], $inc = [], $options = ['multi' => true, 'upsert' => false]): bool;

    /**
     * removeColumns
     *
     * @param  mixed $filter
     * @param  mixed $unset
     * @param  mixed $options
     * @return bool
     */
    public static function removeColumns($filter = [], $unset = [], $options = ['multi' => true]): bool;

    /**
     * renameColumns
     *
     * @param  mixed $filter
     * @param  mixed $rename
     * @param  mixed $options
     * @return bool
     */
    public static function renameColumns($filter = [], $rename = [], $options = ['multi' => true]): bool;

    /**
     * createIndexes
     *
     * @param  mixed $indexes
     * @return bool
     */
    public static function createIndexes($indexes = []): bool;

    /**
     * deleteRaw
     *
     * @param  mixed $filter
     * @param  mixed $options
     * @return void
     */
    public static function deleteRaw($filter = [], $options = ['limit' => 0]);

    /**
     * delete
     *
     * @return void
     */
    public function delete(): bool;

    /**
     * save
     *
     * @param  mixed $forceInsert
     * @return void
     */
    public function save($forceInsert = false): bool;

    /**
     * beforeUpdate
     *
     * @return void
     */
    public function beforeUpdate();

    /**
     * afterUpdate
     *
     * @return void
     */
    public function afterUpdate();

    /**
     * beforeSave
     *
     * @param  mixed $forceInsert
     * @return void
     */
    public function beforeSave($forceInsert = false);

    /**
     * afterSave
     *
     * @return void
     */
    public function afterSave();

    /**
     * beforeDelete
     *
     * @return void
     */
    public function beforeDelete();

    /**
     * afterDelete
     *
     * @return void
     */
    public function afterDelete();

    /**
     * getId
     *
     * @return void
     */
    public function getId();

    /**
     * getIds
     *
     * @param  mixed $documents
     * @return void
     */
    public static function getIds($documents = []);

    /**
     * toArray
     *
     * @return void
     */
    public function toArray();

    /**
     * objectToArray
     *
     * @param  mixed $data
     * @return void
     */
    public static function objectToArray($data);

    /**
     * toTime
     *
     * @param  mixed $property
     * @return void
     */
    public function toTime($property);

    /**
     * toDate
     *
     * @param  mixed $property
     * @param  mixed $format
     * @return void
     */
    public function toDate($property, $format = 'Y-m-d H:i:s');

    /**
     * combineById
     *
     * @param  mixed $documents
     * @param  mixed $callback
     * @return void
     */
    public static function combineById($documents = [], $callback = false);

    /**
     * combine
     *
     * @param  mixed $key
     * @param  mixed $documents
     * @param  mixed $dynamic
     * @param  mixed $callback
     * @return void
     */
    public static function combine($key, $documents = [], $dynamic = false, $callback = false);

    /**
     * Convert ids to ObjectID
     *
     * @param array $ids
     * @return array
     */
    public static function convertIds($ids = []);

    /**
     * Convert string _id to object id
     *
     * @param string $id
     */
    public static function objectId($id);

    /**
     * Validation Mongo ID
     *
     * @param \MongoDB\BSON\ObjectID|string|false $id
     * @return bool
     */
    public static function isMongoId($id): bool;

    /**
     * Filter Mongo ID's
     *
     * @param array $ids
     * @return array
     */
    public static function filterMongoIds($ids = []);

    /**
     * Filter
     *
     * @param array $binds
     * @param resource $callback
     * @return array
     */
    public static function filter($binds = [], $callback = false);

    /**
     * Get mongo date by unixtime
     *
     * @return integer|false $time
     * @return \MongoDB\BSON\UTCDatetime
     */
    public static function getDate($time = false);

    /**
     * Format mongo date to string
     *
     * @param \MongoDB\BSON\UTCDateTime $date
     * @param string $format Y-m-d H:i:s
     * @return string
     */
    public static function dateFormat($date, $format = 'Y-m-d H:i:s');

    /**
     * Conver mongo date to unixtime
     *
     * @param \MongoDB\BSON\UTCDateTime $date
     * @return integer
     */
    public static function toSeconds($date);

    /**
     * execute
     *
     * @return void
     */
    public static function execute();

    /**
     * connect
     *
     * @return void
     */
    public static function connect();

    /**
     * getConnection
     *
     * @return void
     */
    public static function getConnection();

    /**
     * setServer
     *
     * @param  mixed $server
     * @return void
     */
    public static function setServer($server = []);

    /**
     * getServer
     *
     * @return void
     */
    public static function getServer();

    /**
     * setDb
     *
     * @param  mixed $db
     * @return void
     */
    public static function setDb($db);

    /**
     * getDb
     *
     * @return void
     */
    public static function getDb();

    /**
     * setSource
     *
     * @param  mixed $source
     * @return void
     */
    public static function setSource($source);

    /**
     * getSource
     *
     * @return void
     */
    public static function getSource();

    /**
     * filterBinds
     *
     * @param  mixed $filter
     * @return void
     */
    public static function filterBinds($filter = []);
}
