<?php
namespace Models;

class UserApps extends \Lib\ModelManager
{
    const STATUS_ACTIVE   = 1;
    const STATUS_INACTIVE = 2;

    public $_id;
    public $user_id;
    public $user_token;
    public $platform;
    public $device_token;
    public $status = self::STATUS_ACTIVE;

    /**
     * getSource
     *
     * @return void
     */
    public static function getSource()
    {
        return 'user_apps';
    }
}
