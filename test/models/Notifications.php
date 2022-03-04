<?php
namespace Models;

use Lib\RemoteModelManager;

class Notifications extends \Lib\ModelManager
{
    protected static $application_id = 605;

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
     * @param  mixed $parameters
     * @return void
     */
    public static function send($parameters = [])
    {
        $parameters = \array_merge([
            'application_id' => self::$application_id,
            'badge'          => 1,
        ], $parameters);

        RemoteModelManager::setSource('notifications');
        $parameters = RemoteModelManager::filterRequestParams([
            'data' => $parameters,
        ]);

        $response = RemoteModelManager::request('send', $parameters);
        if ($response['status'] == RemoteModelManager::STATUS_SUCCESS) {
            return $response['data'];
        }
        return false;
    }
}
