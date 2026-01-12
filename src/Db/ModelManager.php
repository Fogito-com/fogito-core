<?php

namespace Fogito\Db;

use Fogito\App;
use Fogito\Exception;
use Fogito\Lib\Company;
use Fogito\Lib\Lang;
use ReflectionClass;

abstract class ModelManager
{
    protected static $_server;
    protected static $_db;
    protected static $_source;
    protected static $_connection;
    protected static $_shared = false;

    /**
     * _id
     *
     * @var mixed
     */
    public $_id;

    /**
     * find
     *
     * @param mixed $parameters
     * @return array
     */
    public static function find($parameters = [])
    {
        self::execute();

        $options = [];
        if (isset($parameters['sort']))
        {
            $options['sort'] = $parameters['sort'];
        }

        if (isset($parameters['limit']))
        {
            $options['limit'] = $parameters['limit'];
        }

        if (isset($parameters['skip']))
        {
            $options['skip'] = $parameters['skip'];
        }

        if (isset($parameters['projection']))
        {
            $options['projection'] = \array_fill_keys($parameters['projection'], true);
        }

        $filter = [];
        if (isset($parameters[0]) && \is_array($parameters[0]))
        {
            $filter = $parameters[0];
        }

        $filter = static::filterBinds($filter);

        $query = self::$_connection->executeQuery(self::$_db . '.' . self::$_source, new \MongoDB\Driver\Query($filter, $options));
        $documents = [];
        foreach ($query as $i => $document)
        {
            $documents[$i] = new static();
            foreach ($document as $key => $value)
            {
                $documents[$i]->{$key} = $value;
            }
        }

        return $documents;
    }

    /**
     * findFirst
     *
     * @param mixed $parameters
     * @return false|\Fogito\Db\ModelManager
     */
    public static function findFirst($parameters = [])
    {
        self::execute();

        $options = [
            'limit' => 1,
        ];
        if (isset($parameters['sort']))
        {
            $options['sort'] = $parameters['sort'];
        }

        if (isset($parameters['skip']))
        {
            $options['skip'] = $parameters['skip'];
        }

        if (isset($parameters['projection']))
        {
            $options['projection'] = \array_fill_keys($parameters['projection'], true);
        }

        $filter = [];
        if (isset($parameters[0]) && \is_array($parameters[0]))
        {
            $filter = $parameters[0];
        }

        $filter = static::filterBinds($filter);

        $query = self::$_connection->executeQuery(self::$_db . '.' . self::$_source, new \MongoDB\Driver\Query($filter, $options));
        foreach ($query as $document)
        {
            $static = new static();
            foreach ($document as $key => $value)
            {
                $static->{$key} = $value;
            }
            return $static;
        }

        return false;
    }

    /**
     * findById
     *
     * @param mixed $id
     * @return false|\Fogito\Db\ModelManager
     */
    public static function findById($id, $parameters = [])
    {
        self::execute();
        $filter = static::filterBinds(['_id' => self::objectId($id)]);

        $query = self::$_connection->executeQuery(self::$_db . '.' . self::$_source, new \MongoDB\Driver\Query($filter, []));
        foreach ($query as $document)
        {
            $static = new static();
            foreach ($document as $key => $value)
            {
                $static->{$key} = $value;
            }
            return $static;
        }

        return false;
    }

    /**
     * count
     *
     * @param mixed $parameters
     * @return integer
     */
    public static function count($parameters = [])
    {
        self::execute();

        $filter = [];
        if (isset($parameters[0]) && \is_array($parameters[0]))
        {
            $filter = $parameters[0];
        }

        $filter = static::filterBinds($filter);

        $query = self::$_connection->executeCommand(self::$_db, new \MongoDB\Driver\Command([
            'count' => self::$_source,
            'query' => $filter
        ]));
        return $query->toArray()[0]->n;
    }

