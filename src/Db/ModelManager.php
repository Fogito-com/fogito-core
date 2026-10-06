<?php

namespace Fogito\Db;

use Fogito\App;
use Fogito\Exception;
use Fogito\Lib\Auth;
use Fogito\Lib\Company;
use Fogito\Lib\Lang;
use ReflectionClass;

abstract class ModelManager
{
    protected static $_server;
    protected static $_db;
    protected static $_source;
    protected static $_connection;

    /** Managers built so far, keyed by DSN (see connect()). */
    protected static $_connections = [];

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
            'query' => (object)$filter // an empty PHP array would be sent as a BSON array
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
        if (!$insertId && isset($parameters['_id']))
            $insertId = $parameters['_id'];
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
                return \array_merge(['background' => true], $row, [
                    'ns' => $ns,
                ]);
            }, $indexes),
        ]));

        return !!$result;
    }

    /**
     * getIndexes
     *
     * Returns the indexes of the collection, without the default "_id_" one.
     * A missing collection has no indexes, so it returns an empty array.
     *
     * <code>
     *      Model::getIndexes();
     *      // [
     *      //     [
     *      //         'name'   => 'company_id_1_is_deleted_1',
     *      //         'key'    => ['company_id' => 1, 'is_deleted' => 1],
     *      //         'unique' => false,
     *      //     ],
     *      // ]
     * </code>
     *
     * @return array
     */
    public static function getIndexes()
    {
        self::execute();

        try
        {
            $cursor = self::$_connection->executeCommand(self::$_db, new \MongoDB\Driver\Command([
                'listIndexes' => self::$_source,
            ]));
        }
        catch (\MongoDB\Driver\Exception\Exception $e)
        {
            return []; // NamespaceNotFound: the collection does not exist yet
        }

        $indexes = [];
        foreach ($cursor as $index)
        {
            if (!isset($index->key) || $index->name === '_id_')
                continue;

            $index = self::objectToArray($index);
            unset($index['v'], $index['ns']);
            $index['key'] = self::normalizeIndexKey($index['key']);
            $index['unique'] = !empty($index['unique']);
            $indexes[] = $index;
        }

        return $indexes;
    }

    /**
     * createIndex
     *
     * Creates one index on the collection. The key is a field name, a list of
     * field names, or a field => direction/type map; field order matters for
     * compound indexes. The name defaults to the one MongoDB would generate.
     * Creating an index that already exists with the same options is a no-op.
     *
     * Indexes are built in the background unless 'background' => false is
     * passed: a foreground build locks the database until it finishes, which
     * freezes the service on big collections. MongoDB 4.2+ ignores the option
     * and always builds without holding the lock for the whole build.
     *
     * <code>
     *      Model::createIndex('company_id');
     *      Model::createIndex(['company_id' => 1, 'created_at' => -1]);
     *      Model::createIndex(['email' => 1], ['unique' => true]);
     *      Model::createIndex(['created_at' => 1], ['expireAfterSeconds' => 300]);
     * </code>
     *
     * @param string|array $key
     * @param array $options name, unique, sparse, expireAfterSeconds, partialFilterExpression, background (true), ...
     * @return bool
     * @throws \MongoDB\Driver\Exception\Exception If MongoDB rejects the index
     */
    public static function createIndex($key, $options = [])
    {
        $key = self::normalizeIndexKey($key);
        if (count($key) === 0)
            throw new Exception('Index key is empty');

        self::execute();

        $index = array_merge(['background' => true], (array)$options, ['key' => $key]);
        if (empty($index['name']))
            $index['name'] = self::getIndexName($key);

        $result = self::$_connection->executeCommand(self::$_db, new \MongoDB\Driver\Command([
            'createIndexes' => self::$_source,
            'indexes'       => [$index],
        ]));

        return !empty($result->toArray()[0]->ok);
    }

    /**
     * checkIndex
     *
     * Checks whether the collection has an index on the given key, in the
     * same field order. Options that are passed (unique, sparse,
     * expireAfterSeconds, ...) must match too; "name" is matched only when it
     * is passed.
     *
     * <code>
     *      Model::checkIndex('company_id');
     *      Model::checkIndex(['email' => 1], ['unique' => true]);
     * </code>
     *
     * @param string|array $key
     * @param array $options
     * @return bool
     */
    public static function checkIndex($key, $options = [])
    {
        $key = self::normalizeIndexKey($key);
        $options = self::objectToArray((array)$options);
        unset($options['background']); // build option, not stored on the index

        foreach (self::getIndexes() as $index)
        {
            if ($index['key'] !== $key)
                continue;

            $matches = true;
            foreach ($options as $option => $value)
            {
                $current = isset($index[$option]) ? $index[$option] : null;
                if (\in_array($option, ['unique', 'sparse', 'hidden'], true))
                {
                    $matches = !empty($current) === !empty($value);
                }
                elseif (\is_numeric($value) && \is_numeric($current))
                {
                    $matches = $current == $value;
                }
                else
                {
                    $matches = $current === $value;
                }

                if (!$matches)
                    break;
            }

            if ($matches)
                return true;
        }

        return false;
    }

    /**
     * normalizeIndexKey
     *
     * 'a' and ['a', 'b'] become ['a' => 1] and ['a' => 1, 'b' => 1]. Numeric
     * directions become integers, because indexes created from the mongo
     * shell store them as doubles (1.0).
     *
     * @param string|array|object $key
     * @return array
     */
    protected static function normalizeIndexKey($key)
    {
        if (\is_string($key))
            return [$key => 1];

        $normalized = [];
        foreach ((array)$key as $field => $direction)
        {
            if (\is_int($field))
                $normalized[(string)$direction] = 1;
            else
                $normalized[$field] = \is_numeric($direction) ? (int)$direction : $direction;
        }

        return $normalized;
    }

    /**
     * getIndexName
     *
     * The name MongoDB generates for a key, e.g. company_id_1_created_at_-1.
     *
     * @param array $key
     * @return string
     */
    protected static function getIndexName($key)
    {
        $parts = [];
        foreach ($key as $field => $direction)
            $parts[] = $field . '_' . $direction;

        return implode('_', $parts);
    }

    /**
     * dropIndex
     *
     * Drops an index by its name or by its key. Returns false when there is
     * no such index.
     *
     * <code>
     *      Model::dropIndex('company_id_1');
     *      Model::dropIndex(['company_id' => 1, 'created_at' => -1]);
     * </code>
     *
     * @param string|array $nameOrKey
     * @return bool
     */
    public static function dropIndex($nameOrKey)
    {
        $indexes = self::getIndexes();
        $name = false;
        foreach ($indexes as $index)
        {
            if (\is_string($nameOrKey) && $index['name'] === $nameOrKey)
                $name = $index['name'];
        }
        if (!$name)
        {
            $key = self::normalizeIndexKey($nameOrKey);
            foreach ($indexes as $index)
            {
                if ($index['key'] === $key)
                    $name = $index['name'];
            }
        }
        if (!$name)
            return false;

        $result = self::$_connection->executeCommand(self::$_db, new \MongoDB\Driver\Command([
            'dropIndexes' => self::$_source,
            'index'       => $name,
        ]));

        return !empty($result->toArray()[0]->ok);
    }

    /**
     * syncIndexes
     *
     * Creates the indexes the model declares in getIndexDefinitions() that
     * the collection does not have yet. An index with the same key and options
     * under another name counts as existing. With $drop, indexes the model
     * does not declare are dropped too.
     *
     * <code>
     *      class Notes extends ModelManager
     *      {
     *          public static function getIndexDefinitions()
     *          {
     *              return [
     *                  ['key' => ['company_id' => 1, 'is_deleted' => 1]],
     *                  ['key' => ['folder_id' => 1]],
     *                  ['key' => ['code' => 1], 'unique' => true],
     *              ];
     *          }
     *      }
     *
     *      Notes::syncIndexes();
     *      // ['created' => ['code_1'], 'existing' => ['company_id_1_is_deleted_1', 'folder_id_1'], 'dropped' => []]
     * </code>
     *
     * @param bool $drop
     * @return array
     */
    public static function syncIndexes($drop = false)
    {
        if (!method_exists(get_called_class(), 'getIndexDefinitions'))
            throw new Exception(get_called_class() . '::getIndexDefinitions() is not defined');

        $report = ['created' => [], 'existing' => [], 'dropped' => []];
        $declared = [];

        foreach ((array)static::getIndexDefinitions() as $definition)
        {
            $definition = (array)$definition;
            if (!isset($definition['key']))
                throw new Exception('Index definition has no "key"');

            $key = self::normalizeIndexKey($definition['key']);
            $options = $definition;
            unset($options['key'], $options['name']);
            $declared[] = $key;

            if (self::checkIndex($key, $options))
            {
                foreach (self::getIndexes() as $index)
                {
                    if ($index['key'] === $key)
                    {
                        $report['existing'][] = $index['name'];
                        break;
                    }
                }
                continue;
            }

            $options = $definition;
            unset($options['key']);
            self::createIndex($key, $options);
            $report['created'][] = !empty($options['name']) ? $options['name'] : self::getIndexName($key);
        }

        if ($drop)
        {
            foreach (self::getIndexes() as $index)
            {
                if (!\in_array($index['key'], $declared, true) && self::dropIndex($index['name']))
                    $report['dropped'][] = $index['name'];
            }
        }

        return $report;
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
     * paginate
     *
     * Returns one page of documents and the total count of the filter.
     * Takes the same parameters as find(); "limit" and "skip" in them are
     * replaced by the page.
     *
     * <code>
     *      Model::paginate([['is_deleted' => ['$ne' => 1]], 'sort' => ['_id' => -1]], 2, 20);
     *      // ['items' => [...], 'total' => 135, 'page' => 2, 'limit' => 20, 'pages' => 7]
     * </code>
     *
     * @param array $parameters
     * @param int $page starts at 1
     * @param int $limit
     * @return array
     */
    public static function paginate($parameters = [], $page = 1, $limit = 20)
    {
        $page  = max(1, (int)$page);
        $limit = max(1, (int)$limit);

        $parameters = (array)$parameters;
        $parameters['limit'] = $limit;
        $parameters['skip']  = ($page - 1) * $limit;

        $total = (int)static::count($parameters);

        return [
            'items' => $total > $parameters['skip'] ? static::find($parameters) : [],
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
            'pages' => (int)ceil($total / $limit),
        ];
    }

    /**
     * exists
     *
     * Checks whether a document matches the filter, fetching only its _id.
     *
     * <code>
     *      Model::exists(['email' => $email]);
     * </code>
     *
     * @param array $filter
     * @return bool
     */
    public static function exists($filter = [])
    {
        return static::findFirst([(array)$filter, 'projection' => ['_id']]) !== false;
    }

    /**
     * findByIds
     *
     * Finds the documents with the given ids. Invalid ids are skipped, so an
     * empty or fully invalid list returns [] without a query.
     *
     * <code>
     *      Model::findByIds(['5f1d...', '5f2a...'], ['sort' => ['_id' => -1]]);
     * </code>
     *
     * @param array $ids strings or ObjectIDs
     * @param array $parameters other find() parameters; its filter is combined with the ids
     * @return array
     */
    public static function findByIds($ids = [], $parameters = [])
    {
        $objectIds = [];
        foreach ((array)$ids as $id)
        {
            if ($id instanceof \MongoDB\BSON\ObjectID)
                $objectIds[(string)$id] = $id;
            elseif (\is_scalar($id) && self::isMongoId(trim((string)$id)) && strlen(trim((string)$id)) === 24)
                $objectIds[trim((string)$id)] = new \MongoDB\BSON\ObjectID(trim((string)$id));
        }

        if (count($objectIds) === 0)
            return [];

        $parameters = (array)$parameters;
        $filter = isset($parameters[0]) && \is_array($parameters[0]) ? $parameters[0] : [];
        $filter['_id'] = ['$in' => array_values($objectIds)];
        $parameters[0] = $filter;

        return static::find($parameters);
    }

    /**
     * distinct
     *
     * Returns the distinct values of a field among the documents that match
     * the filter.
     *
     * <code>
     *      Model::distinct('folder_id', ['is_deleted' => ['$ne' => 1]]);
     * </code>
     *
     * @param string $field
     * @param array $filter
     * @return array
     */
    public static function distinct($field, $filter = [])
    {
        self::execute();
        $filter = static::filterBinds((array)$filter);

        $result = self::$_connection->executeCommand(self::$_db, new \MongoDB\Driver\Command([
            'distinct' => self::$_source,
            'key'      => (string)$field,
            'query'    => (object)$filter,
        ]));

        $values = $result->toArray()[0]->values;
        return \is_array($values) ? $values : [];
    }

    /**
     * findOneAndUpdate
     *
     * Updates the first document that matches the filter and returns it, in
     * one atomic step. $update is either an update document ('$set', '$inc',
     * ...) or plain fields, which are $set.
     *
     * <code>
     *      Model::findOneAndUpdate(['_id' => $id], ['$inc' => ['views' => 1]]);
     *      Model::findOneAndUpdate(['code' => $code], ['status' => 2], ['new' => false]);
     * </code>
     *
     * @param array $filter
     * @param array $update
     * @param array $options sort, upsert (false), new (true: return the updated document), projection
     * @return false|\Fogito\Db\ModelManager
     */
    public static function findOneAndUpdate($filter, $update, $options = [])
    {
        self::execute();

        $update = (array)$update;
        if (count($update) === 0)
            throw new Exception('Update is empty');

        $isOperators = strpos((string)key($update), '$') === 0;
        $command = [
            'findAndModify' => self::$_source,
            'query'         => (object)static::filterBinds((array)$filter),
            'update'        => $isOperators ? $update : ['$set' => $update],
            'new'           => !isset($options['new']) || $options['new'] !== false,
            'upsert'        => isset($options['upsert']) && $options['upsert'] === true,
        ];
        if (isset($options['sort']))
            $command['sort'] = $options['sort'];
        if (isset($options['projection']))
            $command['fields'] = \array_fill_keys($options['projection'], true);

        $result = self::$_connection->executeCommand(self::$_db, new \MongoDB\Driver\Command($command));
        $document = $result->toArray()[0]->value;
        if (!$document)
            return false;

        $static = new static();
        foreach ($document as $key => $value)
        {
            $static->{$key} = $value;
        }
        return $static;
    }

    /**
     * addToSet
     *
     * Adds values to an array field, skipping values it already contains.
     *
     * <code>
     *      Model::addToSet(['_id' => $id], 'users', [$userId1, $userId2]);
     * </code>
     *
     * @param array $filter
     * @param string $field
     * @param mixed $values one value or a list of values
     * @param array $options multi (true), upsert (false)
     * @return bool
     */
    public static function addToSet($filter, $field, $values, $options = [])
    {
        return self::updateArray($filter, ['$addToSet' => [$field => ['$each' => self::toList($values)]]], $options);
    }

    /**
     * pull
     *
     * Removes values from an array field.
     *
     * <code>
     *      Model::pull(['_id' => $id], 'users', $userId);
     * </code>
     *
     * @param array $filter
     * @param string $field
     * @param mixed $values one value or a list of values
     * @param array $options multi (true), upsert (false)
     * @return bool
     */
    public static function pull($filter, $field, $values, $options = [])
    {
        return self::updateArray($filter, ['$pull' => [$field => ['$in' => self::toList($values)]]], $options);
    }

    /**
     * softDelete
     *
     * Marks the matching documents as deleted (is_deleted, deleter_id,
     * deleted_at) instead of removing them. The filter must not be empty, so
     * a missing condition cannot delete the whole collection.
     *
     * <code>
     *      Model::softDelete(['_id' => Model::objectId($id)]);
     * </code>
     *
     * @param array $filter
     * @param array $options multi (true)
     * @return bool
     */
    public static function softDelete($filter, $options = [])
    {
        $filter = (array)$filter;
        if (count($filter) === 0)
            throw new Exception('softDelete filter is empty');

        return static::update($filter, [
            'is_deleted' => 1,
            'deleter_id' => (string)Auth::getId(),
            'deleted_at' => self::getDate(),
        ], ['multi' => !isset($options['multi']) || $options['multi'] !== false, 'upsert' => false]);
    }

    /**
     * restore
     *
     * Undoes softDelete() on the matching deleted documents.
     *
     * <code>
     *      Model::restore(['_id' => Model::objectId($id)]);
     * </code>
     *
     * @param array $filter
     * @param array $options multi (true)
     * @return bool
     */
    public static function restore($filter, $options = [])
    {
        $filter = (array)$filter;
        if (count($filter) === 0)
            throw new Exception('restore filter is empty');

        $filter['is_deleted'] = 1;
        return self::updateArray($filter, [
            '$set'   => ['is_deleted' => 0],
            '$unset' => ['deleter_id' => true, 'deleted_at' => true],
        ], $options);
    }

    /**
     * updateArray
     *
     * Runs an update document (operators) on the matching documents.
     *
     * @param array $filter
     * @param array $update
     * @param array $options multi (true), upsert (false)
     * @return bool
     */
    protected static function updateArray($filter, $update, $options = [])
    {
        self::execute();
        $filter = static::filterBinds((array)$filter);

        $query = new \MongoDB\Driver\BulkWrite;
        $query->update($filter, $update, [
            'multi'  => !isset($options['multi']) || $options['multi'] !== false,
            'upsert' => isset($options['upsert']) && $options['upsert'] === true,
        ]);
        $result = self::$_connection->executeBulkWrite(self::$_db . '.' . self::$_source, $query);
        return !!$result;
    }

    /**
     * toList
     *
     * @param mixed $values
     * @return array
     */
    protected static function toList($values)
    {
        return \is_array($values) ? array_values($values) : [$values];
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
            $this->afterUpdate();
        }
        else
        {
            $result = self::insert($properties);
            $this->_id = self::objectId($result);
            $this->afterSave($forceInsert);
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

        // Not filterBinds(): the sequence document is keyed by _id alone, and an
        // added company_id would make the upsert insert a duplicate _id for
        // every company other than the one that created the sequence.
        $filter = ['_id' => $id];

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
            $bulk->insert(static::filterInsertBinds((array)$row));
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

        self::connect();
    }

    /**
     * connect
     *
     * Selects the connection of the server set by execute(). A model that
     * declares its own database must authenticate with its own credentials,
     * so connections are pooled per DSN rather than kept as a single one:
     * otherwise every model reuses whichever connection was opened first in
     * the request, and only its database name changes.
     *
     * @return void
     */
    public static function connect()
    {
        $dsn = self::getDsn(self::$_server);

        if (!isset(self::$_connections[$dsn]))
            self::$_connections[$dsn] = new \MongoDB\Driver\Manager($dsn);

        self::$_connection = self::$_connections[$dsn];
    }

    /**
     * getDsn
     *
     * The credentials are percent encoded: ":", "/", "?", "#", "[", "]" and
     * "@" are reserved in a connection string, so a password containing one
     * of them would otherwise be cut short or misparsed.
     *
     * @param array $server
     * @return string
     */
    public static function getDsn($server = [])
    {
        if (!$server['username'] || !$server['password'])
            return 'mongodb://' . $server['host'] . ':' . $server['port'];

        return 'mongodb://' . rawurlencode($server["username"]) . ':' . rawurlencode($server["password"])
            . '@' . $server["host"] . ':' . $server["port"] . '/' . $server["dbname"];
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

        $match = static::filterBinds(isset($filter[0]) ? (array)$filter[0] : []);
        if (count($match) > 0)
        {
            $pipleLine[] = ['$match' => $match];
        }
        if (isset($filter["sort"]))
        {
            $pipleLine[] = ['$sort' => $filter["sort"]];
        }

        if (isset($filter["skip"]))
        {
            $pipleLine[] = ['$skip' => $filter["skip"]];
        }

        if (isset($filter["limit"]))
        {
            $pipleLine[] = ['$limit' => $filter["limit"]];
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
