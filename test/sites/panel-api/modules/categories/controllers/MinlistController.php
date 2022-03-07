<?php
namespace Categories\Controllers;

use Lib\Lang;
use Lib\Request;
use Lib\Response;
use Models\Categories;

class MinlistController
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

        $binds = [
            'status'     => Categories::STATUS_ACTIVE,
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

        $query = Categories::find([
            $binds,
            'sort' => [
                'index' => 1,
            ],
        ]);

        $response = [];
        foreach ($query as $row) {
            $response[] = [
                'id'    => $row->getId(),
                'title' => Categories::getTitle($row),
            ];
        }

        Response::success(null, $response);
    }
}
