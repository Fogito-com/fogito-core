<?php
/**
 * @author Tural Ilyasov <senior2ral@gmail.com>
 * @link https://github.com/Fogito-com/fogito-core
 * @version 1.0.2
 * @package Fogito-Core
*/
namespace Fogito\Db;

use Fogito\App;
use Fogito\Db\RemoteModelManagerInterface;
use Fogito\Exception;
use Fogito\Lib\Auth;
use Fogito\Lib\Lang;
use ReflectionClass;

abstract class RemoteModelManager implements RemoteModelManagerInterface
{
    const STATUS_SUCCESS = 'success';
    const STATUS_ERROR   = 'error';

    protected static $_url = 'https://s2s.fogito.com';
    protected static $_source;
    protected static $_data       = [];
    protected static $_executable = false;
    protected static $_exception;

    /**
     * id
     *
     * @var mixed
     */
    public $id;

    /**
     * __construct
     *
     * @return void
     */
    public function __construct()
    {
        if (!$this->id) {
            $this->id = new \MongoDB\BSON\ObjectID();
        }
    }

    /**
     * find
     *
     * @param  mixed $parameters
     * @return array
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

        $filter = static::filterBinds($filter);
        $data   = \array_merge($options, [
            'filter' => $filter,
        ]);

        $response = self::request('find', [
            'data' => $data,
        ]);

        if ($response->status == self::STATUS_SUCCESS) {
            $documents = [];
            foreach ($response->data as $i => $document) {
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
     * @return false|\Fogito\Db\RemoteModelManager
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

        $filter = static::filterBinds($filter);
        $data   = \array_merge($options, [
            'filter' => $filter,
        ]);

        $response = self::request('findfirst', [
            'data' => $data,
        ]);

        if ($response->status == self::STATUS_SUCCESS) {
            $document = new static();
            foreach ($response->data as $key => $value) {
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
     * @return false|\Fogito\Db\RemoteModelManager
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
    public static function update($filter = [], $properties = [], $options = ['multi' => true, 'upsert' => false])
    {
        $filter   = static::filterBinds($filter);
        $response = self::request('update', [
            'data' => [
                'filter' => $filter,
                'update' => static::filterUpdateData($properties),
            ],
        ]);

        return $response->status == self::STATUS_SUCCESS;
    }

    /**
     * insert
     *
     * @param  mixed $properties
     * @return string|bool
     */
    public static function insert($properties = [])
    {
        $properties = static::filterBinds($properties);
        $response   = self::request('insert', [
            'data' => [
                'insert' => static::filterInsertData($properties),
            ],
        ]);

        return $response->status == self::STATUS_SUCCESS ? $response->data->id : false;
    }

    /**
     * deleteRaw
     *
     * @param  mixed $filter
     * @return bool
     */
    public static function deleteRaw($filter = [])
    {
        $filter   = static::filterBinds($filter);
        $response = self::request('delete', [
            'data' => [
                'filter' => $filter,
            ],
        ]);

        return $response->status == self::STATUS_SUCCESS;
    }

    /**
     * delete
     *
     * @return bool
     */
    public function delete()
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
     * @return bool
     */
    public function save($forceInsert = false)
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

        $properties = static::filterBinds($properties);

        if ($this->id && !$forceInsert) {
            $result = self::update(['id' => $this->id], $properties);
            $this->afterUpdate();
        } else {
            $result = self::insert($properties);
            if (!$this->id) {
                $this->id = $result;
            }
            $this->afterSave($forceInsert);
        }

