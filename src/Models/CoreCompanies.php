<?php
namespace Fogito\Models;

use Fogito\Config;

class CoreCompanies extends \Fogito\Db\RemoteModelManager
{
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
        return "companies";
    }
}
