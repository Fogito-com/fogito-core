<?php
namespace Lib;

use Lib\Lang;
use Models\Tokens;
use Models\Users;

class Auth
{
    const ERROR_CODE = 1002;

    public static $token = null;
    public static $data  = [];
    public static $exception;

    /**
     * init
     *
     * @param  mixed $token
     * @param  mixed $grantTypes
     * @return void
     */
    public static function init($token, $grantTypes = [])
    {
        try {
            if (!$token) {
                throw new \Exception(Lang::get('InvalidToken', 'Invalid token'), self::ERROR_CODE);
            }

            $data = Tokens::findFirst([
                [
                    'token'  => $token,
                    'status' => Tokens::STATUS_ACTIVE,
                ],
            ]);
            if (!$data) {
                throw new \Exception(Lang::get('SessionExpired', 'Session expired'), self::ERROR_CODE);
            }

            self::setToken($token);
            $user = Users::findFirst([
                Users::filter([
                    '_id'        => Users::objectId($data->user_id),
                    'is_deleted' => [
                        '$ne' => true,
                    ],
                ], function ($x) use ($grantTypes) {
                    if ($grantTypes) {
                        $x['type'] = [
                            '$in' => $grantTypes,
                        ];
                    }
                    return $x;
                }),
            ]);
            if (!$user) {
                throw new \Exception(Lang::get('AuthenticationFailed', 'Authentication failed'), self::ERROR_CODE);
            }

            self::setData($user);
        } catch (\Exception $e) {
            self::$exception = $e;
        }
    }

    /**
     * getException
     *
     * @return void
     */
    public static function getException()
    {
        return self::$exception;
    }

    /**
     * isAuth
     *
     * @return void
     */
    public static function isAuth()
    {
        return self::$data instanceof \Lib\ModelManager;
    }

    /**
     * getId
     *
     * @return void
     */
    public static function getId()
    {
        if (self::isAuth()) {
            return self::$data->getId();
        }
        return null;
    }

    /**
     * Get user type
     *
     * @return ingeter
     */
    public static function getType()
    {
        return self::$data->type;
    }

    /**
     * Get moderator level
     *
     * @return string
     */
    public static function getLevel()
    {
        return self::getType() == Users::TYPE_MODERATOR ? self::$data->level : null;
    }

    /**
     * setData
     *
     * @param  mixed $data
     * @return void
     */
    public static function setData($data)
    {
        self::$data = $data;
    }

    /**
     * getData
     *
     * @return void
     */
    public static function getData()
    {
        return self::$data;
    }

    /**
     * getToken
     *
     * @return void
     */
    public static function getToken()
    {
        return self::$token;
    }

    /**
     * setToken
     *
     * @param  mixed $token
     * @return void
     */
    public static function setToken($token)
    {
        self::$token = $token;
    }

    /**
     * generatePassword
     *
     * @param  mixed $password
     * @return void
     */
    public static function passwordHash($password)
    {
        return \password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * verifyPassword
     *
     * @param  mixed $password
     * @param  mixed $hash
     * @return void
     */
    public static function verifyPassword($password, $hash)
    {
        return \password_verify($password, $hash);
    }
}
