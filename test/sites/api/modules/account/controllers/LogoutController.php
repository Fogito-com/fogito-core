<?php
namespace Account\Controllers;

use Lib\Auth;
use Lib\Lang;
use Lib\Response;
use Models\Tokens;

class LogoutController
{
    public function index()
    {
        if (!Auth::isAuth()) {
            Response::error(Lang::get('AuthenticationFailed'));
        }

        \setcookie('token', null, [
            'expires'  => time(),
            'path'     => '/',
            'domain'   => 'api.imtahan.im',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'None',
        ]);

        $data = Tokens::findFirst([
            [
                'token'  => Auth::getToken(),
                'status' => Tokens::STATUS_ACTIVE,
            ],
        ]);

        if ($data) {
            $data->status = Tokens::STATUS_INACTIVE;
            $data->save();
        }

        Response::success(Lang::get('SessionCancelledSucessfully', 'Session cancelled sucessfully'));
    }
}
