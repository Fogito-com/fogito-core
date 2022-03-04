<?php
namespace Lib;

use Fogito\Config;

class ModelManager extends \Fogito\Db\ModelManager
{    
    /**
     * getServer
     *
     * @return void
     */
    public static function getServer()
    {
        return Config::get('mongo.server');
    }
    
    /**
     * getDb
     *
     * @return void
     */
    public static function getDb()
    {
        return Config::get('mongo.dbname');
    }
    
    /**
     * filterBinds
     *
     * @param  mixed $filter
     * @return void
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
            $filter['company_id'] = COMPANY_ID;
        }
        return $filter;
    }

    public static function customFind($filter = [])
    {
        return parent::find($filter);
    }
}
