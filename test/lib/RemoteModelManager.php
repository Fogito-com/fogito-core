<?php
namespace Lib;

use Lib\App;
use Lib\Auth;

class RemoteModelManager extends \Fogito\Db\RemoteModelManager
{
    /**
     * getUrl
     *
     * @return void
     */
    public static function getUrl()
    {
        return App::$di->config->s2s->api_url;
    }

    /**
     * getSource
     *
     * @return void
     */
    public static function getSource()
    {
        return null;
    }

    /**
     * filterRequestParams
     *
     * @param  mixed $parameters
     * @return void
     */
    public static function filterRequestParams($parameters = [])
    {
        $credentials = App::$di->config->s2s->credentials;
        if ($credentials && \is_array($credentials)) {
            $parameters = \array_merge($parameters, $credentials);
        }

        if (!\array_key_exists('token', $parameters)) {
            $parameters = \array_merge($parameters, [
                'token'      => Auth::getToken(),
                'token_user' => Auth::getId(),
            ]);
        }

        return $parameters;
    }

    /**
     * filterResponseData
     *
     * @param  mixed $response
     * @return void
     */
    public static function filterResponseData($response = [])
    {
        return \json_decode($response, true);
    }
}
