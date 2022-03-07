<?php
namespace Auth\Controllers;

use Lib\Auth;
use Lib\Lang;
use Lib\Request;
use Lib\Response;
use Models\Tokens;
use Models\Users;

class LoginController
{
    /**
     * __construct
     *
     * @param  mixed $app
     * @return void
     */
    public function __construct($app)
    {
        if (!Request::isPost()) {
            Response::error(Lang::get('Invalid request method.'));
        }
    }
    
    /**
     * index
     *
     * @return void
     */
    public function index()
    {
        $req      = Request::get('data');
        $username = (string) trim($req['username']);
        $password = (string) trim($req['password']);
        $remember = (bool) trim($req['remember']);

        if (!$username) {
            Response::error(Lang::get('UsernameIsRequired', 'Username is required'));
        }
        if (!preg_match('/^[a-z0-9]{4,24}$/', strtolower($username))) {
            Response::error(Lang::get('UsernameIsWrong', 'Username is wrong'));
        }
        if (!is_string($password) || strlen($password) > 50 || strlen($password) < 6) {
            Response::error(Lang::get('PasswordIsWrong', 'Password is wrong'));
        }

        $data = Users::findFirst([
            [
                'username'   => (string) $username,
                'type'       => [
                    '$in' => [
                        Users::TYPE_MODERATOR,
                    ],
                ],
                'is_deleted' => [
                    '$ne' => true,
                ],
            ],
        ]);

        if (!$data) {
            Response::error(Lang::get('InformationNotFound', 'Information not found'));
        }
        if (!Auth::verifyPassword($password, $data->password)) {
            Response::error(Lang::get('PasswordIsWrong', 'Password is wrong'));
        }

        $t     = new Tokens();
        $token = $t->createByUserId($data->getId());

        Auth::setToken($token);

        $expires = $remember ? (time() + 90 * 86400) : (time() + 86400);
        $exp     = \explode('.', Request::getServer('HTTP_HOST'));
        $domain  = '.' . $exp[1] . '.' . $exp[2];

        \setcookie('admin_token', $token, [
            'expires'  => $expires,
            'path'     => '/',
            'domain'   => $domain,
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'None',
        ]);

        Response::success(null, [
            'token' => $token,
        ]);
    }
}
