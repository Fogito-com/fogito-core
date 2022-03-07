<?php
namespace Models;

use Lib\Lang;

class LogsSms extends \Lib\ModelManager
{
    public $_id;
    public $operator;
    public $phone;
    public $text;
    public $response = [];
    public $limit;
    public $hash;
    
    /**
     * getSource
     *
     * @return void
     */
    public static function getSource()
    {
        return 'logs_sms';
    }
    
    /**
     * getSmsLimit
     *
     * @return void
     */
    public function getSmsLimit()
    {
        $data = self::findFirst([
            [
                'limit' => [
                    '$ne' => null
                ]
            ],
            'sort' => [
                'created_at' => -1,
            ],
        ]);
        if ($data) {
            return $data->limit;
        }
        return 1;
    }
}
