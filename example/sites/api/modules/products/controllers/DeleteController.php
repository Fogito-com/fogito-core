<?php
namespace Products\Controllers;

use Fogito\Lib\Lang;
use Fogito\Http\Request;
use Fogito\Http\Response;
use Models\Products;

class DeleteController
{
    /**
     * __construct
     *
     * @param  \Fogito\App $app
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
        $id   = Products::filterMongoIds((array) $req['id']);
        $data = Products::find([
            [
                '_id'        => [
                    '$in' => Products::convertIds($id),
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
            if(\method_exists($row, 'deleteBelonges')) {
                $row->deleteBelonges();
            }
        }

        Response::success(Lang::get('DeletedSuccessfully', 'Deleted successfully'));
    }
}
