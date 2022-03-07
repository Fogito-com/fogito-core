<?php
namespace Fogito\Db;

interface RemoteModelManagerInterface
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
     * update
     *
     * @param  mixed $filter
     * @param  mixed $properties
     * @param  mixed $options
     * @return bool
     */
    public static function update($filter = [], $properties = [], $options = ['multi' => true, 'upsert' => false]): bool;

    /**
     * insert
     *
     * @param  mixed $properties
     * @return void
     */
    public static function insert($properties = []);

    /**
     * deleteRaw
     *
     * @param  mixed $filter
     * @return bool
     */
    public static function deleteRaw($filter = []): bool;

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
     * request
     *
     * @param  mixed $action
     * @param  mixed $body
     * @return void
     */
    public static function request($action, $body = []);

    /**
     * execute
     *
     * @return void
     */
    public static function execute();

    /**
     * getCurlOptions
     *
     * @param  mixed $action
     * @param  mixed $body
     * @param  mixed $curl
     * @return void
     */
    public static function getCurlOptions($action, $body = [], $curl = []);

    /**
     * makeUrl
     *
     * @param  mixed $action
     * @return void
     */
    public static function makeUrl($action);

    /**
     * getUrl
     *
     * @return void
     */
    public static function getUrl();

    /**
     * setUrl
     *
     * @return void
     */
    public static function setUrl($url);

    /**
     * getSource
     *
     * @return void
     */
    public static function getSource();

    /**
     * setSource
     *
     * @param  mixed $source
     * @return void
     */
    public static function setSource($source);

    /**
     * filterRequestParams
     *
     * @param  mixed $request
     * @return void
     */
    public static function filterRequestParams($parameters = []);

    /**
     * filterResponseData
     *
     * @param  mixed $response
     * @return void
     */
    public static function filterResponseData($response = []);

    /**
     * filterBinds
     *
     * @param  mixed $filter
     * @return void
     */
    public static function filterBinds($filter = []);

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
     * getData
     *
     * @return void
     */
    public static function getData();
}
