<?php
namespace Account\Controllers;

use Lib\Auth;
use Lib\Response;
use Models\Users;

class InfoController
{
    /**
     * index
     *
     * @return void
     */
    public function index()
    {
        Response::success(null, Users::filterData(Auth::getData()));
    }
}
