<?php
namespace Controllers;

use Lib\Auth;
use Lib\Cache;
use Lib\Helpers;
use Lib\Lang;
use Lib\Request;
use Lib\Response;
use Lib\SimpleImage;
use Models\Files;
use Models\FilesTemp;

class Upload
{
    /**
     * initialize
     *
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
        $req         = Request::getPost();
        $aspectRatio = '1/1';
        if (trim($req['aspect_ratio'])) {
            $exp = explode('x', trim($req['aspect_ratio']));
            if ($exp[0] > 0 && $exp[1] > 0) {
                $aspectRatio = implode('/', $exp);
            }
        }

        $cropType = false;
        if (trim($req['crop_type'])) {
            $cropType = strtolower(trim($req['crop_type']));
        }

        $data = Files::getFileData($_FILES['file']);

        if (Cache::is_brute_force('files-upload-' . Request::getServer('REMOTE_ADDR'), ['minute' => 60])) {
            Response::error(Lang::get('AttemptReached'));
        }
        if (!in_array($data['type'], Files::TYPES)) {
            Response::error(Lang::get('FileTypeNotAllowed', 'This file type is not allowed'));
        }
        if (!$data['file']) {
            Response::error(Lang::get('YouDidntChooseFile', 'You didn`t choose file'));
        }
        if ($data['size'] > Files::MAX_FILE_SIZE) {
            Response::error(Lang::get('FileSizeMaxLimit', 'File size cann`t be larger than ' . Helpers::filesize(Files::MAX_FILE_SIZE)));
        }

        $path = STORAGE_PATH . '/' . Files::pathGen([12]);
        mkdir($path, 0777, true);

        $path_org = $path . '/' . Files::pathGen([6], 1);
        mkdir($path_org, 0777, true);

        $orgfile = $path_org . '/' . Helpers::strToLat($data['filename']) . '.' . $data['extension'];
        copy($data['file'], $orgfile);

        $avatars = [];

        switch ($data['extension']) {
            case 'jpe':
            case 'jpeg':
            case 'jpg':
            case 'png':
            case 'gif':
                foreach (Files::DIMENSIONS as $key => $size) {
                    $path_3   = Files::pathGen([6], 1);
                    $filepath = $path . '/' . $path_3;
                    if (!is_dir($filepath)) {
                        mkdir($filepath, 0777, true);
                    }

                    $img           = new SimpleImage($data['file']);
                    list($x1, $x2) = explode('/', $aspectRatio);

                    switch ($cropType) {
                        default:
                            $img->thumbnail($size, $size);
                            break;

                        case 'thumbnail':
                            $img->thumbnail($size, $size);
                            break;

                        case 'bestfit':
                            $img->best_fit($size, $size);
                            break;
                    }

                    $img->save($filepath . '/' . $size . '.' . $data['extension'], Files::QUALITY);
                    $avatars[$key] = Files::excludeRootPath($filepath) . '/' . $size . '.' . $data['extension'];
                }
                break;

            default:
                break;
        }

        $i = new FilesTemp();
        if (Auth::isAuth()) {
            $i->user_id = Auth::getId();
        }
        $i->file      = (string) Files::excludeRootPath($orgfile);
        $i->filename  = (string) $data['filename'];
        $i->type      = (string) $data['type'];
        $i->extension = (string) $data['extension'];
        $i->size      = (float) $data['size'];
        $i->avatars   = (array) $avatars;
        $i->save();

        Response::success(Lang::get('UploadedSuccessfully', 'Uploaded successfully'), \array_merge(Files::filterData($i), [
            'file'    => CLOUD_URL . '/' . $i->file,
            'avatars' => Files::getAvatar($i),
        ]));
    }
}
