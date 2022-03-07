<?php
namespace Models;

use Lib\Auth;
use Lib\Lang;

class Users extends \Lib\ModelManager
{
    const STATUS_MODERATE = 1;
    const STATUS_ACTIVE   = 2;
    const STATUS_INACTIVE = 3;

    const TYPE_USER      = 'user';
    const TYPE_MODERATOR = 'moderator';

    const LEVEL_OPERATOR       = 'operator';
    const LEVEL_SUPERVISOR     = 'supervisor';
    const LEVEL_ADMINISTRATION = 'administration';

    const GENDER_MALE   = 'male';
    const GENDER_FEMALE = 'female';

    const OPERATOR_AZERCELL = 1;
    const OPERATOR_BAKCELL  = 2;
    const OPERATOR_NAR      = 3;

    public $_id;
    public $type = self::TYPE_USER;
    public $username;
    public $password;
    public $fullname;
    public $gender = self::GENDER_MALE;
    public $birth;
    public $operator;
    public $phone;
    public $phone_is_verified = false;
    public $email;
    public $email_is_verified = false;
    public $avatar_id;
    public $level;
    public $status = self::STATUS_ACTIVE;

    /**
     * getSource
     *
     * @return void
     */
    public static function getSource()
    {
        return 'users';
    }

    /**
     * filterData
     *
     * @param  mixed $data
     * @return void
     */
    public static function filterData($data)
    {
        if (!$data) {
            return [];
        }

        $response = [
            'id'                => $data->getId(),
            'type'              => self::getDataByValue($data->type, self::typeList()),
            'username'          => $data->username,
            'fullname'          => $data->fullname,
            'gender'            => self::getDataByValue($data->gender, self::genderList()),
            'phone'             => [
                'label'    => '(0' . substr($data->phone, 3, 2) . ') ' . substr($data->phone, 5, 3) . '-' . substr($data->phone, 8, 2) . '-' . substr($data->phone, 10, 2),
                'operator' => $data->operator ? self::getDataByValue($data->operator, self::operatorList()) : null,
                'prefix'   => (int) substr($data->phone, 0, 5),
                'number'   => (int) substr($data->phone, 5, 7),
            ],
            'phone_is_verified' => (bool) $data->phone_is_verified,
            'email'             => $data->email,
            'email_is_verified' => (bool) $data->email_is_verified,
            'birth'             => $data->birth,
            'avatar'            => Files::getAvatarById($data->avatar_id),
            'status'            => self::getDataByValue($data->status, self::statusList()),
        ];

        if ($data->type == self::TYPE_MODERATOR) {
            $response = array_merge($response, [
                'level' => self::getDataByValue($data->level, self::levelList()),
            ]);
        }
        return $response;
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
                'label' => Lang::get('Moderate'),
                'value' => self::STATUS_MODERATE,
            ],
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
     * levelList
     *
     * @return void
     */
    public static function levelList()
    {
        return [
            [
                'label' => Lang::get('Operator'),
                'value' => self::LEVEL_OPERATOR,
            ],
            [
                'label' => Lang::get('Supervisor'),
                'value' => self::LEVEL_SUPERVISOR,
            ],
            [
                'label' => Lang::get('Administrator'),
                'value' => self::LEVEL_ADMINISTRATION,
            ],
        ];
    }

    /**
     * typeList
     *
     * @return void
     */
    public static function typeList()
    {
        return [
            [
                'label' => Lang::get('Moderator'),
                'value' => self::TYPE_MODERATOR,
            ],
            [
                'label' => Lang::get('User'),
                'value' => self::TYPE_USER,
            ],
        ];
    }

    /**
     * genderList
     *
     * @return void
     */
    public static function genderList()
    {
        return [
            [
                'label' => Lang::get('Male'),
                'value' => self::GENDER_MALE,
            ],
            [
                'label' => Lang::get('Female'),
                'value' => self::GENDER_FEMALE,
            ],
        ];
    }

    /**
     * operatorList
     *
     * @return void
     */
    public static function operatorList()
    {
        return [
            [
                'label' => Lang::get('Azercell'),
                'value' => self::OPERATOR_AZERCELL,
            ],
            [
                'label' => Lang::get('Bakcell'),
                'value' => self::OPERATOR_BAKCELL,
            ],
            [
                'label' => Lang::get('Nar'),
                'value' => self::OPERATOR_NAR,
            ],
        ];
    }

    /**
     * prefixList
     *
     * @return void
     */
    public static function prefixList()
    {
        return [
            [
                'label' => '010',
                'value' => 99410,
            ],
            [
                'label' => '050',
                'value' => 99450,
            ],
            [
                'label' => '051',
                'value' => 99451,
            ],
            [
                'label' => '055',
                'value' => 99455,
            ],
            [
                'label' => '060',
                'value' => 99460,
            ],
            [
                'label' => '070',
                'value' => 99470,
            ],
            [
                'label' => '077',
                'value' => 99477,
            ],
            [
                'label' => '099',
                'value' => 99499,
            ],
        ];
    }

    /**
     * yearList
     *
     * @return void
     */
    public static function yearList()
    {
        return array_map(function ($value) {
            return [
                'label' => (string) $value,
                'value' => (string) $value,
            ];
        }, range(date("Y"), date("Y") - 70));
    }

    /**
     * monthList
     *
     * @return void
     */
    public static function monthList()
    {
        return array_map(function ($value) {
            if ($value < 10) {
                $value = "0" . $value;
            }
            return [
                'label' => (string) Lang::get(date("F", strtotime(date("Y-{$value}-01")))),
                'value' => (string) $value,
            ];
        }, range(1, 12));
    }

    /**
     * dayList
     *
     * @return void
     */
    public static function dayList()
    {
        return array_map(function ($value) {
            if ($value < 10) {
                $value = "0" . $value;
            }
            return [
                'label' => (string) $value,
                'value' => (string) $value,
            ];
        }, range(1, 31));
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

    /**
     * getAccessByLevel
     *
     * @param  mixed $level
     * @return void
     */
    public static function getAccessByLevel($level)
    {
        return Auth::getLevel() == self::LEVEL_ADMINISTRATOR || (Auth::getLevel() == self::LEVEL_SUPERVISOR && \in_array($level, [self::LEVEL_OPERATOR]));
    }
}
