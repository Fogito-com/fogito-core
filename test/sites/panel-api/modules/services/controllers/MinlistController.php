<?php
namespace Services\Controllers;

use Lib\Lang;
use Lib\Request;
use Lib\Response;
use Models\Services;

class MinlistController
{
    public function index()
    {
        $req         = Request::get();
        $category_id = (string) trim($req['category_id']);
        $keyword     = (string) trim($req['keyword']);

        $binds = [
            'status'     => Services::STATUS_ACTIVE,
            'is_deleted' => [
                '$ne' => true,
            ],
        ];

        if ($category_id) {
            $binds['category_id'] = $category_id;
        }

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

        $query = Services::find([
            $binds,
            'sort' => [
                'index' => 1,
            ],
        ]);

        $response = [];
        foreach ($query as $row) {
            $response[] = [
                'id'    => $row->getId(),
                'title' => Services::getTitle($row),
            ];
        }

        Response::success(null, $response);
    }
}
