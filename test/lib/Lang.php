<?php
namespace Lib;

use Lib\Request;

class Lang
{
    protected static $db          = \Models\Translations::class;
    protected static $data        = [];
    protected static $defaultLang = 'en';
    protected static $currentLang = 'en';
    protected static $templateId;
    protected static $languages = [
        [
            'short_code' => 'en',
            'name'       => 'English',
            'locale'     => 'en_US',
        ],
        [
            'short_code' => 'ru',
            'name'       => 'Russian',
            'locale'     => 'ru_RU',
        ],
        [
            'short_code' => 'tr',
            'name'       => 'Turkish',
            'locale'     => 'tr_TR',
        ],
    ];
    protected static $cache = [
        'db'       => null,
        'lifetime' => 3600,
        'key'      => 'lang',
    ];
    protected static $executable = false;

    /**
     * execute
     *
     * @return void
     */
    public static function execute()
    {
        if (self::$executable) {
            return;
        }
        self::$executable = true;

        $lang = self::getRequestLang();
        if (!$lang || !\in_array($lang, \array_column(self::$languages, 'short_code'))) {
            $lang = self::getDefaultLang();
        }
        self::setLang($lang);

        if (!self::$templateId) {
            throw new \Exception('Template ID is required');
        }

        $data = self::getByTemplateId(self::$templateId);
        self::setData($data);
    }

    /**
     * setDb
     *
     * @param  mixed $db
     * @return void
     */
    public static function setDb($db)
    {
        self::$db = $db;
    }

    /**
     * getDb
     *
     * @return void
     */
    public static function getDb()
    {
        return self::$db;
    }

    /**
     * setTemplateId
     *
     * @param  mixed $templateId
     * @return void
     */
    public static function setTemplateId($templateId)
    {
        self::$templateId = $templateId;
    }

    /**
     * getTemplateId
     *
     * @return void
     */
    public static function getTemplateId()
    {
        return self::$templateId;
    }

    /**
     * getRequestLang
     *
     * @return void
     */
    public static function getRequestLang()
    {
        return (new Request)->get('lang');
    }

    /**
     * setData
     *
     * @param  mixed $data
     * @return void
     */
    public static function setData($data = [])
    {
        return self::$data = $data;
    }

    /**
     * getData
     *
     * @return void
     */
    public static function getData()
    {
        return self::$data;
    }

    /**
     * setLang
     *
     * @param  mixed $value
     * @return void
     */
    public static function setLang($value)
    {
        return self::$currentLang = $value;
    }

    /**
     * getLang
     *
     * @return void
     */
    public static function getLang()
    {
        return self::$currentLang;
    }

    /**
     * setDefaultLang
     *
     * @param  mixed $value
     * @return void
     */
    public static function setDefaultLang($value)
    {
        return self::$defaultLang = $value;
    }

    /**
     * getDefaultLang
     *
     * @return void
     */
    public static function getDefaultLang()
    {
        return self::$defaultLang;
    }

    /**
     * setLanguages
     *
     * @param  mixed $languages
     * @return void
     */
    public static function setLanguages($languages = [])
    {
        self::$languages = $languages;
    }

    /**
     * getLanguages
     *
     * @return void
     */
    public static function getLanguages()
    {
        return self::$languages;
    }

    /**
     * setCache
     *
     * @param  mixed $cache
     * @return void
     */
    public static function setCache($cache = [])
    {
        self::$cache = $cache;
    }

    /**
     * getCache
     *
     * @return void
     */
    public static function getCache()
    {
        return self::$cache;
    }

    /**
     * getByTemplateId
     *
     * @param  mixed $template_id
     * @return void
     */
    public static function getByTemplateId($template_id)
    {
        $data         = [];
        $translations = self::$db::find([
            [
                'templates'  => [
                    '$in' => [$template_id],
                ],
                'is_deleted' => [
                    '$ne' => true,
                ],
            ],
        ]);
        if ($translations) {
            foreach ($translations as $row) {
                if ($row->translations) {
                    if (\property_exists($row->translations, self::$currentLang)) {
                        $translation = $row->translations->{self::$currentLang};
                    } elseif (\property_exists($row->translations, self::$defaultLang)) {
                        $translation = $row->translations->{self::$defaultLang};
                    } elseif ($value = $row->translations->{\current(\array_keys($row->translations))}) {
                        $translation = $value;
                    } else {
                        $translation = $row->key;
                    }
                } else {
                    $translation = $row->key;
                }
                $data[$row->key] = $translation;
            }
        }
        return $data;
    }

    /**
     * get
     *
     * @param  mixed $key
     * @param  mixed $translate
     * @param  mixed $update
     * @return void
     */
    public static function get($key, $translate = null, $update = false)
    {
        $key = (string) trim($key);
        if (\array_key_exists($key, self::$data)) {
            if ($update) {
                $item = self::$db::findFirst([
                    [
                        'key'        => $key,
                        'is_deleted' => [
                            '$ne' => true,
                        ],
                    ],
                ]);
                if ($item) {
                    $item->translations = \array_merge((array) $item->translations, [
                        self::$currentLang => $translate,
                    ]);
                    $item->save();
                }
                self::$data[$key] = $translate ? $translate : $key;
            }
        } elseif (\strlen($key) > 0) {
            self::add($key, $translate);
            self::$data[$key] = $translate ? $translate : $key;
        }

        return self::$data[$key];
    }

    /**
     * add
     *
     * @param  mixed $key
     * @param  mixed $translate
     * @return void
     */
    public static function add($key, $translate = null)
    {
        if ($key) {
            $data = self::$db::findFirst([
                [
                    'key'        => $key,
                    'is_deleted' => [
                        '$ne' => true,
                    ],
                ],
            ]);
            if (!$data) {
                $i            = new self::$db();
                $i->key       = $key;
                $i->templates = [
                    self::$templateId,
                ];
                $i->translations = [
                    self::$currentLang => $translate ? $translate : $key,
                ];
                $i->save();
            } else {
                if (!in_array(self::$templateId, $data->templates)) {
                    $data->templates[] = self::$templateId;
                    $data->save();
                }
            }
        }
        return true;
    }
}
