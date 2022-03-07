<?php
namespace Data\Controllers;

use Lib\Lang;
use Lib\Request;
use Lib\Response;
use Models\Translations;

class TranslationsController
{
    /**
     * index
     *
     * @return void
     */
    public function index()
    {
        $req    = Request::ge();
        $app_id = (int) trim($req['app_id']);
        $lang   = (string) trim($req['lang']);

        $template = Translations::getDataByValue($app_id, Translations::templateList());
        if (!$template) {
            Response::error(Lang::get('WrongAppId', 'Wrong app ID'));
        }

        Lang::setLang($lang);
        $data = Lang::getByTemplateId($app_id);

        $res = new Response();
        $res->setJsonContent([
            Response::KEY_STATUS => Response::STATUS_SUCCESS,
            Response::KEY_CODE   => Response::CODE_SUCCESS,
            Response::KEY_DATA   => $data,
            'template'           => $template,
        ])->send();
    }

    /**
     * set
     *
     * @return void
     */
    public function set()
    {
        $req    = Request::get();
        $app_id = (int) trim($req['app_id']);
        $lang   = (string) trim($req['lang']);
        $data   = \array_slice((array) $req['data'], 0, 100);

        $template = Translations::getDataByValue($app_id, Translations::templateList());
        if (!$template) {
            Response::error(Lang::get('WrongAppId', 'Wrong app ID'));
        }

        if (!\in_array($lang, \array_column(Lang::getLanguages(), 'short_code'))) {
            Response::error(Lang::get('LanguageNotFound', 'Language not found'));
        }

        $translationsByKey = Translations::combine('key', Translations::find([
            [
                'key'        => [
                    '$in' => \array_keys($data),
                ],
                'is_deleted' => [
                    '$ne' => 1,
                ],
            ],
        ]));

        foreach ($data as $key => $value) {
            if (\is_string($key) && \preg_match('/[a-zA-Z0-9\\_]{2,100}/i', trim($key)) && \preg_match('/[0-9\_\-\+\?\@\!\)\(\}\{\*\%\#\=\/\.\,\;\:\"\'\|\[ ]{1,100}/i', trim($value))) {
                $translate = $translationsByKey[$key];
                if (!$translation) {
                    $i            = new Translations();
                    $i->key       = $key;
                    $i->templates = [
                        $app_id,
                    ];
                    $i->translations = [
                        $lang => $value ? $value : $key,
                    ];
                    $i->save();
                } else {
                    if (!in_array($app_id, $translate->templates)) {
                        $translate->templates[] = $app_id;
                        $translate->save();
                    }
                }
            }
        }

        Response::success();
    }
}