    /**
     * update
     *
     * @param mixed $filter
     * @param mixed $set
     * @param mixed $options
     * @return bool
     */
    public static function update($filter = [], $set = [], $options = [])
    {
        self::execute();

        $queryOptions = [
            'multi'  => $options['multi'] === false ? false : true,
            'upsert' => $options['upsert'] === true ? true : false,
        ];
        $filter = static::filterBinds($filter);

        $query = new \MongoDB\Driver\BulkWrite;
        $query->update(
            $filter,
            ['$set' => $set],
            $queryOptions
        );
        $result = self::$_connection->executeBulkWrite(self::$_db . '.' . self::$_source, $query);
        return !!$result;
    }

    /**
     * insert
     *
     * @param mixed $parameters
     * @return string|false
     */
    public static function insert($parameters = [])
    {
        self::execute();
        $parameters = static::filterInsertBinds($parameters);

        $query = new \MongoDB\Driver\BulkWrite;
        $insertId = $query->insert($parameters);
        $result = self::$_connection->executeBulkWrite(self::$_db . '.' . self::$_source, $query);

        return $insertId ? $insertId : false;
    }

    /**
     * increment
     *
     * @param mixed $filter
     * @param mixed $inc
     * @param mixed $options
     * @return bool
     */
    public static function increment($filter = [], $inc = [], $options = [])
    {
        self::execute();

        $queryOptions = [
            'multi'  => $options['multi'] === false ? false : true,
            'upsert' => $options['upsert'] === true ? true : false,
        ];
        $filter = static::filterBinds($filter);

        $query = new \MongoDB\Driver\BulkWrite;
        $query->update(
            $filter,
            ['$inc' => $inc],
            $queryOptions
        );
        $result = self::$_connection->executeBulkWrite(self::$_db . '.' . self::$_source, $query);
        return !!$result;
    }


