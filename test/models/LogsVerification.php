<?php
namespace Models;

use Lib\Helpers;

class LogsVerification extends \Lib\ModelManager
{
    const STATUS_ACTIVE   = 1;
    const STATUS_INACTIVE = 2;

    const CODE_LENGTH = 6;

    public $_id;
    public $operator;
    public $phone;
    public $code;
    public $hash;
    public $status = self::STATUS_ACTIVE;

    public static function getSource()
    {
        return 'logs_verification';
    }

    public function beforeSave($forceInsert = false)
    {
        $this->expired_at = self::getDate(time() + 3600 * 12);
        $this->created_at = self::getDate();
    }

    public function getCode($digit)
    {
        $this->code = Helpers::randomNumber(self::CODE_LENGTH);
    }
}
