<?php
namespace Account\Controllers;

use Lib\Auth;
use Lib\Lang;
use Lib\Request;
use Lib\Response;
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
        $req      = Request::get('data');
        $email    = \strtolower((string) trim($req['email']));
        $fullname = (string) trim($req['fullname']);
        $birth    = (string) trim($req['birth']);
        $gender   = (string) trim($req['gender']);

        if (!is_string($fullname)) {
            Response::error(Lang::get('FullNameIsRequired'));
        }
        if (!is_string($fullname) || strlen($fullname) < 2 || strlen($fullname) > 50) {
            Response::error(Lang::get('FullNameError'));
        }
        if (!is_string($gender)) {
            Response::error(Lang::get('GenderIsRequired'));
        }
        if (!Users::getDataByValue($gender, Users::genderList())) {
            Response::error(Lang::get('GenderIsNotValid'));
        }
        if ($email && !\filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error(Lang::get('WrongEmailAddress'));
        }
        if (date('Y-m-d', strtotime($birth)) != $birth) {
            Response::error(Lang::get('BirthDateIsWrong'));
        }

        if ($email) {
            $exist = Users::findFirst([
                [
                    '_id'        => [
                        '$ne' => Users::objectId(Auth::getId()),
                    ],
                    'email'      => $email,
                    'is_deleted' => [
                        '$ne' => true,
                    ],
                ],
            ]);
            if ($exist) {
                Response::error(Lang::get('EmailAlreadyExists', 'Email already exist'));
            }
        }

        $data = Auth::getData();
        if ($data->email != $email) {
            $data->email_is_verified = false;
        }
        $data->email    = $email ? $email : null;
        $data->fullname = $fullname;
        $data->birth    = $birth;
        $data->gender   = $gender;
        $data->save();

        Response::success(Lang::get('UpdatedSuccessfully', 'Updated successfully'));
    }
}
