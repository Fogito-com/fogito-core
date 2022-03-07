<?php
namespace Auth\Controllers;

use Lib\Auth;
use Lib\Lang;
use Lib\Request;
use Lib\Response;
use Models\Tokens;
use Models\Users;

class SetupController
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
        $checkAdministrator = Users::findFirst([
            [
                'level'      => Users::LEVEL_ADMINISTRATION,
                'is_deleted' => [
                    '$ne' => true,
                ],
            ],
        ]);
        if ($checkAdministrator) {
            Response::error(Lang::get('AdministratorExist', 'Administrator exist'));
        }

        $req      = Request::get('data');
        $username = (string) trim($req['username']);
        $password = (string) trim($req['password']);
        $operator = (int) trim($req['operator']);
        $prefix   = (int) trim($req['prefix']);
        $number   = (int) trim($req['number']);
        $phone    = (string) $prefix . $number;
        $email    = (string) trim($req['email']);
        $fullname = (string) trim($req['fullname']);
        $birth    = (string) trim($req['birth']);
        $gender   = (string) trim($req['gender']);

        if (!$username) {
            Response::error(Lang::get('UsernameIsRequired', 'Username is required'));
        } elseif (!preg_match('/^[a-z0-9]{4,24}$/', strtolower($username))) {
            Response::error(Lang::get('UsernameIsWrong', 'Username is wrong'));
        }

        $checkUsername = Users::findFirst([
            [
                'username'   => $username,
                'is_deleted' => [
                    '$ne' => true,
                ],
            ],
        ]);
        if ($checkUsername) {
            Response::error(Lang::get('UsernameExists'));
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

        if (!is_string($password) || strlen($password) > 50 || strlen($password) < 6) {
            Response::error(Lang::get('PasswordError'));
        }

        $i           = new Users();
        $i->type     = Users::TYPE_MODERATOR;
        $i->level    = Users::LEVEL_ADMINISTRATION;
        $i->username = $username;
        $i->password = Auth::passwordHash($password);
        $i->operator = $operator;
        $i->phone    = $phone;
        $i->email    = $email;
        $i->fullname = $fullname;
        $i->birth    = $birth;
        $i->gender   = $gender;
        $i->save();

        // create a new token
        $t     = new Tokens();
        $token = $t->createByUserId($i->getId());

        Auth::setToken($token);
        Auth::setData($i);

        $expires = time() + 86400;
        $exp     = \explode('.', Request::getServer('HTTP_HOST'));
        $domain  = '.' . $exp[1] . $exp[2];

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
