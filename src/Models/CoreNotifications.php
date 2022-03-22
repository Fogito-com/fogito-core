<?php
/**
 * @author Tural Ilyasov <senior2ral@gmail.com>
 * @link https://github.com/Fogito-com/fogito-core
 * @version 1.0.2
 * @package Fogito-Core
*/
namespace Fogito\Models;

/**
 * Notifications
 *
 * Example:
 * * Notifications::setApplicationID(\Fogito\Core::APP_ID_DRIVING_SCHOOL);
 * * Notifications::setData([
 * *     'user_id'     => '5d0814b880084e69bd1d112f',
 * *     'title'       => 'What is Lorem Ipsum?',
 * *     'description' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.',
 * * ]);
 * * $res = Notifications::send();
 */
class CoreNotifications extends \Fogito\Db\RemoteModelManager
{
    protected static $application_id;
    protected static $data = [];

    /**
     * setApplicationID
     *
     * @param  mixed $application_id
     * @return void
     */
    public static function setApplicationID($application_id)
    {
        self::$application_id = $application_id;
    }

    /**
     * getApplicationID
     *
     * @return void
     */
    public static function getApplicationID()
    {
        return self::$application_id;
    }

    /**
     * setData
     *
     * @param  mixed $data
     * @return void
     */
    public static function setData($data = [])
    {
        self::$data = $data;
    }

    /**
     * getSource
     *
     * @return void
     */
    public static function getSource()
    {
        return 'notifications';
    }

    /**
     * send
     *
     * @param  mixed $data
     * @return void
     */
    public static function send()
    {
        if (!self::$application_id) {
            throw new \Exception('Application ID is required');
        }

        if (!self::$data['user_id']) {
            throw new \Exception('Parameter "user_id" is required');
        }

        if (!self::$data['title']) {
            throw new \Exception('Parameter "title" is required');
        }

        if (!self::$data['description']) {
            throw new \Exception('Parameter "description" is required');
        }

        $data = \array_merge([
            'badge' => 1,
        ], self::$data);

        $response = self::request('send', [
            'data' => $data,
        ]);
        if ($response['status'] == self::STATUS_SUCCESS) {
            return $response['data'];
        }
        return false;
    }
}
