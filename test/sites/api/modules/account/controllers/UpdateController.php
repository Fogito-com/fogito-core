<?php
namespace Account\Controllers;

use Lib\Auth;
use Lib\Lang;
use Lib\Request;
use Lib\Response;
use Models\Files;
use Models\UserApps;
use Models\Users;

class UpdateController
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
        $req   = Request::get('data');
        $field = (string) trim($req['field']);
        $value = $req['value'];

        $data = Auth::getData();

        switch ($field) {
            case 'username':
                $username = (string) trim($value);
                if (!$username) {
                    Response::error(Lang::get('UsernameIsRequired', 'Username is required'));
                }
                if (!preg_match('/^[a-z0-9]{4,24}$/', strtolower($username))) {
                    Response::error(Lang::get('UsernameIsWrong', 'Username is wrong'));
                }

                $exist = Users::findFirst([
                    [
                        '_id'        => [
                            '$ne' => $data->_id,
                        ],
                        'username'   => $username,
                        'is_deleted' => [
                            '$ne' => true,
                        ],
                    ],
                ]);
                if ($exist) {
                    Response::error(Lang::get('UsernameAlreadyExists', 'Username already exist'));
                }

                $data->username = $username;
                $data->save();
                break;

            case 'password':
                $oldpassword = (string) trim($value['oldpassword']);
                $password    = (string) trim($value['password']);
                $repassword  = (string) trim($value['repassword']);

                if (!$oldpassword) {
                    Response::error(Lang::get('OldPasswordIsRequired', 'Old password is required'));
                }
                if (!Auth::verifyPassword($oldpassword, $data->password)) {
                    Response::error(Lang::get('OldPasswordIsWrong', 'Old password is wrong'));
                }
                if (!$password) {
                    Response::error(Lang::get('NewPasswordIsRequired', 'New password is required'));
                }
                if (!is_string($password) || strlen($password) > 50 || strlen($password) < 6) {
                    Response::error(Lang::get('NewPasswordError', 'Password is wrong'));
                }
                if ($repassword != $password) {
                    Response::error(Lang::get('PasswordsDoNotMatch', 'Passwords do not match'));
                }

                $data->password = Auth::passwordHash($password);
                $data->save();
                break;

            case 'avatar':
                $avatar_id = (string) trim($value);
                if ($avatar_id && $avatar_id != $data->avatar_id) {
                    Files::copyTempFile($avatar_id, [
                        'parent_type' => Files::PARENT_TYPE_USERS,
                        'parent_id'   => $data->getId(),
                    ]);
                }
                $data->avatar_id = $avatar_id ? $avatar_id : null;
                $data->save();
                break;

            case 'set_device':
                $platform     = (string) trim($value['platform']);
                $device_token = (string) trim($value['device_token']);

                $app = UserApps::findFirst([
                    [
                        'user_id'  => Auth::getId(),
                        'platform' => $platform,
                    ],
                ]);
                if ($app) {
                    if ($device_token != $app->device_token) {
                        $app->device_token = $device_token;
                        $app->save();
                    }
                } else {
                    $ua               = new UserApps();
                    $ua->user_id      = Auth::getId();
                    $ua->user_token   = Auth::getToken();
                    $ua->platform     = $platform;
                    $ua->device_token = $device_token;
                    $ua->save();
                }
                break;

            default:
                Response::error(Lang::get('FieldNotFound', 'Field not found'));
                break;
        }

        Response::success(Lang::get('UpdatedSuccessfully', 'Updated successfully'));
    }
}
