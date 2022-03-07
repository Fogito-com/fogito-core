<?php
namespace Models;

use Lib\Lang;

class Translations extends \Lib\ModelManager
{
    const TEMPLATE_ID_WEB       = 1;
    const TEMPLATE_ID_APP       = 2;
    const TEMPLATE_ID_PANEL     = 3;
    const TEMPLATE_ID_API       = 4;
    const TEMPLATE_ID_PANEL_API = 5;
    const TEMPLATE_ID_CLOUD_API = 6;

    public $_id;
    public $key;
    public $templates    = [];
    public $translations = [];

    /**
     * getSource
     *
     * @return void
     */
    public static function getSource()
    {
        return 'translations';
    }
    
    /**
     * beforeSave
     *
     * @param  mixed $forceInsert
     * @return void
     */
    public function beforeSave($forceInsert = false)
    {}

    /**
     * templateList
     *
     * @return void
     */
    public static function templateList()
    {

        return [
            [
                'label' => Lang::get('Web'),
                'value' => self::TEMPLATE_ID_WEB,
            ],
            [
                'label' => Lang::get('App'),
                'value' => self::TEMPLATE_ID_APP,
            ],
            [
                'label' => Lang::get('Panel'),
                'value' => self::TEMPLATE_ID_PANEL,
            ],
            [
                'label' => Lang::get('Api', 'Web/App API'),
                'value' => self::TEMPLATE_ID_API,
            ],
            [
                'label' => Lang::get('PanelApi', 'Panel API'),
                'value' => self::TEMPLATE_ID_PANEL_API,
            ],
            [
                'label' => Lang::get('CloudApi', 'Cloud API'),
                'value' => self::TEMPLATE_ID_CLOUD_API,
            ],
        ];
    }
}
