<?php
namespace Models;

class SettingsRemote extends \Lib\RemoteModelManager
{    
    /**
     * getSource
     *
     * @return void
     */
    public static function getSource()
    {
        return 'settings';
    }
    
    /**
     * getData
     *
     * @return void
     */
    public static function getData()
    {
        $response = self::request(null, []);
        if($response['status'] == self::STATUS_SUCCESS) {
            return $response['data'];
        }
        return false;
    }
}
