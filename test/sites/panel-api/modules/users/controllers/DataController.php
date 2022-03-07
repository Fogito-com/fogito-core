<?php
namespace Users\Controllers;

use Lib\Response;
use Models\Users;

class DataController
{
    public function index()
    {
        Response::success(null, [
            'type'     => Users::typeList(),
            'level'    => Users::levelList(),
            'gender'   => Users::genderList(),
            'status'   => Users::statusList(),
            'operator' => Users::operatorList(),
            'prefix'   => Users::prefixList(),
            'year'     => Users::yearList(),
            'month'    => Users::monthList(),
            'day'      => Users::dayList(),
        ]);
    }
}
