<?php
namespace Services\Controllers;

use Lib\Response;
use Models\Services;

class DataController
{
    public function index()
    {
        $response = [
            'status' => Services::statusList(),
        ];

        Response::success(null, $response);
    }
}
