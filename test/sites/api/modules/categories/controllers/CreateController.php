<?php
namespace Categories\Controllers;

use Fogito\Request;
use Fogito\Response;

class CreateController
{
    public function index()
    {
        $req = Request::get();

        Response::setData($req);
        Response::setMessage('Created successfully');
        Response::send();
    }
}
