<?php
declare(strict_types=1);

namespace Fogito\Http {
    final class Request
    {
        public static function envMode()
        {
            return 'production';
        }

        public static function get($key)
        {
            return null;
        }

        public static function getServer($key)
        {
            return null;
        }
    }

    final class Response
    {
    }
}

namespace Fogito\Lib {
    final class Lang
    {
        public static function setLang($lang)
        {
        }

        public static function getLang()
        {
            return 'en';
        }

        public static function get($key, $default = null)
        {
            return $default;
        }

        public static function setData($data)
        {
        }
    }

    final class Cache
    {
        public static function get($key)
        {
            return false;
        }

        public static function set($key, $value, $duration)
        {
        }
    }

    final class Company
    {
        public static function setData($data)
        {
        }

        public static function getData()
        {
            return (object) array('business_model' => 1);
        }

        public static function getId()
        {
            return 'compat-company';
        }
    }
}

namespace Fogito\Models {
    final class CoreSettings
    {
        public static function getServer()
        {
            return 'http://unused.invalid';
        }

        public static function curl($url, $data)
        {
            return json_encode(array(
                'status' => 'success',
                'data' => array('account' => array('id' => 'compat-user')),
            ));
        }

        public static function setData($data)
        {
        }
    }
}

namespace {
    set_error_handler(static function ($severity, $message, $file, $line) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    });

    require dirname(__DIR__).'/src/Exception.php';
    require dirname(__DIR__).'/src/Config.php';
    require dirname(__DIR__).'/src/Lib/Auth.php';

    $config = new Fogito\Config(array('enabled' => true));
    if (!isset($config['enabled']) || $config['enabled'] !== true || count($config) !== 1) {
        throw new RuntimeException('Config interface compatibility check failed.');
    }
    $config['name'] = 'Fogito';
    unset($config['name']);

    Fogito\Lib\Auth::init();
    if (Fogito\Lib\Auth::getId() !== 'compat-user') {
        throw new RuntimeException('Auth compatibility check failed.');
    }

    echo "PHP 8 compatibility regression checks passed.\n";
}
