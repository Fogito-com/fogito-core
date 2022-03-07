<?php
namespace Categories\Controllers;

use Lib\Lang;
use Lib\Request;
use Lib\Response;
use Models\Categories;
use Models\Files;

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
        $data = Categories::findFirst([
            [
                '_id'        => Categories::objectId($id),
                'is_deleted' => [
                    '$ne' => true,
                ],
            ],
        ]);
        if (!$data) {
            Response::error(Lang::get('CategoryNotFound', 'Category not found'));
        }

        $response = [
            'id'           => $data->getId(),
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
