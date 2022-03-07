<?php
namespace Services\Controllers;

use Lib\Helpers;
use Lib\Lang;
use Lib\Request;
use Lib\Response;
use Models\Files;
use Models\Services;

class CreateController
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
        $category_id  = (string) trim($req['category_id']);
        $title        = (string) trim($req['title']);
        $description  = (string) trim($req['description']);
        $translations = (array) $req['translations'];
        $slug         = (string) trim($req['slug']);
        $avatar_id    = (string) trim($req['avatar_id']);

        if (!$slug) {
            $slug = $title;
        }

        if (!Services::isMongoId($category_id)) {
            Response::error(Lang::get('CategoryWasNotSelected', 'Category was not selected'));
        }

        if (!$title) {
            Response::error(Lang::get('TitleIsRequired', 'Title is required'));
        }

        $exist = Services::findFirst([
            [
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

        if ($avatar_id) {
            $file = Files::copyTempFile($avatar_id, [
                'parent_type' => Files::PARENT_TYPE_SERVICES,
            ]);
        }

        $i               = new Services();
        $i->category_id  = $category_id;
        $i->title        = $title;
        $i->description  = $description;
        $i->translations = $translations;
        $i->slug         = Helpers::textToSlug($slug);
        $i->avatar_id    = $avatar_id ? $avatar_id : null;
        $i->setIndex();
        $i->save();

        if ($file) {
            $file->parent_id = $i->getId();
            $file->save();
        }

        Response::success(Lang::get('CreatedSuccessfully', 'Created successfully'));

    }
}
