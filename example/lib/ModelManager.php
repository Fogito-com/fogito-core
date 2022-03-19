<?php
namespace Lib;

use Fogito\Lib\Auth;
use Fogito\Lib\Company;
use Fogito\App;

class ModelManager extends \Fogito\Db\ModelManager
{
    /**
     * getServer
     *
     * @return array
     */
    public static function getServer()
    {
        return App::$di->config->mongo->server->toArray();
    }

    /**
     * getDb
     *
     * @return string
     */
    public static function getDb()
    {
        return App::$di->config->mongo->dbname;
    }

    /**
     * filterBinds
     *
     * @param  array $filter
     * @return array
     */
    public static function filterBinds($filter = [])
    {
        $source = parent::getSource();
        if (\in_array($source, ['companies'])) {
            return $filter;
        }

        if (!\array_key_exists('company_id', $filter)) {
            if (!defined('COMPANY_ID')) {
                throw new \Exception('COMPANY_ID is not defined');
            }
            $filter['company_id'] = Company::getId();
        }
        return $filter;
    }

    /**
     * beforeSave
     *
     * @param  boolean $forceInsert
     * @return void
     */
    public function beforeSave($forceInsert = false)
    {
        if (!$this->created_at) {
            $this->created_at = self::getDate();
        }
        if (!$this->creator_id && Auth::isAuth()) {
            $this->creator_id = Auth::getId();
        }
    }

    /**
     * beforeUpdate
     *
     * @return void
     */
    public function beforeUpdate()
    {
        if (!$this->updated_at) {
            $this->updated_at = self::getDate();
        }
        if ($this->is_deleted) {
            if (!$this->deleted_at) {
                $this->deleted_at = self::getDate();
            }
            if (!$this->deleted_id && Auth::isAuth()) {
                $this->deleted_id = Auth::getId();
            }
        }
    }

    
    /**
     * getDataByValue
     *
     * @param  mixed $value
     * @param  array $items
     * @param  string $key
     * @return mixed
     */
    public static function getDataByValue($value, $items = [], $key = 'value')
    {
        $data = self::combine($key, $items, true);
        if ($data[$value]) {
            return $data[$value];
        }
        return false;
    }

    /**
     * filterLimitSkip
     *
     * @param  int $limit
     * @param  int $skip
     * @param  int $max
     * @return array
     */
    public static function filterLimitSkip($limit = 0, $skip = 0, $max = 100)
    {
        return [
            $limit == "-1" ? 0 : ((!(int) $limit || (int) $limit > (int) $max) ? (int) $max : (int) $limit),
            $limit == "-1" ? 0 : (int) $skip,
        ];
    }

    /**
     * multiSort
     *
     * @param  array $sort
     * @param  array $fields
     * @return array
     */
    public static function multiSort($sort = [], $fields = [])
    {
        if ($sort) {
            if (!is_array($sort[0])) {
                $sort = [$sort];
            }

            $sorting = [];
            foreach ($sort as $value) {
                $sort_field = trim($value['field']);
                $sort_order = trim($value['order']);

                if (in_array($sort_field, $fields)) {
                    $sorting = array_merge($sorting, [$sort_field => $sort_order == 'desc' ? -1 : 1]);
                }
            }
            return $sorting;
        }
    }
}
