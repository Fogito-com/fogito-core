<?php
namespace Users\Controllers;

use Lib\Auth;
use Lib\Lang;
use Lib\Request;
use Lib\Response;
use Models\Files;
use Models\Users;

class UpdateController
{
    public function index()
    {
        $req   = Request::get('data');
        $id    = (string) trim($req['id']);
        $field = (string) trim($req['field']);
        $value = $req['value'];

        $data = Users::findFirst([
            [
                '_id'        => Users::objectId($id),
                'is_deleted' => [
                    '$ne' => true,
                ],
            ],
        ]);

        if (!$data) {
            Response::error(Lang::get('UserNotFound', 'User not found'));
        }

        switch ($field) {
            case 'password':
                if ($data->type == Users::TYPE_MODERATOR && !Users::getAccessByLevel($data->level)) {
                    Response::error(Lang::get('YouDontHavePermissionToUpdatePassword', 'You do not have permission to update password'));
                }
                if (!is_string($password) || strlen($password) > 50 || strlen($password) < 6) {
                    Response::error(Lang::get('PasswordError'));
                }
                $data->password = Auth::passwordHash($value);
                $data->save();
                break;

            case 'avatar':
                if ($value && $value != $data->avatar_id) {
                    $file = Files::copyTempFile($value, [
                        'parent_type' => Files::PARENT_TYPE_USERS,
                        'parent_id'   => $data->getId(),
                    ]);
                    $data->avatar_id = $file->getId();
                    $data->save();
                }
                $data->avatar_id = $value ? $value : null;
                $data->save();
                break;

            default:
                Response::error(Lang::get('FieldNotFound', 'Field not found'));
                break;
        }

        Response::success(Lang::get('UpdatedSucessfully', 'Updated sucessfully'));
    }
}