    public static function updateAndIncrement($filter, $update, $increment, $options = [])
    {
        self::execute();
        $filter = self::filterBinds((array)$filter);
        if (is_array($increment['is_deleted']) && array_key_exists('$ne', $increment['is_deleted']))
        {
            $increment['is_deleted'] = ((int)$increment['is_deleted']['$ne'] == 1 ? 0 : 1);
        }

        $queryOptions = [
            'multi'  => $options['multi'] === false ? false : true,
            'upsert' => $options['upsert'] === true ? true : false,
        ];
        $query = new \MongoDB\Driver\BulkWrite;
        $query->update(
            $filter,
            [
                '$set' => $update,
                '$inc' => $increment,
            ],
            $queryOptions
        );
        $result = self::$_connection->executeBulkWrite(self::$_db . '.' . self::$_source, $query);

        if ($result)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * removeColumns
     *
     * @param mixed $filter
     * @param mixed $unset
     * @param mixed $options
     * @return bool
     */
    public static function removeColumns($filter = [], $unset = [], $options = ['multi' => true])
    {
        self::execute();
        $filter = static::filterBinds($filter);

        $query = new \MongoDB\Driver\BulkWrite;
        $query->update(
            $filter,
            ['$unset' => $unset],
            $options
        );
        $result = self::$_connection->executeBulkWrite(self::$_db . '.' . self::$_source, $query);
        return !!$result;
    }

    /**
     * renameColumns
     *
     * <code>
     *      Model::renameColumns([
     *          'is_deleted' => [
     *              '$ne' => 1
     *          ]
     *      ],[
     *          '<column name>' => true
     *      ]);
     * </code>
     *
     * @param mixed $filter
     * @param mixed $rename
     * @param mixed $options
     * @return bool
     */
    public static function renameColumns($filter = [], $rename = [], $options = ['multi' => true])
    {
        self::execute();
        $filter = static::filterBinds($filter);

        $query = new \MongoDB\Driver\BulkWrite;
        $query->update(
            $filter,
            ['$rename' => $rename],
            $options
        );
        $result = self::$_connection->executeBulkWrite(self::$_db . '.' . self::$_source, $query);
        return !!$result;
    }

    /**
     * createIndexes
     *
     * <code>
     *      Model::createIndexes([
     *          [
     *              'name' => 'company_id',
     *              'key'  => [
     *                  'company_id' => 1
     *              ],
     *              'unique' => true,
     *              'expireAfterSeconds' => 300
     *          ]
     *      ]);
     * </code>
     *
     * @param mixed $indexes
     * @return bool
     */
    public static function createIndexes($indexes = [])
    {
        self::execute();

        $ns = self::$_db . '.' . self::$_source;
        $result = self::$_connection->executeCommand(self::$_db, new \MongoDB\Driver\Command([
            'createIndexes' => self::$_source,
            'indexes'       => \array_map(function ($row) use ($ns)
            {
                return \array_merge($row, [
                    'ns' => $ns,
                ]);
            }, $indexes),
        ]));

        return !!$result;
    }

    /**
     * deleteRaw
     *
     * @param mixed $filter
     * @param mixed $options
     * @return bool
     */
    public static function deleteRaw($filter = [], $options = ['limit' => 0])
    {
        self::execute();

        $queryOptions = [
            "limit" => (int)$options["limit"]
        ];
        $filter = static::filterBinds($filter);

        $query = new \MongoDB\Driver\BulkWrite;
        $query->delete(
            $filter,
            $queryOptions
        );
        $result = self::$_connection->executeBulkWrite(self::$_db . '.' . self::$_source, $query);
        return !!$result;
    }

    /**
     * delete
     *
     * @return bool
     */
    public function delete()
    {
        if (!$this->getId())
        {
            return false;
        }

        $this->beforeDelete();
        $res = self::deleteRaw([
            '_id' => self::objectId($this->getId()),
        ]);
        $this->afterDelete();
        return !!$res;
    }


    public static function sum($field, $filter = [])
    {
        self::execute();

        $pipleLine = [];
        $filter = self::filterBinds((array)$filter[0]);
        if (count((array)$filter) > 0)
        {
            $pipleLine[] = ['$match' => $filter];
        }

        $pipleLine[] = [
            '$group' => [
                '_id'   => '$asdak',
                'total' => ['$sum' => '$' . $field],
                'count' => ['$sum' => 1]
            ],
        ];
        $Command = new \MongoDB\Driver\Command([
            'aggregate' => self::$_source,
            'pipeline'  => $pipleLine,
            "cursor"    => ["batchSize" => 1]
        ]);

        $Result = self::$_connection->executeCommand(self::$_db, $Command);
        return $Result->toArray()[0]->total;
    }


    /**
     * save
     *
     * @param mixed $forceInsert
     * @return bool
     */
    public function save($forceInsert = false)
    {
        if (isset($this->_id) && !$this->_id instanceof \MongoDB\BSON\ObjectID)
        {
            $this->_id = self::objectId($this->_id);
        }

        if (!$this->_id || $forceInsert)
        {
            $this->beforeSave($forceInsert);
            $properties = (array)$this;
            if (!$forceInsert)
            {
                unset($properties['_id']);
            }
        }
        else
        {
            $this->beforeUpdate();
            $properties = (array)$this;
            unset($properties['_id']);
        }

        $properties = static::filterInsertBinds($properties);

        if ($this->_id && !$forceInsert)
        {
            $result = self::update(['_id' => $this->_id], $properties);
            $this->afterSave($forceInsert);
        }
        else
        {
            $result = self::insert($properties);
            $this->_id = self::objectId($result);
            $this->afterUpdate();
        }
        return $result;
    }

    /**
     * Returns a list of indexes for the current MongoDB collection.
     *
     * This function queries MongoDB for all defined indexes on the current collection (`self::$_source`)
     * and returns them as a normalized array where each item contains the sorted list of index keys.
     *
     * @return array List of existing index key combinations, sorted and normalized.
     * @throws \MongoDB\Driver\Exception\Exception If the command execution fails.
     */
    public static function getIndexes(): array
    {
        self::init();

        $command = new \MongoDB\Driver\Command([
            'listIndexes' => self::$_source,
        ]);

        try
        {
            $cursor = self::$_connection->executeCommand(self::$_db, $command);
            $indexes = iterator_to_array($cursor);
        }
        catch (\MongoDB\Driver\Exception\Exception $e)
        {
            return [];
        }

        $result = [];

        foreach ($indexes as $index)
        {
            if (!isset($index->key))
            {
                continue;
            }

            if ($index->key->_id)
            {
                continue;
            }

            $result[] = $index;
        }

        return $result;
    }

    /**
     * beforeUpdate
     *
     * @return void
     */
    public function beforeUpdate()
    {
    }

    /**
     * afterUpdate
     *
     * @return void
     */
    public function afterUpdate()
    {
    }

    /**
     * beforeSave
     *
     * @param mixed $forceInsert
     * @return void
     */
    public function beforeSave($forceInsert = false)
    {
    }

    /**
     * afterSave
     *
     * @return void
     */
    public function afterSave()
    {
    }

    /**
     * beforeDelete
     *
     * @return void
     */
    public function beforeDelete()
    {
    }

    /**
     * afterDelete
     *
     * @return void
     */
    public function afterDelete()
    {
    }

    /**
     * getId
     *
     * @return string
     */
    public function getId()
    {
        return (string)$this->_id;
    }

    /**
     * getIds
     *
     * @param mixed $documents
     * @return array
     */
    public static function getIds($documents = [])
    {
        $data = [];
        foreach ($documents as $row)
        {
            $id = $row->getId();
            if (!in_array($id, (array)$data))
            {
                $data[] = $id;
            }
        }
        return $data;
    }

    /**
     * toArray
     *
     * @return array
     */
    public function toArray()
    {
        return self::objectToArray($this);
    }

    /**
     * objectToArray
     *
     * @param mixed $data
     * @return array
     */
    public static function objectToArray($data)
    {
        $attributes = [];
        foreach ($data as $key => $value)
        {
            if (is_array($value))
            {
                $attributes[$key] = self::objectToArray($value);
            }
            elseif (is_object($value))
            {
                if ($value instanceof \MongoDB\BSON\ObjectID)
                {
                    $attributes[$key] = (string)$value;
                }
                elseif ($value instanceof \MongoDB\BSON\UTCDateTime)
                {
                    $attributes[$key] = round($value->toDateTime()->format('U.u'), 0);
                }
                else
                {
                    $attributes[$key] = self::objectToArray($value);
                }
            }
            else
            {
                $attributes[$key] = $value;
            }
        }
        return $attributes;
    }

    /**
     * toTime
     *
     * @param mixed $property
     * @return integer
     */
    public function toTime($property)
    {
        if (!\property_exists($this, $property))
        {
            $reflection = new ReflectionClass(get_class($this));
            throw new Exception("Property $property does not exist in " . $reflection->getNamespaceName());
        }
        return self::toSeconds($this->{$property});
    }

    /**
     * toDate
     *
     * @param mixed $property
     * @param mixed $format
     * @return string
     */
    public function toDate($property, $format = 'Y-m-d H:i:s')
    {
        if (!\property_exists($this, $property))
        {
            $reflection = new ReflectionClass(get_class($this));
            throw new Exception("Property $property does not exist in " . $reflection->getNamespaceName());
        }
        return self::dateFormat($this->{$property}, $format);
    }

    /**
     * combineById
     *
     * @param mixed $documents
     * @param mixed $callback
     * @return array
     */
    public static function combineById($documents = [], $callback = false)
    {
        $data = [];
        foreach ($documents as $row)
        {
            $data[$row->getId()] = $callback ? $callback($row) : $row;
        }
        return $data;
    }

    /**
     * combine
     *
     * @param mixed $key
     * @param mixed $documents
     * @param mixed $dynamic
     * @param mixed $callback
     * @return array
     */
    public static function combine($key, $documents = [], $dynamic = false, $callback = false)
    {
        $data = [];
        foreach ($documents as $row)
        {
            if (\is_array($row))
            {
                if (\array_key_exists($key, (array)$row))
                {
                    if ($dynamic)
                    {
                        $data[$row[$key]] = $callback ? $callback($row) : $row;
                    }
                    else
                    {
                        $data[$row[$key]][] = $callback ? $callback($row) : $row;
                    }
                }
            }
            elseif (\is_object($row))
            {
                if (\property_exists($row, $key))
                {
                    if ($row->{$key} instanceof \MongoDB\BSON\ObjectID)
                    {
                        if ($dynamic)
                        {
                            $data[$row->getId()] = $callback ? $callback($row) : $row;
                        }
                        else
                        {
                            $data[$row->getId()][] = $callback ? $callback($row) : $row;
                        }
                    }
                    elseif ($row->{$key} instanceof \MongoDB\BSON\UTCDateTime)
                    {
                        if ($dynamic)
                        {
                            $data[round($row->{$key}->toDateTime()->format('U.u'), 0)] = $callback ? $callback($row) : $row;
                        }
                        else
                        {
                            $data[round($row->{$key}->toDateTime()->format('U.u'), 0)][] = $callback ? $callback($row) : $row;
                        }
                    }
                    else
                    {
                        if ($dynamic)
                        {
                            $data[$row->{$key}] = $callback ? $callback($row) : $row;
                        }
                        else
                        {
                            $data[$row->{$key}][] = $callback ? $callback($row) : $row;
                        }
                    }
                }
            }
        }
        return $data;
    }

    /**
     * Convert ids to ObjectID
     *
     * @param array $ids
     * @return array
     */
    public static function convertIds($ids = [])
    {
        $objIds = [];
        foreach ($ids as $id)
            $objIds[] = self::objectId($id);
        return $objIds;
    }

    public static function getNewId($type=false)
    {
        self::execute();

        $id = static::$_source;
        if($type)
            $id .= '_'.$type;

        $filter = static::filterBinds(['_id' => $id]);

        $command = new \MongoDB\Driver\Command([
            'findAndModify' => 'collection_sequences',
            'query' => $filter,
            'update' => ['$inc' => ['seq' => 1]],
            'new' => true,
            'upsert' => true
        ]);

        $cursor = self::$_connection->executeCommand(self::$_db, $command);
        $result = current($cursor->toArray());

        if (isset($result->value->seq)) {
            return $result->value->seq;
        } else {
            return false;
        }
    }

    /**
     * Convert string _id to object id
     *
     * @param string $id
     * @return false|\MongoDB\BSON\ObjectID
     */
    public static function objectId($id)
    {
        if (strlen($id) < 5)
        {
            return false;
        }
        elseif ($id instanceof \MongoDB\BSON\ObjectID)
        {
            return $id;
        }
        elseif (preg_match('/^[a-f\d]{24}$/i', $id))
        {
            return new \MongoDB\BSON\ObjectID($id);
        }
        throw new \Exception("Object ID is wrong");
    }

    public static function insertBulk($data)
    {
        self::execute();
        $bulk = new \MongoDB\Driver\BulkWrite;
        foreach ($data as $row)
        {
            $data = self::filterBinds((array)$row);
            $bulk->insert($data);
        }
        $result = self::$_connection->executeBulkWrite(self::$_db . '.' . self::$_source, $bulk);
        if ($result->getWriteErrors())
        {
            return false;
        }
        return true;
    }

    public static function updateBulk($data, $options = [])
    {
        self::execute();
        $queryOptions = [
            'multi'  => $options['multi'] === false ? false : true,
            'upsert' => $options['upsert'] === true ? true : false,
        ];
        $bulk = new \MongoDB\Driver\BulkWrite;
        foreach ($data as $row)
        {
            $filter = static::filterBinds($row[0]);
            $bulk->update(
                $filter,
                ['$set' => $row[1]], $queryOptions
            );
        }
        $result = self::$_connection->executeBulkWrite(self::$_db . '.' . self::$_source, $bulk);
        if ($result->getWriteErrors())
        {
            return false;
        }
        return true;
    }

    /**
     * Ensures every "$in" operator receives an array.
     *
     * @param mixed $filter Filter data (recursive)
     * @return mixed Normalized filter
     */
    protected static function normalizeMongoInOperator($filter)
    {
        if (!is_array($filter)) {
            return $filter;
        }

        foreach ($filter as $k => $v) {
            if ($k === '$in') {
                // if scalar/null/object => wrap as array; if already array => ok
                if (!is_array($v)) {
                    $filter[$k] = $v === null ? [] : [$v];
                }
                continue;
            }

            if (is_array($v)) {
                $filter[$k] = static::normalizeMongoInOperator($v);
            }
        }

        return $filter;
    }

    /**
     * Validation Mongo ID
     *
     * @param \MongoDB\BSON\ObjectID|string|false $id
     * @return bool
     */
    public static function isMongoId($id)
    {
        if (!$id)
        {
            return false;
        }

        if ($id instanceof \MongoDB\BSON\ObjectID || preg_match('/^[a-f\d]{24}$/i', $id))
        {
            return true;
        }

        try
        {
            new \MongoDB\BSON\ObjectID($id);
            return true;
        }
        catch (\Exception $e)
        {
            return false;
        }
        catch (\MongoException $e)
        {
            return false;
        }
    }

    /**
     * Filter Mongo ID's
     *
     * @param array $ids
     * @return array
     */
    public static function filterMongoIds($ids = [])
    {
        $data = [];
        foreach ($ids as $id)
        {
            if (self::isMongoId(trim($id)))
            {
                $data[] = trim($id);
            }
        }

        return $data;
    }

    /**
     * Filter
     *
     * @param array $binds
     * @param resource $callback
     * @return array
     */
    public static function filter($binds = [], $callback = false)
    {
        if (is_callable($callback))
        {
            return $callback($binds);
        }
        return $binds;
    }

    /**
     * Get mongo date by unixtime
     *
     * @return integer|false $time
     * @return \MongoDB\BSON\UTCDatetime
     */
    public static function getDate($time = false, $round = true)
    {
        if (!$time)
        {
            $time = (int)round(microtime(true) * 1000);
        }
        else if ($round)
        {
            $time *= 1000;
        }
        return new \MongoDB\BSON\UTCDateTime($time);
    }

    /**
     * Format mongo date to string
     *
     * @param \MongoDB\BSON\UTCDateTime $date
     * @param string $format Y-m-d H:i:s
     * @return string
     */
    public static function dateFormat($date, $format = 'Y-m-d H:i:s')
    {
        return date($format, self::toSeconds($date));
    }

    /**
     * Conver mongo date to unixtime
     *
     * @param \MongoDB\BSON\UTCDateTime $date
     * @return integer
     */
    public static function toSeconds($date, $round = true)
    {
        if (is_object($date) && \method_exists($date, 'toDateTime'))
        {
            if ($round)
                return round(@$date->toDateTime()->format('U.u'), 0);
            return round(@$date->toDateTime()->format('U.u'), 3);
        }
        return 0;
    }

    /**
     * execute
     *
     * @return void
     * @throws Exception
     */
    public static function execute()
    {
        $config = App::$di->config->databases->default->toArray();
        if (method_exists(get_class(new static()), 'getConfig'))
            $config = static::getConfig();
        if (!$config["dbname"])
            throw new Exception('Database not found');

        if (!$config)
            throw new Exception('MongoDB server not found');

        $source = static::getSource();
        if (!$source)
            throw new Exception('Collection not found');

        self::setServer($config);
        self::setDb($config["dbname"]);
        self::setSource($source);

        if (!self::$_connection)
            self::connect();
    }

    /**
     * connect
     *
     * @return void
     */
    public static function connect()
    {
        if (!self::$_server['username'] || !self::$_server['password'])
        {
            $dsn = 'mongodb://' . self::$_server['host'] . ':' . self::$_server['port'];
        }
        else
        {
            $dsn = 'mongodb://' . self::$_server["username"] . ':' . self::$_server["password"] . '@' . self::$_server["host"] . ':' . self::$_server["port"] . '/' . self::$_server["dbname"];
        }
        self::$_connection = new \MongoDB\Driver\Manager($dsn);
    }

    /**
     * getConnection
     *
     * @return \MongoDB\Driver\Manager
     */
    public static function getConnection()
    {
        return self::$_connection;
    }

    /**
     * setServer
     *
     * @param mixed $server
     * @return void
     */
    public static function setServer($server = [])
    {
        self::$_server = $server;
    }

    /**
     * getServer
     *
     * @return array
     */
    public static function getServer()
    {
        return self::$_server;
    }

    /**
     * setDb
     *
     * @param mixed $db
     * @return void
     */
    public static function setDb($db)
    {
        self::$_db = $db;
    }

    /**
     * getDb
     *
     * @return string
     */
    public static function getDb()
    {
        return self::$_db;
    }

    /**
     * setSource
     *
     * @param mixed $source
     * @return void
     */
    public static function setSource($source)
    {
        self::$_source = $source;
    }

    /**
     * getSource
     *
     * @return string
     */
    public static function getSource()
    {
        return self::$_source;
    }

    /**
     * filterBinds
     *
     * @param mixed $filter
     * @return array
     */
    public static function filterBinds($filter = [], $options = [])
    {
        $filter = self::normalizeMongoInOperator($filter);
        if (in_array(self::$_source, (array)App::$di->config->skipped_filtering_collections->toArray()))
            return $filter;

        if (method_exists(new static(), "getFindFilters") && count((array)static::getFindFilters()) > 0)
            $filter = array_merge(static::getFindFilters(), $filter);

        if (!isset($filter['business_type']) && !App::$di->config->skip_filter_business_type && defined('BUSINESS_TYPE') && (defined('BUSINESS_TYPE') && BUSINESS_TYPE))
            $filter["business_type"] = BUSINESS_TYPE;

        if (static::$_shared)
        {
            if (count((array)$filter['company_ids']) === 0 && Company::getId())
                $filter["company_ids"] = ['$in' => array_merge(Company::getData()->branch_ids, [Company::getId()])];
        }
        else
        {
            if (!isset($filter['company_id']) && !App::$di->config->skip_filter_company_id && (defined('COMPANY_ID') && COMPANY_ID))
                $filter["company_id"] = COMPANY_ID;
        }

        return $filter;
    }

    public static function filterInsertBinds($filter = [], $options = [])
    {
        if (method_exists(new static(), "getInsertFilters") && count((array)static::getInsertFilters()) > 0)
            $filter = array_merge(static::getInsertFilters(), $filter);

        if (!isset($filter['business_type']) && Company::getData()->business_model)
            $filter["business_type"] = Company::getData()->business_model;

        if (!isset($filter['company_id']) && Company::getId())
            $filter["company_id"] = Company::getId();

        if (static::$_shared)
            if (count((array)$filter['company_ids']) == 0 && Company::getId())
                $filter["company_ids"] = [Company::getId()];

        return $filter;
    }

    public static function aggregate($filter, $fields)
    {
        self::execute();
        $pipleLine = [];

        if (count($filter ?: []) > 0)
        {
            $pipleLine[] = ['$match' => $filter[0]];
        }
        if (isset($filter["sort"]))
        {
            $pipleLine[] = ['$sort' => $filter["sort"]];
        }

        if (isset($filter["limit"]))
        {
            $pipleLine[] = ['$limit' => $filter["limit"]];
        }

        if (isset($filter["skip"]))
        {
            $pipleLine[] = ['$skip' => $filter["skip"]];
        }

        $pipleLine[] = $fields[0];

        $Command = new \MongoDB\Driver\Command([
            'aggregate' => self::$_source,
            'pipeline'  => $pipleLine,
            'cursor'    => ["batchSize" => 1],
        ]);

        $Result = self::$_connection->executeCommand(self::$_db, $Command);
        return $Result->toArray();
    }

    public static function getSchemeByColumn($column)
    {
        return static::getScheme()[$column];
    }

    public static function toMilliSeconds($date)
    {
        if (is_object($date) && method_exists($date, "toDateTime"))
            return round(@$date->toDateTime()->format("U.u") * 1000, 0);
        return 0;
    }

    public static function nextNumber($parameter, $count = 1)
    {
        self::execute();

        $filter = static::filterBinds(['counter' => $parameter]);

        $command = new \MongoDB\Driver\Command([
            'findAndModify' => self::$_source,
            'query'         => $filter,
            'update'        => ['$inc' => ['seq' => $count]],
            'new'           => true,
            'upsert'        => true
        ]);

        $cursor = self::$_connection->executeCommand(self::$_db, $command);
        $result = current($cursor->toArray());

        if (isset($result->value->seq))
        {
            return $result->value->seq;
        }
        else
        {
            return false;
        }
    }


    public static function dateFiltered($date, $format = "Y-m-d H:i:s")
    {
        if (is_object($date) && method_exists($date, "toDateTime"))
        {
            return date($format, self::toSeconds($date));
        }

        return 0;
    }
}
