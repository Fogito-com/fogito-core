<?php
namespace Categories\Controllers;

use Lib\Lang;
use Lib\Request;
use Lib\Response;
use Models\Categories;
use Models\Files;

class ListController
{
    /**
     * index
     *
     * @return void
     */
    public function index()
    {
        $req     = Request::get();
        $keyword = (string) trim($req['keyword']);
        $status  = (int) trim($req['status']);

        $binds = [
            'is_deleted' => [
                '$ne' => true,
            ],
        ];

        if ($keyword) {
            $binds['$or'][] = [
                'title' => [
                    '$regex'   => $keyword,
                    '$options' => 'i',
                ],
            ];
            $languages = Lang::getLanguages();
            foreach ($languages as $row) {
                $binds['$or'][] = [
                    'translations.title.' . $row['short_code'] => [
                        '$regex'   => $keyword,
                        '$options' => 'i',
                    ],
                ];
            }
        }

        if ($status) {
            $binds['status'] = $status;
        }

        list($limit, $skip) = Categories::filterLimitSkip($req['limit'], $req['skip'], 100);
        $conditions         = [
            $binds,
            'limit' => $limit,
            'skip'  => $skip,
            'sort'  => [
                'index' => 1,
            ],
        ];

        $sort_field = trim($req['sort']['field']);
        $sort_order = trim($req['sort']['order']);

        $sort = [];
        if (in_array($sort_field, ['title', 'slug', 'status', 'index', 'created_at'])) {
            $conditions['sort'] = [$sort_field => $sort_order == 'desc' ? -1 : 1];
        }

        $query = Categories::find($conditions);
        $count = Categories::count([
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
        foreach ($query as $row) {
            $avatar     = $filesById[$row->avatar_id];
            $response[] = [
                'id'          => $row->getId(),
                'title'       => Categories::getTitle($row),
                'description' => Categories::getDescription($row),
                'avatar'      => Files::getAvatar($avatar, Files::SIZE_MEDIUM),
                'status'      => Categories::getDataByValue($row->status, Categories::statusList()),
                'index'       => $row->index,
                'created_at'  => Categories::dateFormat($row->created_at, 'Y-m-d H:i'),
            ];
        }

        Response::success(null, $response, $count);
    }
}
