<?php
namespace Products\Controllers;

use Fogito\Lib\Lang;
use Lib\Helpers;
use Lib\Request;
use Lib\Response;
use Models\Files;
use Models\Products;

class CreateController
{
    public function __construct($app)
    {
        if (!Request::isPost()) {
            Response::error(Lang::get('Invalid request method.'));
        }
    }


    public function index()
    {
        $req          = Request::get('data');
        $title        = (string) trim($req['title']);
        $description  = (string) trim($req['description']);
        $translations = (array) $req['translations'];
        $slug         = (string) trim($req['slug']);
        $avatar_id    = (string) trim($req['avatar_id']);

        if (!$title) {
            Response::error(Lang::get('TitleIsRequired', 'Title is required'));
        }

        if (!$slug) {
            $slug = $title;
        }

        $exist = Products::findFirst([
            [
                'title'      => $title,
                'is_deleted' => [
                    '$ne' => true,
                ],
            ],
        ]);
        if ($exist) {
            Response::error(Lang::get('ProductAlreadyExist', 'Product already exist'));
        }

        if ($avatar_id) {
            $file = Files::copyTempFile($avatar_id, [
                'parent_type' => Files::PARENT_TYPE_PRODUCTS,
            ]);
        }

        $i               = new Products();
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
