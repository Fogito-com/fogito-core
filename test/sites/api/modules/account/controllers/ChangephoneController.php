<?php
namespace Account\Controllers;

use Lib\Auth;
use Lib\Helpers;
use Lib\Lang;
use Lib\Request;
use Lib\Response;
use Lib\Sms;
use Models\LogsVerification;
use Models\Users;

class ChangephoneController
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
     * step1
     *
     * @return void
     */
    public function step1()
    {
        $req      = Request::get('data');
        $operator = (int) trim($req['operator']);
        $prefix   = (int) trim($req['prefix']);
        $number   = (int) trim($req['number']);
        $phone    = (string) $prefix . $number;

        if (!$operator) {
            Response::error(Lang::get('OperatorWasNotSelected', 'Operator was not selected'));
        }
        if (!Users::getDataByValue($operator, Users::operatorList())) {
            Response::error(Lang::get('OperatorNotFound', 'Operator not found'));
        }
        if (!$prefix) {
            Response::error(Lang::get('PrefixWasNotSelecred', 'Prefix was not selelcted'));
        }
        if (!Users::getDataByValue($prefix, Users::prefixList())) {
            Response::error(Lang::get('PrefixIsWrong', 'Prefix is wrong'));
        }
        if (!is_numeric($number) || strlen($number) > 15 || strlen($number) < 5) {
            Response::error(Lang::get('PhoneIsWrong', 'Phone number is wrong'));
        }

        $exist = Users::findFirst([
            [
                '_id'        => [
                    '$ne' => $data->_id,
                ],
                'phone'      => $phone,
                'is_deleted' => [
                    '$ne' => 1,
                ],
            ],
        ]);
        if ($user) {
            Response::error(Lang::get('PhoneAlreadyExist', 'Phone already exist'));
        }

        $code    = Helpers::randomNumber(LogsVerification::CODE_LENGTH);
        $smsText = strtr(Lang::get('SmsTemplateVerificationCode', 'Verification code: %code%'), [
            '%code%' => $code,
        ]);
        $response = Sms::solo($phone, $smsText, $operator);
        if ($response['status'] == 'error') {
            Response::error(Lang::get('TechnicalError'));
        }

        $log           = new LogsVerification();
        $log->operator = (int) $operator;
        $log->phone    = (string) $phone;
        $log->code     = (string) $code;
        $log->hash     = (string) $response['hash'];
        $log->save();

        Response::success(Lang::get('VerificationCodeSentSucessfully'), [
            'hash' => $response['hash'],
            'code' => ENV == 'development' ? $code : null,
        ]);
    }

    /**
     * step2
     *
     * @return void
     */
    public function step2()
    {
        $req  = Request::get('data');
        $hash = (string) trim($req['hash']);
        $code = (string) trim($req['code']);

        if (!is_string($hash) || strlen($hash) != 32) {
            Response::error(Lang::get('HashNotFound', 'Hash not found'));
        }

        if (!is_string($code) || strlen($code) < LogsVerification::CODE_LENGTH) {
            Response::error(Lang::get('VerificationCodeIsWrong', 'Verification code is wrong'));
        }

        $verification = LogsVerification::findFirst([
            [
                'hash'       => (string) $hash,
                'status'     => LogsVerification::STATUS_ACTIVE,
                'expired_at' => [
                    '$gt' => LogsVerification::getDate(),
                ],
            ],
        ]);
        if (!$verification) {
            Response::error(Lang::get('VerificationCodeNotFound'));
        }
        if ($code != $verification->code) {
            Response::error(Lang::get('CodeIsWrong'));
        }

        $data                    = Auth::getData();
        $data->phone             = $verification->phone;
        $data->status            = Users::STATUS_ACTIVE;
        $data->phone_is_verified = true;
        $data->save();

        Response::success(Lang::get('UpdatedSuccessfully'));
    }
}
