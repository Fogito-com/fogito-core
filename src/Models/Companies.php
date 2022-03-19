<?php
/**
 * @author Tural Ilyasov <senior2ral@gmail.com>
 * @link https://github.com/Fogito-com/fogito-core
 * @version 1.0.2
 * @package Fogito-Core
*/
namespace Fogito\Models;

use Fogito\Lib\Auth;

class Companies extends \Fogito\Db\RemoteModelManager
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
        return 'companies';
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
            'title',
            'address',
            'currencies',
            'languages',
            'timezones',
            'phones',
            'emails',
            'vat',
            'invoice_days',
            'next_invoice_number',
            'tax_id',
            'reg_id',
            'account_id',
            'weekly',
            'status',
            'permissions',
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
