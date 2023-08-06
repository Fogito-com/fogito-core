<?php
namespace Fogito\Models;

use Fogito\Config;

class CoreSMS extends \Fogito\Db\RemoteModelManager
{
    public static function getServer()
    {
        return Config::getUrl("s2s");
    }

    public static function getSource()
    {
        return "sms";
    }

}
