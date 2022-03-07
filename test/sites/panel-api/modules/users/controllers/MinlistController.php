<?php
namespace Users\Controllers;

use Lib\Request;
use Lib\Response;
use Models\Users;

class MinlistController
{
    public function index()
    {
        $req     = Request::get();
        $type    = (string) trim($req['type']);
        $keyword = (string) trim($req['keyword']);

        $binds = [
            'status'     => Users::STATUS_ACTIVE,
            'is_deleted' => [
                '$ne' => true,
            ],
        ];

        if ($type) {
            $binds['type'] = $type;
        }

        if ($keyword) {
            $binds['$or'] = [
                [
                    'fullname' => [
                        '$regex'   => $keyword,
                        '$options' => 'i',
                    ],
                ],
                [
                    'username' => [
                        '$regex'   => $keyword,
                        '$options' => 'i',
                    ],
                ],
                [
                    'phone' => [
                        '$regex'   => $keyword,
                        '$options' => 'i',
                    ],
                ],
                [
                    'email' => [
                        '$regex'   => $keyword,
                        '$options' => 'i',
                    ],
                ],
            ];
        }

        $query = Users::find([
            $binds,
            'sort' => [
                'fullname' => 1,
            ],
        ]);

        $response = [];
        foreach ($query as $row) {
            $response[] = [
                'id'       => $row->getId(),
                'fullname' => $row->fullname,
            ];
        }

        Response::success(null, $response);
    }
}
