<?php
namespace Fogito\Db;

use Fogito\Exception;
use Fogito\Db\RemoteModelManagerInterface;

abstract class RemoteModelManager implements RemoteModelManagerInterface
{
    const STATUS_SUCCESS = 'success';
    const STATUS_ERROR   = 'error';

    protected static $_url;
    protected static $_source;
    protected static $_data       = [];
    protected static $_executable = false;

    /**
     * id
     *
     * @var mixed
     */
    public $id;

    /**
     * find
     *
     * @param  mixed $parameters
     * @return void
     */
    public static function find($parameters = [])
    {
        $options = [];

        if (isset($parameters['columns']) && \is_array($parameters['columns'])) {
            $options['columns'] = $parameters['columns'];
        }

        if (isset($parameters['sort'])) {
            $options['sort'] = $parameters['sort'];
        }

        if (isset($parameters['limit'])) {
            $options['limit'] = $parameters['limit'];
        }

        if (isset($parameters['skip'])) {
            $options['skip'] = $parameters['skip'];
        }

        $filter = [];
        if (isset($parameters[0]) && \is_array($parameters[0])) {
            $filter = $parameters[0];
        }

        $filter = self::filterBinds($filter);
        $data   = \array_merge($options, [
            'filter' => $filter,
        ]);

        $response = self::request('find', [
            'data' => $data,
        ]);

        if ($response['status'] == self::STATUS_SUCCESS) {
            $documents = [];
            foreach ($response['data'] as $i => $document) {
                $documents[$i] = new static();
                foreach ($document as $key => $value) {
                    $documents[$i]->{$key} = $value;
                }
            }
            return $documents;
        }

        return [];
    }

    /**
     * findFirst
     *
     * @param  mixed $parameters
     * @return void
     */
    public static function findFirst($parameters = [])
    {
        $options = [];

        if (isset($parameters['columns']) && \is_array($parameters['columns'])) {
            $options['columns'] = $parameters['columns'];
        }

        if (isset($parameters['sort'])) {
            $options['sort'] = $parameters['sort'];
        }

        $filter = [];
        if (isset($parameters[0]) && \is_array($parameters[0])) {
            $filter = $parameters[0];
        }

        $filter = self::filterBinds($filter);
        $data   = \array_merge($options, [
            'filter' => $filter,
        ]);

        $response = self::request('findfirst', [
            'data' => $data,
        ]);

        if ($response['status'] == self::STATUS_SUCCESS) {
            $document = new static();
            foreach ($response['data'] as $key => $value) {
                $document->{$key} = $value;
            }
            return $document;
        }

        return false;
    }

    /**
     * findById
     *
     * @param  mixed $id
     * @return void
     */
    public static function findById($id)
    {
        return self::findFirst([
            [
                'id' => $id,
            ],
        ]);
    }

    /**
     * update
     *
     * @param  mixed $filter
     * @param  mixed $properties
     * @param  mixed $options
     * @return bool
     */
    public static function update($filter = [], $properties = [], $options = ['multi' => true, 'upsert' => false]): bool
    {
        $filter = static::filterBinds($filter);
        foreach ($properties as $key => $value) {
            if (!\property_exists(static::class, $property)) {
                throw new Exception("Property \$property\ does not exist");
            }
        }

        $response = self::request('update', [
            'data' => [
                'filter' => $filter,
                'update' => $properties,
            ],
        ]);

        return $response['status'] == self::STATUS_SUCCESS;
    }

    /**
     * insert
     *
     * @param  mixed $properties
     * @return void
     */
    public static function insert($properties = [])
    {
        $properties = static::filterBinds($properties);
        foreach ($properties as $key => $value) {
            if (!\property_exists(static::class, $property)) {
                throw new Exception("Property \$property\ does not exist");
            }
        }

        $response = self::request('insert', [
            'data' => [
                'insert' => $properties,
            ],
        ]);

        return $response['status'] == self::STATUS_SUCCESS ? $response['data'] : false;
    }

    /**
     * deleteRaw
     *
     * @param  mixed $filter
     * @return bool
     */
    public static function deleteRaw($filter = []): bool
    {
        $filter   = static::filterBinds($filter);
        $response = self::request('delete', [
            'data' => [
                'filter' => $filter,
            ],
        ]);

        return $response['status'] == self::STATUS_SUCCESS;
    }

    /**
     * delete
     *
     * @return void
     */
    public function delete(): bool
    {
        if (!$this->getId()) {
            return false;
        }

        $this->beforeDelete();
        $res = self::deleteRaw([
            'id' => $this->getId(),
        ]);
        $this->afterDelete();
        return !!$res;
    }

