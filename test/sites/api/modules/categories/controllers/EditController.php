<?php
namespace Categories\Controllers;

use Fogito\Request;
use Fogito\Response;
use Fogito\Application;

class EditController
{
    public function index($params)
    {
        $req = Request::getAll();

        echo '<pre>';
        print_r(Application::$di);
        exit;

        Response::dump($req);
        Response::setData($params);
        Response::setMessage('Updated successfully');
        Response::send();
    }
}
