<?php
namespace Users\Controllers;

use Lib\Lang;
use Lib\Request;
use Lib\Response;
use Models\Users;

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

        $response = [
            'id'        => $data->getId(),
            'type'      => $data->type,
            'username'  => $data->username,
            'fullname'  => $data->fullname,
            'email'     => $data->email,
            'operator'  => $data->operator,
            'phone'     => [
                'prefix' => substr($data->phone, 0, 5),
                'number' => substr($data->phone, 5, 7),
            ],
            'gender'    => $data->gender,
            'birth'     => $data->birth,
            'avatar_id' => $data->avatar_id,
            'status'    => $data->status,
        ];

        if($data->type == Users::TYPE_MODERATOR) {
            $response['level'] = $data->level;
        }

        Response::success(null, $response);
    }
}
