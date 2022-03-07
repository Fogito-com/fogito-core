<?php
namespace Models;

use Lib\Helpers;
use Lib\Request;

class Tokens extends \Lib\ModelManager
{
    const STATUS_ACTIVE   = 1;
    const STATUS_INACTIVE = 2;

    public $_id;
    public $user_id;
    public $token;
    public $ip;
    public $device;
    public $status = self::STATUS_ACTIVE;

    /**
     * getSource
     *
     * @return void
     */
    public static function getSource()
    {
        return 'tokens';
    }

    /**
     * setUserIp
     *
     * @return void
     */
    public function setUserIp()
    {
        $this->ip = Request::getServer('REMOTE_ADDR');
    }

    /**
     * setUserAgent
     *
     * @return void
     */
    public function setUserAgent()
    {
        $this->device = Request::getServer('HTTP_USER_AGENT');
    }

    /**
     * createByUserId
     *
     * @param  mixed $user_id
     * @return void
     */
    public function createByUserId($user_id)
    {
        $t          = new self;
        $t->user_id = $user_id;
        $t->token   = Helpers::genUuid();
        $t->setUserIp();
        $t->setUserAgent();
        if ($t->save()) {
            return $t->token;
        }
        return false;
    }
}
