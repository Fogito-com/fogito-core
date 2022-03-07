<?php
namespace Categories\Controllers;

use Lib\Helpers;
use Lib\Lang;
use Lib\Request;
use Lib\Response;
use Models\Categories;
use Models\Files;

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
        $title        = (string) trim($req['title']);
        $description  = (string) trim($req['description']);
        $translations = (array) $req['translations'];
        $slug         = (string) trim($req['slug']);
        $avatar_id    = (string) trim($req['avatar_id']);
        $index        = (int) trim($req['index']);
        $status       = (int) trim($req['status']);

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

        if (!$title) {
            Response::error(Lang::get('TitleIsRequired', 'Title is required'));
        }

        if (!$slug) {
            $slug = $title;
        }

        $exist = Categories::findFirst([
            [
                '_id'        => [
                    '$ne' => $data->_id,
                ],
                'title'      => $title,
                'is_deleted' => [
                    '$ne' => true,
                ],
            ],
        ]);
        if ($exist) {
            Response::error(Lang::get('CategoryAlreadyExist', 'Category already exist'));
        }

        if ($avatar_id && $avatar_id != $data->avatar_id) {
            $file = Files::copyTempFile($avatar_id, [
                'parent_type' => Files::PARENT_TYPE_CATEGORIES,
                'parent_id' => $data->getId()
            ]);
        }

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
