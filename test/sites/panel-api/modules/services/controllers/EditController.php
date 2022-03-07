<?php
namespace Services\Controllers;

use Lib\Helpers;
use Lib\Lang;
use Lib\Request;
use Lib\Response;
use Models\Files;
use Models\Services;

class EditController
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
        $req          = Request::get('data');
        $id           = (string) trim($req['id']);
        $category_id  = (string) trim($req['category_id']);
        $title        = (string) trim($req['title']);
        $description  = (string) trim($req['description']);
        $translations = (array) $req['translations'];
        $slug         = (string) trim($req['slug']);
        $avatar_id    = (string) trim($req['avatar_id']);
        $index        = (int) trim($req['index']);
        $status       = (int) trim($req['status']);

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

        if (!$title) {
            Response::error(Lang::get('TitleIsRequired', 'Title is required'));
        }

        if (!$slug) {
            $slug = $title;
        }

        $exist = Services::findFirst([
            [
                '_id'         => [
                    '$ne' => $data->_id,
                ],
                'category_id' => $category_id,
                'title'       => $title,
                'is_deleted'  => [
                    '$ne' => true,
                ],
            ],
        ]);
        if ($exist) {
            Response::error(Lang::get('ServiceAlreadyExist', 'Service already exist'));
        }

        if ($avatar_id && $avatar_id != $data->avatar_id) {
            $file = Files::copyTempFile($avatar_id, [
                'parent_type' => Files::PARENT_TYPE_SERVICES,
                'parent_id' => $data->getId()
            ]);
        }

        $data->category_id  = $category_id;
        $data->title        = $title;
        $data->description  = $description;
        $data->translations = $translations;
        $data->slug         = Helpers::textToSlug($slug);
        $data->avatar_id    = $avatar_id ? $avatar_id : null;
        $data->index        = $index;
        $data->status       = $status;
        $data->save();

        Response::success(Lang::get('UpdatedSuccessfully', 'Updated successfully'));
    }
}
