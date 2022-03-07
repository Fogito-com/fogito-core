<?php
namespace Services\Controllers;

use Lib\Lang;
use Lib\Request;
use Lib\Response;
use Models\Services;

class InfoController
{
    /**
     * index
     *
     * @return void
     */
    public function index()
    {
        $id   = Request::get('id');
        $data = Services::findFirst([
            [
                '_id'        => Services::objectId($id),
                'is_deleted' => [
                    '$ne' => true,
                ],
            ],
        ]);
        if (!$data) {
            Response::error(Lang::get('ServiceNotFound', 'Service not found'));
        }

        $response = [
            'id'           => $data->getId(),
            'category_id'  => $data->category_id,
            'title'        => $data->title,
            'description'  => $data->description,
            'translations' => $data->translations,
            'slug'         => $data->slug,
            'avatar_id'    => $data->avatar_id,
            'status'       => $data->status,
            'index'        => $data->index,
        ];

        Response::success(null, $response);
    }
}