    /**
     * save
     *
     * @param  mixed $forceInsert
     * @return void
     */
    public function save($forceInsert = false): bool
    {
        if (!$this->getId() || $forceInsert) {
            $this->beforeSave($forceInsert);
            $properties = (array) $this;
        } else {
            $this->beforeUpdate();
            $properties = \array_filter((array) $this, function ($x) {
                return $x != 'id';
            }, ARRAY_FILTER_USE_KEY);
        }

        if ($this->id instanceof \MongoDB\BSON\ObjectID) {
            $this->id = $this->getId();
        }

        $properties = self::filterBinds($properties);

        if ($this->id && !$forceInsert) {
            $result = self::update(['id' => $this->id], $properties);
            $this->afterSave($forceInsert);
        } else {
            $result = self::insert($properties);
            $this->id = $result;
            $this->afterUpdate();
        }
        return !!$insertId;
    }

    /**
     * request
     *
     * @param  mixed $action
     * @param  mixed $body
     * @return void
     */
    public static function request($action, $body = [])
    {
        self::execute();
        $curlOptions = static::getCurlOptions($action, $body, [
            CURLOPT_URL            => static::makeUrl($action),
            CURLOPT_POSTFIELDS     => \http_build_query(static::filterRequestParams($body)),
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_HTTPHEADER     => [
                'Content-Type' => 'application/json',
            ],
        ]);

        $ch = \curl_init();
        \curl_setopt_array($ch, $curlOptions);
        $response  = \curl_exec($ch);
        $exception = null;
        if (\curl_errno($ch)) {
            $exception = new Exception(\curl_error($ch));
        }
        \curl_close($ch);

        \parse_str($curlOptions[CURLOPT_POSTFIELDS], $request);
        $response = static::filterResponseData($response);

        self::$_data[$curlOptions[CURLOPT_URL]]['request']  = $request;
        self::$_data[$curlOptions[CURLOPT_URL]]['response'] = $response;

        if ($exception) {
            throw $exception;
        }

        return $response;
    }

    /**
     * execute
     *
     * @return void
     */
    public static function execute()
    {
        $url = static::getUrl();
        if (!$url) {
            throw new Exception('URL not found');
        }

        self::setUrl($url);

        $source = static::getSource();
        if ($source) {
            self::setSource($source);
        }
    }

    /**
     * getCurlOptions
     *
     * @param  mixed $action
     * @param  mixed $body
     * @param  mixed $curl
     * @return void
     */
    public static function getCurlOptions($action, $body = [], $curl = [])
    {
        return $curl;
    }

    /**
     * makeUrl
     *
     * @param  mixed $action
     * @return void
     */
    public static function makeUrl($action)
    {
        $url = rtrim(self::$_url, '/');

        if (self::$_source) {
            $url .= '/' . trim(self::$_source, '/');
        }

        if ($action) {
            $url .= '/' . trim($action, '/');
        }

        return $url;
    }

    /**
     * getUrl
     *
     * @return void
     */
    public static function getUrl()
    {
        return self::$_url;
    }

    /**
     * setUrl
     *
     * @return void
     */
    public static function setUrl($url)
    {
        self::$_url = $url;
    }

    /**
     * getSource
     *
     * @return void
     */
    public static function getSource()
    {
        return self::$_source;
    }

    /**
     * setSource
     *
     * @param  mixed $source
     * @return void
     */
    public static function setSource($source)
    {
        self::$_source = $source;
    }

    /**
     * filterRequestParams
     *
     * @param  mixed $request
     * @return void
     */
    public static function filterRequestParams($parameters = [])
    {
        return $parameters;
    }

    /**
     * filterResponseData
     *
     * @param  mixed $response
     * @return void
     */
    public static function filterResponseData($response = [])
    {
        return \json_decode($response, true);
    }

    /**
     * filterBinds
     *
     * @param  mixed $filter
     * @return void
     */
    public static function filterBinds($filter = [])
    {
        return $filter;
    }

    /**
     * beforeUpdate
     *
     * @return void
     */
    public function beforeUpdate()
    {}

    /**
     * afterUpdate
     *
     * @return void
     */
    public function afterUpdate()
    {}

    /**
     * beforeSave
     *
     * @param  mixed $forceInsert
     * @return void
     */
    public function beforeSave($forceInsert = false)
    {}

    /**
     * afterSave
     *
     * @return void
     */
    public function afterSave()
    {}

    /**
     * beforeDelete
     *
     * @return void
     */
    public function beforeDelete()
    {}

    /**
     * afterDelete
     *
     * @return void
     */
    public function afterDelete()
    {}

    /**
     * getId
     *
     * @return void
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * getIds
     *
     * @param  mixed $documents
     * @return void
     */
    public static function getIds($documents = [])
    {
        $data = [];
        foreach ($documents as $row) {
            $id = $row->getId();
            if (!in_array($id, $data)) {
                $data[] = $id;
            }
        }
        return $data;
    }

