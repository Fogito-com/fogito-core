<?php
namespace Services\Controllers;

use Lib\Lang;
use Lib\Request;
use Lib\Response;
use Models\Categories;
use Models\Files;
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
                'status'     => Services::STATUS_ACTIVE,
                'is_deleted' => [
                    '$ne' => true,
                ],
            ],
        ]);
        if (!$data) {
            Response::error(Lang::get('ServiceNotFound', 'Service not found'));
        }

        $category = Categories::findById($data->category_id);

        $response = [
            'id'          => $data->getId(),
            'category'    => Categories::getIdTitle($category),
            'title'       => Services::getTitle($data),
            'description' => Services::getDescription($data),
            'avatar'      => Files::getAvatarById($data->avatar_id),
            'slug'        => $data->slug,
        ];

        Response::success(null, $response);
    }
}
