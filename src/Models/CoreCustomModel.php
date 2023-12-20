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
}
