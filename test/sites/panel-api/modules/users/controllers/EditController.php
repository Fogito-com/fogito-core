<?php
namespace Users\Controllers;

use Lib\Auth;
use Lib\Lang;
use Lib\Request;
use Lib\Response;
use Models\Files;
use Models\Users;

class EditController
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
        $req       = Request::get('data');
        $id        = (string) trim($req['id']);
        $type      = (string) trim($req['type']);
        $username  = (string) trim($req['username']);
        $password  = (string) trim($req['password']);
        $operator  = (int) trim($req['operator']);
        $prefix    = (int) trim($req['prefix']);
        $number    = (int) trim($req['number']);
        $phone     = (string) $prefix . $number;
        $email     = \strtolower((string) trim($req['email']));
        $fullname  = (string) trim($req['fullname']);
        $birth     = (string) trim($req['birth']);
        $gender    = (string) trim($req['gender']);
        $level     = (string) trim($req['level']);
        $avatar_id = (string) trim($req['avatar_id']);
        $status    = (int) trim($req['status']);

        $data = Users::findFirst([
            [
                '_id'        => Users::objectId($id),
                'is_deleted' => [
                    '$ne' => true,
                ],
            ],
        ]);
        if (!$data) {
            Response::error(Lang::get('UserNotFound', 'User not found'));
        }

        if (!$username) {
            Response::error(Lang::get('UsernameIsRequired', 'Username is required'));
        } elseif (!preg_match('/^[a-z0-9]{4,24}$/', strtolower($username))) {
            Response::error(Lang::get('UsernameIsWrong', 'Username is wrong'));
        }

        if (!is_string($fullname)) {
            Response::error(Lang::get('FullNameIsRequired'));
        } elseif (!is_string($fullname) || strlen($fullname) < 2 || strlen($fullname) > 50) {
            Response::error(Lang::get('FullNameError'));
        }

        if (!$operator) {
            Response::error(Lang::get('OperatorWasNotSelected'));
        } elseif (!Users::getDataByValue($operator, Users::operatorList())) {
            Response::error(Lang::get('OperatorNotFound'));
        }

        if (!$prefix) {
            Response::error(Lang::get('PrefixWasNotSelected'));
        } elseif (!Users::getDataByValue($prefix, Users::prefixList())) {
            Response::error(Lang::get('PrefixIsWrong'));
        } elseif (!is_numeric($number) || strlen($number) > 15 || strlen($number) < 5) {
            Response::error(Lang::get('PhoneIsWrong'));
        }

        if (!is_string($gender)) {
            Response::error(Lang::get('GenderIsRequired'));
        } elseif (!Users::getDataByValue($gender, Users::genderList())) {
            Response::error(Lang::get('GenderIsNotValid'));
        }

        if ($email && !\filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error(Lang::get('WrongEmailAddress'));
        }

        if (date('Y-m-d', strtotime($birth)) != $birth) {
            Response::error(Lang::get('BirthDateIsWrong'));
        }

        if ($password && (!is_string($password) || strlen($password) > 50 || strlen($password) < 6)) {
            Response::error(Lang::get('PasswordError'));
        }

        if ($type == Users::TYPE_MODERATOR) {
            if (!$level) {
                Response::error(Lang::get('LevelWasNotSelected'));
            } elseif (!Users::getDataByValue($level, Users::levelList())) {
                Response::error(Lang::get('LevelNotFound'));
            }
        }

        $check = Users::findFirst([
            Users::filter([
                '_id'        => [
                    '$ne' => $data->_id,
                ],
                '$or'        => [
                    ['username' => $username],
                    ['phone' => $phone],
                ],
                'is_deleted' => [
                    '$ne' => true,
                ],
            ], function ($x) use ($email) {
                if ($email) {
                    $x['$or'][] = ['email' => $email];
                }
                return $x;
            }),
        ]);
        if ($check) {
            if($check->username == $username) {
                Response::error(Lang::get('UsernameAlreadyExists', 'Username already exist'));
            } elseif($check->phone == $phone) {
                Response::error(Lang::get('PhoneAlreadyExists', 'Phone already exist'));
            } else {
                Response::error(Lang::get('EmailAlreadyExists', 'Email already exist'));
            }
        }

        if ($avatar_id && $avatar_id != $data->avatar_id) {
            Files::copyTempFile($avatar_id, [
                'parent_type' => Files::PARENT_TYPE_USERS,
                'parent_id' => $data->getId()
            ]);
        }

        $data->type = $type;
        if ($type == Users::TYPE_MODERATOR) {
            $data->level = $level;
        }
        $data->username = $username;
        if ($password) {
            $data->password = Auth::passwordHash($password);
        }
        $data->avatar_id = $avatar_id ? $avatar_id : null;
        $data->operator  = $operator;
        $data->phone     = $phone;
        $data->email     = $email;
        $data->fullname  = $fullname;
        $data->birth     = $birth;
        $data->gender    = $gender;
        $data->status    = $status;
        $data->save();

        Response::success(Lang::get('UpdatedSuccessfully', 'Updated successfully'));
    }
}
