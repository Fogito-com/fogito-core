<?php
namespace Categories\Controllers;

use Lib\Response;
use Models\Categories;

class DataController
{    
    /**
     * index
     *
     * @return void
     */
    public function index()
    {
        Response::success(null, [
            'status' => Categories::statusList(),
        ]);
    }
}
