<?php
namespace Users\Controllers;

use Lib\Request;
use Lib\Response;
use Models\Files;
use Models\Users;

class ListController
{
    /**
     * index
     *
     * @return void
     */
    public function index()
    {
        $req             = Request::get();
        $categtypeory_id = (string) trim($req['type']);
        $keyword         = (string) trim($req['keyword']);
        $status          = (int) trim($req['status']);

        $binds = [
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

        if ($status) {
            $binds['status'] = $status;
        }

        list($limit, $skip) = Users::filterLimitSkip($req['limit'], $req['skip'], 100);
        $conditions         = [
            $binds,
            'limit' => $limit,
            'skip'  => $skip,
            'sort'  => [
                'fullname' => 1,
            ],
        ];

        $sort_field = trim($req['sort']['field']);
        $sort_order = trim($req['sort']['order']);

        $sort = [];
        if (in_array($sort_field, ['fullname', 'username', 'phone', 'email', 'status', 'created_at'])) {
            $conditions['sort'] = [$sort_field => $sort_order == 'desc' ? -1 : 1];
        }

        $query = Users::find($conditions);
        $count = Users::count([
            $binds,
        ]);

        $filesById = Files::combineById(Files::find([
            [
                '_id'        => [
                    '$in' => Files::convertIds(\array_values(\array_column($query, 'avatar_id'))),
                ],
                'is_deleted' => [
                    '$ne' => true,
                ],
            ],
        ]));

        $response = [];
        foreach ($query as $i => $row) {
            $avatar       = $filesById[$row->avatar_id];
            $response[$i] = [
                'id'         => $row->getId(),
                'fullname'   => $row->fullname,
                'username'   => $row->username,
                'email'      => $row->email,
                'phone'      => $row->phone,
                'birth'      => $row->birth,
                'gender'     => Users::getDataByValue($row->gender, Users::genderList()),
                'avatar'     => Files::getAvatarById($row->avatar_id, Files::SIZE_MEDIUM),
                'status'     => Users::getDataByValue($row->status, Users::statusList()),
                'type'       => Users::getDataByValue($row->type, Users::typeList()),
                'created_at' => Users::dateFormat($row->created_at, 'Y-m-d H:i'),
            ];
            if ($row->type == Users::TYPE_MODERATOR) {
                $response[$i]['level'] = Users::getDataByValue($row->level, Users::levelList());
            }
        }

        Response::success(null, $response, $count);
    }
}
