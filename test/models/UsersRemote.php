<?php
namespace Models;

class UsersRemote extends \Lib\RemoteModelManager
{    
    /**
     * getSource
     *
     * @return void
     */
    public static function getSource()
    {
        return 'users';
    }
    
    /**
     * updateAvatar
     *
     * @param  mixed $parameters
     * @return void
     */
    public static function updateAvatar($parameters = [])
    {
        $response = self::request('avatarupdate', $parameters);
        if($response['status'] == self::STATUS_SUCCESS) {
            return $response['data'];
        }
        return false;
    }
    
    /**
     * deleteAvatar
     *
     * @param  mixed $parameters
     * @return void
     */
    public static function deleteAvatar($parameters = [])
    {
        $response = self::request('avatardelete', $parameters);
        if($response['status'] == self::STATUS_SUCCESS) {
            return $response['data'];
        }
        return false;
    }
}
