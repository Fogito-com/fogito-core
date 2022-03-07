<?php
namespace Services\Controllers;

use Lib\Lang;
use Lib\Request;
use Lib\Response;
use Models\Services;

class DeleteController
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
        $req  = Request::get('data');
        $id   = Services::filterMongoIds((array) $req['id']);
        $data = Services::find([
            [
                '_id'        => [
                    '$in' => Services::convertIds($id),
                ],
                'is_deleted' => [
                    '$ne' => true,
                ],
            ],
        ]);

        if (!$data) {
            Response::error(Lang::get('InformationNotFound', 'Information not found'));
        }

        foreach ($data as $row) {
            $row->is_deleted = true;
            $row->save();
            $row->deleteBelonges();
        }

        Response::success(Lang::get('DeletedSuccessfully', 'Deleted successfully'));
    }
}
