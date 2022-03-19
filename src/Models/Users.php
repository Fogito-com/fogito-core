<?php
/**
 * @author Tural Ilyasov <senior2ral@gmail.com>
 * @link https://github.com/Fogito-com/fogito-core
 * @version 1.0.2
 * @package Fogito-Core
*/
namespace Fogito\Models;

use Fogito\Lib\Auth;

class Users extends \Fogito\Db\RemoteModelManager
{
    /**
     * __construct
     *
     * @param  mixed $properties
     * @return void
     */
    public function __construct($properties = [])
    {
        foreach ($properties as $key => $value) {
            $this->{$key} = $value;
        }
    }

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
     * filterInsertData
     *
     * @param  mixed $properties
     * @return void
     */
    public static function filterInsertData($properties = [])
    {
        $allows = [
            'id',
            'type',
            'username',
            'phone',
            'firstname',
            'lastname',
            'fullname',
            'email',
            'password',
            'gender',
            'tax_id',
            'address',
        ];

        foreach ($properties as $key => $value) {
            if (!in_array($key, $allows)) {
                unset($properties[$key]);
            }
        }

        return $properties;
    }

    /**
     * filterUpdateData
     *
     * @param  mixed $properties
     * @return void
     */
    public static function filterUpdateData($properties = [])
    {
        $allows = [
            'id',
            'type',
            'username',
            'phone',
            'firstname',
            'lastname',
            'fullname',
            'email',
            'password',
            'gender',
            'tax_id',
            'address',
            'salary',
            'monthly',
            'weekly',
            'currency',
            'is_deleted',
            'is_blocked',
        ];

        foreach ($properties as $key => $value) {
            if (!in_array($key, $allows)) {
                unset($properties[$key]);
            }
        }
        return $properties;
    }

    public function delete()
    {
        $this->is_deleted = 1;
        if (Auth::isAuth()) {
            $this->deleter_id = Auth::getId();
        }
        $this->deleted_at = self::getDate();
        return !!$this->save();
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
        if ($response['status'] == self::STATUS_SUCCESS) {
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
        if ($response['status'] == self::STATUS_SUCCESS) {
            return $response['data'];
        }
        return false;
    }
}
