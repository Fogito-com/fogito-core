<?php
/**
 * @author Tural Ilyasov <senior2ral@gmail.com>
 * @link https://github.com/Fogito-com/fogito-core
 * @version 1.0.2
 * @package Fogito-Core
*/
namespace Fogito\Models;

use Fogito\Lib\Auth;

class CoreUsers extends \Fogito\Db\RemoteModelManager
{
    const STATUS_MODERATE = 1;
    const STATUS_ACTIVE   = 2;
    const STATUS_INACTIVE = 3;

    const TYPE_USER      = 'user';
    const TYPE_MODERATOR = 'moderator';

    const LEVEL_OPERATOR       = 'operator';
    const LEVEL_SUPERVISOR     = 'supervisor';
    const LEVEL_ADMINISTRATION = 'administration';

    const GENDER_MALE   = 'male';
    const GENDER_FEMALE = 'female';
    
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
