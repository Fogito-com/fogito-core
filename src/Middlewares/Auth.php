<?php
/**
 * @author Tural Ilyasov <senior2ral@gmail.com>
 * @link https://github.com/Fogito-com/fogito-core
 * @version 1.0.2
 * @package Fogito-Core
*/
namespace Fogito\Middlewares;

use Fogito\App;
use Fogito\Events\Event;
use Fogito\Exception;
use Fogito\Http\Request;
use Fogito\Lib\Auth as UserAuth;
use Fogito\Lib\Company;
use Fogito\Lib\Lang;
use Fogito\Models\Companies;
use Fogito\Models\Settings;
use Fogito\Models\Users;

class Auth extends \Fogito\Middleware
{
    /**
     * beforeExecuteRoute
     *
     * @param  Event $event
     * @param  App $app
     * @return void
     */
    public function beforeExecuteRoute(Event $event, App $app)
    {
        $lang = isset($_COOKIE['lang']) ? (string) trim($_COOKIE['lang']) : (string) trim(Request::get('lang'));
        if (preg_match('/[a-z]{2}/i', trim($lang))) {
            Lang::setLang($lang);
        }

        $token = isset($_COOKIE['ut']) ? (string) trim($_COOKIE['ut']) : (string) trim(Request::get('token'));
        UserAuth::setToken($token);

        $data = Settings::init();
        if (is_array($data)) {
            Settings::setData($data);
            foreach ($data as $key => $value) {
                switch ($key) {
                    case 'account_error':
                        throw new Exception($value['description'], $value['error_code']);

                    case 'account':
                        if ($value['id']) {
                            UserAuth::setData(new Users($value));
                        }
                        break;

                    case 'permissions':
                        UserAuth::setPermissions($value);
                        break;

                    case 'company':
                        if ($value['id']) {
                            Company::setData(new Companies($value));
                        }
                        break;

                    case 'translations':
                        Lang::setData($value);
                        break;
                }
            }
        }
    }
}
