<?php
namespace Fogito\Http;

interface RequestInterface
{
    /**
     * Gets a variable from the $_REQUEST superglobal applying filters if needed
     *
     * @param string|null $name
     * @param string|array|null $filters
     * @param mixed $defaultValue
     * @return mixed
     */
    public static function get($name = null, $filters = null, $defaultValue = null);

    /**
     * Gets a variable from the $_POST superglobal applying filters if needed
     *
     * @param string|null $name
     * @param string|array|null $filters
     * @param mixed $defaultValue
     * @return mixed
     */
    public static function getPost($name = null, $filters = null, $defaultValue = null);

    /**
     * Gets variable from $_GET superglobal applying filters if needed
     *
     * @param string|null $name
     * @param string|array|null $filters
     * @param mixed $defaultValue
     * @return mixed
     */
    public static function getQuery($name = null, $filters = null, $defaultValue = null);

    /**
     * Gets variable from $_SERVER superglobal
     *
     * @param string $name
     * @return mixed
     */
    public static function getServer($name);

    /**
     * Checks whether $_SERVER superglobal has certain index
     *
     * @param string $name
     * @return boolean
     */
    public static function has($name);

    /**
     * Checks whether $_POST superglobal has certain index
     *
     * @param string $name
     * @return boolean
     */
    public static function hasPost($name);

    /**
     * Checks whether $_SERVER superglobal has certain index
     *
     * @param string $name
     * @return boolean
     */
    public static function hasQuery($name);

    /**
     * Checks whether $_SERVER superglobal has certain index
     *
     * @param string $name
     * @return mixed
     */
    public static function hasServer($name);

    /**
     * Gets HTTP header from request data
     *
     * @param string $header
     * @return string
     */
    public static function getHeader($header);

    /**
     * Gets HTTP schema (http/https)
     *
     * @return string
     */
    public static function getScheme();

    /**
     * Checks whether request has been made using ajax. Checks if $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest'
     *
     * @return boolean
     */
    public static function isAjax();

    /**
     * Checks whether request has been made using SOAP
     *
     * @return boolean
     */
    public static function isSoapRequested();

    /**
     * Checks whether request has been made using any secure layer
     *
     * @return boolean
     */
    public static function isSecureRequest();

    /**
     * Gets HTTP raws request body
     *
     * @return string
     */
    public static function getRawBody();

    /**
     * Gets active server address IP
     *
     * @return string
     */
    public static function getServerAddress();

    /**
     * Gets active server name
     *
     * @return string
     */
    public static function getServerName();

    /**
     * Gets information about schema, host and port used by the request
     *
     * @return string
     */
    public static function getHttpHost();

    /**
     * Gets most possibly client IPv4 Address. This methods search in $_SERVER['REMOTE_ADDR'] and optionally in $_SERVER['HTTP_X_FORWARDED_FOR']
     *
     * @param boolean|null $trustForwardedHeader
     * @return string
     */
    public static function getClientAddress($trustForwardedHeader = null);

    /**
     * Gets HTTP method which request has been made
     *
     * @return string
     */
    public static function getMethod();

    /**
     * Gets HTTP user agent used to made the request
     *
     * @return string
     */
    public static function getUserAgent();

    /**
     * Check if HTTP method match any of the passed methods
     *
     * @param string|array $methods
     * @return boolean
     */
    public static function isMethod($methods);

    /**
     * Checks whether HTTP method is POST. if $_SERVER['REQUEST_METHOD']=='POST'
     *
     * @return boolean
     */
    public static function isPost();

    /**
     *
     * Checks whether HTTP method is GET. if $_SERVER['REQUEST_METHOD']=='GET'
     *
     * @return boolean
     */
    public static function isGet();

    /**
     * Checks whether HTTP method is PUT. if $_SERVER['REQUEST_METHOD']=='PUT'
     *
     * @return boolean
     */
    public static function isPut();

    /**
     * Checks whether HTTP method is HEAD. if $_SERVER['REQUEST_METHOD']=='HEAD'
     *
     * @return boolean
     */
    public static function isHead();

    /**
     * Checks whether HTTP method is DELETE. if $_SERVER['REQUEST_METHOD']=='DELETE'
     *
     * @return boolean
     */
    public static function isDelete();

    /**
     * Checks whether HTTP method is OPTIONS. if $_SERVER['REQUEST_METHOD']=='OPTIONS'
     *
     * @return boolean
     */
    public static function isOptions();

    /**
     * Checks whether request include attached files
     *
     * @param boolean|null $notErrored
     * @return boolean
     */
    public static function hasFiles($notErrored = null);

    /**
     * Gets attached files as \Fogito\Http\Request\FileInterface compatible instances
     *
     * @param boolean|null $notErrored
     * @return \Fogito\Http\Request\FileInterface[]
     */
    public static function getUploadedFiles($notErrored = null);

    /**
     * Gets web page that refers active request. ie: http://www.google.com
     *
     * @return string
     */
    public static function getHTTPReferer();

    /**
     * Gets array with mime/types and their quality accepted by the browser/client from $_SERVER['HTTP_ACCEPT']
     *
     * @return array
     */
    public static function getAcceptableContent();

    /**
     * Gets best mime/type accepted by the browser/client from $_SERVER['HTTP_ACCEPT']
     *
     * @return array
     */
    public static function getBestAccept();

    /**
     * Gets charsets array and their quality accepted by the browser/client from $_SERVER['HTTP_ACCEPT_CHARSET']
     *
     * @return array
     */
    public static function getClientCharsets();

    /**
     * Gets best charset accepted by the browser/client from $_SERVER['HTTP_ACCEPT_CHARSET']
     *
     * @return string
     */
    public static function getBestCharset();

    /**
     * Gets languages array and their quality accepted by the browser/client from $_SERVER['HTTP_ACCEPT_LANGUAGE']
     *
     * @return array
     */
    public static function getLanguages();

    /**
     * Gets best language accepted by the browser/client from $_SERVER['HTTP_ACCEPT_LANGUAGE']
     *
     * @return string
     */
    public static function getBestLanguage();
}
