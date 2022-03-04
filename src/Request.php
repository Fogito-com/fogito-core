<?php
namespace Fogito;

class Request
{
    const METHOD_GET    = 'GET';
    const METHOD_POST   = 'POST';
    const METHOD_PUT    = 'PUT';
    const METHOD_DELETE = 'DELETE';

    private static $executable = false;

    public static $url;
    public static $method   = self::METHOD_GET;
    public static $protocol = 'http';
    public static $query    = [];
    public static $post     = [];
    public static $input    = [];
    public static $server   = [];

    /**
     * __construct
     *
     * @return void
     */
    public function __construct()
    {
        $this->execute();
    }

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

        self::$protocol = isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] === 1) || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ? 'https' : 'http';
        self::$url      = self::$protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        self::$method   = \in_array($_SERVER['REQUEST_METHOD'], [self::METHOD_GET, self::METHOD_POST, self::METHOD_PUT, self::METHOD_DELETE]) ? $_SERVER['REQUEST_METHOD'] : self::METHOD_GET;

        \parse_str(\parse_url(self::$url, \PHP_URL_QUERY), $query);
        self::$query = $query;

        if ($_POST) {
            self::$post = $_POST;
        }

        $input = file_get_contents('php://input');
        if ($input) {
            self::$input = json_decode($input, true);
        }
    }

    /**
     * allowMethods
     *
     * @param  mixed $allowedMethods
     * @param  mixed $message
     * @return void
     */
    public static function allowMethods($allowedMethods = [], $message = false)
    {
        if ($allowedMethods && !\in_array(self::getMethod(), $allowedMethods)) {
            throw new \Exception($message ? $message : 'Request method not allowed');
        }
    }

    /**
     * get
     *
     * @param  mixed $key
     * @param  mixed $value
     * @return void
     */
    public static function get($key = null, $value = null)
    {
        $query = self::$query;
        if ($key) {
            if (\array_key_exists($key, $query) && $query[$key]) {
                $value = $query[$key];
            }
            return $value;
        }

        return $query;
    }

    /**
     * getPost
     *
     * @param  mixed $key
     * @param  mixed $value
     * @return void
     */
    public static function getPost($key = null, $value = null)
    {
        $query = self::$post;
        if ($key) {
            if (\array_key_exists($key, $query) && $query[$key]) {
                $value = $query[$key];
            }
            return $value;
        }

        return $query;
    }

    /**
     * getInput
     *
     * @param  mixed $key
     * @param  mixed $value
     * @return void
     */
    public static function getInput($key = null, $value = null)
    {
        $query = self::$input;
        if ($key) {
            if (\array_key_exists($key, $query)) {
                $value = $query[$key];
            }
            return $value;
        }

        return $query;
    }

    /**
     * getAll
     *
     * @param  mixed $key
     * @param  mixed $value
     * @return void
     */
    public static function getAll($key = null, $value = null)
    {
        $query = \array_merge(self::$query, self::$post, self::$input);
        if ($key) {
            if (\array_key_exists($key, $query)) {
                $value = $query[$key];
            }
            return $value;
        }

        return $query;
    }

    /**
     * getServer
     *
     * @param  mixed $key
     * @param  mixed $value
     * @return void
     */
    public static function getServer($key = null, $value = null)
    {
        $query = self::$server;
        if ($key) {
            if (\array_key_exists($key, $query)) {
                $value = $query[$key];
            }
            return $value;
        }

        return $query;
    }

    /**
     * getUrl
     *
     * @return void
     */
    public static function getUrl()
    {
        return self::$url;
    }

    /**
     * getProtocol
     *
     * @return void
     */
    public static function getProtocol()
    {
        return self::$protocol;
    }

    /**
     * getQuery
     *
     * @return void
     */
    public static function getQuery()
    {
        return self::$query;
    }

    /**
     * getMethod
     *
     * @return void
     */
    public static function getMethod()
    {
        return self::$method;
    }

    /**
     * getUserAgent
     *
     * @return void
     */
    public static function getUserAgent()
    {
        return self::getServer('HTTP_USER_AGENT');
    }

    /**
     * getIP
     *
     * @return void
     */
    public static function getIP()
    {
        return self::getServer('REMOTE_ADDR');
    }

    /**
     * isHttps
     *
     * @return void
     */
    public static function isHttps()
    {
        return self::getProtocol() == 'https';
    }
}
