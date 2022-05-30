<?php
namespace Fogito\Models;

use Fogito\Config;

class CoreEmails extends \Fogito\Db\RemoteModelManager
{
    public static function getServer()
    {
        return Config::getUrl("s2s");
    }

    public static function getSource()
    {
        return "emails";
    }

}
