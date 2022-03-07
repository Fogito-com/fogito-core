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
                'status'     => Categories::STATUS_ACTIVE,
                'is_deleted' => [
                    '$ne' => true,
                ],
            ],
        ]);
        if (!$data) {
            Response::error(Lang::get('CategoryNotFound', 'Category not found'));
        }

        $response = [
            'id'          => $data->getId(),
            'title'       => Categories::getTitle($data),
            'description' => Categories::getDescription($data),
            'avatar'      => Files::getAvatarById($data->avatar_id),
            'slug'        => $data->slug,
        ];

        Response::success(null, $response);
    }
}
