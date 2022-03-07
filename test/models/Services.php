<?php
namespace Models;

use Lib\Lang;

class Services extends \Lib\ModelManager
{    
    const STATUS_ACTIVE   = 1;
    const STATUS_INACTIVE = 2;

    public $_id;
    public $category_id;
    public $title;
    public $description;
    public $translations = [
        'title'       => [],
        'description' => [],
    ];
    public $slug;
    public $avatar_id;
    public $index;
    public $status = self::STATUS_ACTIVE;

    /**
     * getSource
     *
     * @return void
     */
    public static function getSource()
    {
        return 'services';
    }

    /**
     * statusList
     *
     * @return void
     */
    public static function statusList()
    {
        return [
            [
                'label' => Lang::get('Active'),
                'value' => self::STATUS_ACTIVE,
            ],
            [
                'label' => Lang::get('InActive'),
                'value' => self::STATUS_INACTIVE,
            ],
        ];
    }

    /**
     * setIndex
     *
     * @return void
     */
    public function setIndex()
    {
        $this->index = self::generateIndex();
    }

    /**
     * generateIndex
     *
     * @param  mixed $binds
     * @return void
     */
    public static function generateIndex($binds = [])
    {
        $last = self::findFirst([
            $binds,
            'sort' => [
                'index' => -1,
            ],
        ]);
        if ($last) {
            $index = $last->index + 1;
        } else {
            $index = 1;
        }
        return $index;
    }

    /**
     * getTitle
     *
     * @param  mixed $data
     * @return void
     */
    public static function getTitle($data)
    {
        if (\property_exists($data->translations->title, Lang::getLang())) {
            $translate = $data->translations->title->{Lang::getLang()};
            if($translate) {
                return $translate;
            }
        }
        return $data->title;
    }

    /**
     * getDescription
     *
     * @param  mixed $data
     * @return void
     */
    public static function getDescription($data)
    {
        if (\property_exists($data->translations->description, Lang::getLang())) {
            $translate = $data->translations->description->{Lang::getLang()};
            if($translate) {
                return $translate;
            }
        }
        return $data->description;
    }

    /**
     * getIdTitle
     *
     * @param  mixed $data
     * @return void
     */
    public static function getIdTitle($data)
    {
        if ($data) {
            return [
                'id'    => $data->getId(),
                'title' => self::getTitle($data),
            ];
        }
        return [
            'id'    => null,
            'title' => Lang::get('Unknown'),
        ];
    }
    
    /**
     * deleteBelonges
     *
     * @return void
     */
    public function deleteBelonges()
    {
        if ($this->avatar_id) {
            $avatar = Files::findFirst([
                [
                    '_id'        => Files::objectId($this->avatar_id),
                    'is_deleted' => [
                        '$ne' => true,
                    ],
                ],
            ]);
            if ($avatar) {
                $avatar->is_deleted = true;
                $avatar->save();
            }
        }
    }
}