    /**
     * toArray
     *
     * @return void
     */
    public function toArray()
    {
        return self::objectToArray($this);
    }

    /**
     * objectToArray
     *
     * @param  mixed $data
     * @return void
     */
    public static function objectToArray($data)
    {
        $attributes = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $attributes[$key] = self::objectToArray($value);
            } elseif (is_object($value)) {
                if ($value instanceof \MongoDB\BSON\ObjectID) {
                    $attributes[$key] = (string) $value;
                } elseif ($value instanceof \MongoDB\BSON\UTCDateTime) {
                    $attributes[$key] = round($value->toDateTime()->format('U.u'), 0);
                } else {
                    $attributes[$key] = self::objectToArray($value);
                }
            } else {
                $attributes[$key] = $value;
            }
        }
        return $attributes;
    }

    /**
     * toTime
     *
     * @param  mixed $property
     * @return void
     */
    public function toTime($property)
    {
        if (!\property_exists($this, $property)) {
            throw new Exception("Property \$property\ does not exist");
        }
        return self::toSeconds($this->{$property});
    }

    /**
     * toDate
     *
     * @param  mixed $property
     * @param  mixed $format
     * @return void
     */
    public function toDate($property, $format = 'Y-m-d H:i:s')
    {
        if (!\property_exists($this, $property)) {
            throw new Exception("Property \$property\ does not exist");
        }
        return self::dateFormat($this->{$property}, $format);
    }

    /**
     * combineById
     *
     * @param  mixed $documents
     * @param  mixed $callback
     * @return void
     */
    public static function combineById($documents = [], $callback = false)
    {
        $data = [];
        foreach ($documents as $row) {
            $combine[$row->getId()] = $callback ? $callback($row) : $row;
        }
        return $data;
    }

    /**
     * combine
     *
     * @param  mixed $key
     * @param  mixed $documents
     * @param  mixed $dynamic
     * @param  mixed $callback
     * @return void
     */
    public static function combine($key, $documents = [], $dynamic = false, $callback = false)
    {
        $data = [];
        foreach ($documents as $row) {
            if (\is_array($row)) {
                if (\array_key_exists((array) $row, $key)) {
                    if ($dynamic) {
                        $data[$row[$key]] = $callback ? $callback($row) : $row;
                    } else {
                        $data[$row[$key]][] = $callback ? $callback($row) : $row;
                    }
                }
            } elseif (\is_object($row)) {
                if (\property_exists($row, $key)) {
                    if ($row->{$key} instanceof \MongoDB\BSON\ObjectID) {
                        if ($dynamic) {
                            $data[$row->getId()] = $callback ? $callback($row) : $row;
                        } else {
                            $data[$row->getId()][] = $callback ? $callback($row) : $row;
                        }
                    } elseif ($row->{$key} instanceof \MongoDB\BSON\UTCDateTime) {
                        if ($dynamic) {
                            $data[round($row->{$key}->toDateTime()->format('U.u'), 0)] = $callback ? $callback($row) : $row;
                        } else {
                            $data[round($row->{$key}->toDateTime()->format('U.u'), 0)][] = $callback ? $callback($row) : $row;
                        }
                    } else {
                        if ($dynamic) {
                            $data[$row->{$key}] = $callback ? $callback($row) : $row;
                        } else {
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
        return (array) array_map(function ($id) {
            return self::objectId($id);
        }, $ids);
    }

    /**
     * Convert string _id to object id
     *
     * @param string $id
     */
    public static function objectId($id)
    {
        if ($id instanceof \MongoDB\BSON\ObjectID) {
            return $id;
        } elseif (preg_match('/^[a-f\d]{24}$/i', $id)) {
            return new \MongoDB\BSON\ObjectID($id);
        }
        return $id;
    }

    /**
     * Validation Mongo ID
     *
     * @param \MongoDB\BSON\ObjectID|string|false $id
     * @return bool
     */
    public static function isMongoId($id): bool
    {
        if (!$id) {
            return false;
        }

        if ($id instanceof \MongoDB\BSON\ObjectID || preg_match('/^[a-f\d]{24}$/i', $id)) {
            return true;
        }

        try {
            new \MongoDB\BSON\ObjectID($id);
            return true;
        } catch (\Exception $e) {
            return false;
        } catch (\MongoException $e) {
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
        foreach ($ids as $id) {
            if (self::isMongoId(trim($id))) {
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
        if ($callback) {
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
    public static function getDate($time = false)
    {
        if (!$time) {
            $time = round(microtime(true) * 1000);
        } else {
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
    public static function toSeconds($date)
    {
        if ($date && \method_exists($date, 'toDateTime')) {
            return round(@$date->toDateTime()->format('U.u'), 0);
        }
        return 0;
    }

    /**
     * getData
     *
     * @return void
     */
    public static function getData()
    {
        return self::$_data;
    }
}
