<?php
namespace Settings\Controllers;

use Lib\Auth;
use Lib\Response;
use Models\Users;

class IndexController
{
    /**
     * index
     *
     * @return void
     */
    public function index()
    {
        $response = [];

        if (Auth::isAuth()) {
            $response['account'] = Users::filterData(Auth::getData());
        }

        Response::success(null, $response);
    }
}
