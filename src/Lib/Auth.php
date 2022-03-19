<?php
/**
 * @author Tural Ilyasov <senior2ral@gmail.com>
 * @link https://github.com/Fogito-com/fogito-core
 * @version 1.0.2
 * @package Fogito-Core
*/
namespace Fogito\Lib;

use Fogito\Lib\Helpers;

class Auth
{
    protected static $_token;
    protected static $_data;
    protected static $_permissions = [];

    /**
     * isAuth
     *
     * @return bool
     */
    public static function isAuth()
    {
        return self::$_data instanceof \Fogito\Models\Users;
    }

    /**
     * setData
     *
     * @param  \Fogito\Models\Users $data
     * @return void
     */
    public static function setData($data)
    {
        if (!$data instanceof \Fogito\Models\Users) {
            throw new \Exception('Invalid parameter type: ' . get_called_class());
        }
        self::$_data = $data;
    }

    /**
     * getData
     *
     * @return \Fogito\Models\Users
     */
    public static function getData()
    {
        return self::$_data;
    }

    /**
     * get
     *
     * @param  string $key
     * @return null|mixed
     */
    public static function get($key)
    {
        if (\is_string(trim($key))) {
            return Helpers::getArrayByKey($key, (array) self::$_data, (array) self::$_data);
        }
        return null;
    }

    /**
     * getId
     *
     * @return null|string
     */
    public static function getId()
    {
        if (isset(self::$_data)) {
            return self::$_data->getId();
        }
        return null;
    }

    /**
     * Get user type
     *
     * @return null|string
     */
    public static function getType()
    {
        if (isset(self::$_data)) {
            return self::$_data->type;
        }
        return null;
    }

    /**
     * Get moderator level
     *
     * @return null|string
     */
    public static function getLevel()
    {
        if (isset(self::$_data)) {
            return self::$_data->level;
        }
        return null;
    }

    /**
     * getToken
     *
     * @return null|string
     */
    public static function getToken()
    {
        return self::$_token;
    }

    /**
     * setToken
     *
     * @param  null|string $token
     * @return void
     */
    public static function setToken($token)
    {
        self::$_token = $token;
    }

    /**
     * getPermissions
     *
     * @return array
     */
    public static function getPermissions()
    {
        return self::$_permissions;
    }

    /**
     * setPermissions
     *
     * @param  array $permissions
     * @return void
     */
    public static function setPermissions($permissions = [])
    {
        self::$_permissions = $permissions;
    }

    /**
     * isPermitted
     *
     * @param  string $key
     * @return bool
     */
    public static function isPermitted($key)
    {
        if ($key) {
            if ($key !== null) {
                $permission = Helpers::getArrayByKey($key, self::$_permissions, self::$_permissions);
                if ($permission) {
                    if (\is_array($permission) && $permission['allow']) {
                        return true;
                    } else {
                        return !!$permission;
                    }
                }
            }
        }

        return false;
    }

    /**
     * generatePassword
     *
     * @param  string $password
     * @return string
     */
    public static function passwordHash($password)
    {
        return \password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * verifyPassword
     *
     * @param  string $password
     * @param  string $hash
     * @return bool
     */
    public static function verifyPassword($password, $hash)
    {
        return \password_verify($password, $hash);
    }
}