        return !!$result;
    }

    /**
     * request
     *
     * @param  mixed $action
     * @param  mixed $body
     */
    public static function request($action, $body = [])
    {
        self::$_exception = null;
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
        $response = \curl_exec($ch);
        if (\curl_errno($ch)) {
            self::$_exception = new \Exception(\curl_error($ch));
        }
        \curl_close($ch);

        \parse_str($curlOptions[CURLOPT_POSTFIELDS], $request);
        $response = static::filterResponseData($response);

        self::$_data[$curlOptions[CURLOPT_URL]]['request']  = $request;
        self::$_data[$curlOptions[CURLOPT_URL]]['response'] = $response;

        if ($response->status == self::STATUS_ERROR) {
            self::$_exception = new \Exception($response->description, $response->error_code);
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
     * @return array
     */
    public static function getCurlOptions($action, $body = [], $curl = [])
    {
        return $curl;
    }

    /**
     * makeUrl
     *
     * @param  mixed $action
     * @return string
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
     * @return string
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
     * @return string
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
     * @return array
     */
    public static function filterRequestParams($parameters = [])
    {
        $parameters = \array_merge($parameters, [
            'collection'  => static::getSource(),
            'http_origin' => $_SERVER['HTTP_ORIGIN'],
            'lang'        => Lang::getLang(),
        ]);

        $s2s = App::$di->config->s2s;
        if (\is_object($s2s)) {
            $parameters = \array_merge($parameters, $s2s->toArray());
        }

        if ($token = Auth::getToken())
            $parameters['token'] = $token;
        if ($tokenUser = Auth::getTokenUser())
            $parameters['token_user'] = $tokenUser;
        if (Auth::isAuth())
            $parameters['token_user'] = Auth::getId();

        return $parameters;
    }

    /**
     * filterResponseData
     *
     * @param  mixed $response
     * @return string
     */
    public static function filterResponseData($response = [])
    {
        return \json_decode($response);
    }

    /**
     * filterBinds
     *
     * @param  mixed $filter
     * @return array
     */
    public static function filterBinds($filter = [])
    {
        return $filter;
    }

    /**
     * filterInsertData
     *
     * @param  mixed $properties
     * @return array
     */
    public static function filterInsertData($properties = [])
    {
        return $properties;
    }

    /**
     * filterUpdateData
     *
     * @param  mixed $properties
     * @return array
     */
    public static function filterUpdateData($properties = [])
    {
        return $properties;
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
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * getIds
     *
     * @param  mixed $documents
     * @return array
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
     * @return array
     */
    public function toArray()
    {
        return self::objectToArray($this);
    }

    /**
     * objectToArray
     *
     * @param  mixed $data
     * @return array
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
     * @return integer
     */
    public function toTime($property)
    {
        if (!\property_exists($this, $property)) {
            $reflection = new ReflectionClass(get_class($this));
            throw new Exception("Property $property does not exist in " . $reflection->getNamespaceName());
        }
        return self::toSeconds($this->{$property});
    }

    /**
     * toDate
     *
     * @param  mixed $property
     * @param  mixed $format
     * @return string
     */
    public function toDate($property, $format = 'Y-m-d H:i:s')
    {
        if (!\property_exists($this, $property)) {
            $reflection = new ReflectionClass(get_class($this));
            throw new Exception("Property $property does not exist in " . $reflection->getNamespaceName());
        }
        return self::dateFormat($this->{$property}, $format);
    }

    /**
     * combineById
     *
     * @param  mixed $documents
     * @param  mixed $callback
     * @return array
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
     * @return array
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
     * @return \MongoDB\BSON\ObjectI
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
    public static function isMongoId($id)
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
     * @return array
     */
    public static function getData()
    {
        return self::$_data;
    }

    /**
     * getException
     *
     * @return null|\Exception
     */
    public function getException()
    {
        if (self::$_exception instanceof \Exception) {
            return self::$_exception;
        }
        return null;
    }

    /**
     * getErrorCode
     *
     * @return null|integer
     */
    public function getErrorCode()
    {
        if (self::$_exception instanceof \Exception) {
            return self::$_exception->getCode();
        }
        return null;
    }

    /**
     * getErrorMessage
     *
     * @return null|string
     */
    public function getErrorMessage()
    {
        if (self::$_exception instanceof \Exception) {
            return self::$_exception->getMessage();
        }
        return null;
    }
}
