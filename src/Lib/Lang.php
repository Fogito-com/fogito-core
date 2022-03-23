<?php
namespace Fogito\Lib;

class Lang
{
    const KEY_INFORMATION_NOT_FOUND = 'InformationNotFound';
    const KEY_ITEMS_NOT_FOUND       = 'ItemsNotFound';
    const KEY_PERMISSION_DENIED     = 'PermissionDenied';
    const KEY_ATTEMPT_REACHED       = 'AttemptReached';
    const KEY_WRONG_ID              = 'WrongId';
    const KEY_TECHNICAL_ERROR       = 'TechnicalError';
    const KEY_PARAMETER_TYPE_ERROR  = 'ParameterTypeError';

    public static $_data      = [];
    public static $_lang      = 'en';
    public static $_languages = [];

    /**
     * setData
     *
     * @param  array $data
     * @return void
     */
    public static function setData($data = [])
    {
        self::$_data = $data;
    }

    /**
     * getData
     *
     * @return array
     */
    public static function getData()
    {
        return self::$_data;
    }

    /**
     * getLang
     *
     * @return string
     */
    public static function getLang()
    {
        return self::$_lang;
    }

    /**
     * setLang
     *
     * @param  string $lang
     * @return void
     */
    public static function setLang($lang)
    {
        self::$_lang = $lang;
    }

    /**
     * getLanguages
     *
     * @return array
     */
    public static function getLanguages()
    {
        return self::$_languages;
    }

    /**
     * setLanguages
     *
     * @param  array $languages
     * @return void
     */
    public static function setLanguages($languages = [])
    {
        self::$_languages = $languages;
    }

    /**
     * get
     *
     * @param  string $key
     * @param  null|string $default
     * @return string
     */
    public static function get($key, $default = null)
    {
        if (!array_key_exists($key, self::$_data)) {
            self::$_data[$key] = $default ? $default : $key;
        }

        return self::$_data[$key];
    }
}
