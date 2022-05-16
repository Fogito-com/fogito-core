<?php
namespace Fogito\Models;

use Fogito\Config;

class CoreEmails extends \Fogito\Db\RemoteModelManager
{
    public static function getServer()
    {
        return Config::$_serverUrls["s2s"];
    }

    public static function getSource()
    {
        return "emails";
    }

}
