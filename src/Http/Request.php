<?php
namespace Fogito\Http;

use Fogito\Filter;
use Fogito\Exception;
use Fogito\Text;

class Request
{
    /**
     * Filter
     *
     * @var null
     * @access protected
     */
    protected static $filter;

    /**
     * Raw Body
     *
     * @var null
     * @access protected
     */
    protected static $rawBody;

    /**
     * Gets a variable from the $_REQUEST superglobal applying filters if needed.
     * If no parameters are given the $_REQUEST superglobal is returned
     *
     *<code>
     *  $userEmail = $request->get("user_email");
     *  $userEmail = $request->get("user_email", "email");
     *</code>
     *
     * @param string|null $name
     * @param string|array|null $filters
     * @param mixed $defaultValue
     * @return mixed
     * @throws Exception
     */
    public static function get($name = null, $filters = null, $defaultValue = null)
    {
        /* Validate input */
        if (is_string($name) === false && is_null($name) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_string($filters) === false && is_array($filters) === false &&
            is_null($filters) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $raw = [];
        foreach($_REQUEST as $key => $value) {
            $raw[$key] = $value;
        }
        foreach(self::getJsonRawBody() as $key => $value) {
            $raw[$key] = $value;
        }

        /* Get data */
        if (is_null($name) === false) {
            if (isset($raw[$name]) === true) {
                $value = $raw[$name];

                //Apply filters is required
                if (!is_null($filters)) {
                    //Get filter service
                    if (!is_object(self::$filter)) {
                        self::$filter = new Filter();
                    }

                    return self::$filter->sanitize($value, $filters);
                } else {
                    return $value;
                }
            }

            return $defaultValue;
        }

        return $raw;
    }


    public static function getPost($name = null, $filters = null, $defaultValue = null)
    {
        if (is_string($name) === false && is_null($name) === false) {
            throw new Exception('Invalid parmeter type.');
        }

        if (is_string($filters) === false && is_array($filters) === false &&
            is_null($filters) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($name) === false) {
            if (isset($_POST[$name]) === true) {
                $value = $_POST[$name];

                if (!is_null($filters)) {
                    if (!is_object(self::$filter)) {
                        self::$filter = new Filter();
                    }

                    return self::$filter->sanitize($value, $filters);
                }

                return $value;
            }

            return $defaultValue;
        }

        return $_POST;
    }

    public static function getQuery($name = null, $filters = null, $defaultValue = null)
    {
        if (is_string($name) === false && is_null($name) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($filters) === false && is_string($filters) === false &&
            is_array($filters) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($name) === false) {
            if (isset($_GET[$name]) === true) {
                $value = $_GET[$name];

                if (!is_null($filters)) {
                    if (!is_object(self::$filter)) {
                        self::$filter = new Filter();
                    }

                    return self::$filter->sanitize($value, $filters);
                }

                return $value;
            }

            return $defaultValue;
        }

        return $_GET;
    }


    public static function getServer($name)
    {
        if (is_string($name) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($_SERVER[$name]) === true) {
            return $_SERVER[$name];
        }

        return null;
    }


    public static function setServer($name, $value=false)
    {
        $_SERVER[$name] = $value;
        return true;
    }

    /**
     * Checks whether $_REQUEST superglobal has certain index
     *
     * @param string $name
     * @return boolean
     * @throws Exception
     */
    public static function has($name)
    {
        if (is_string($name) === false) {
            throw new Exception('Invalid parameter type.');
        }

        return isset($_REQUEST[$name]);
    }

    /**
     * Checks whether $_POST superglobal has certain index
     *
     * @param string $name
     * @return boolean
     * @throws Exception
     */
    public static function hasPost($name)
    {
        if (is_string($name) === false) {
            throw new Exception('Invalid parameter type.');
        }

        return isset($_POST[$name]);
    }

    /**
     * Checks whether $_GET superglobal has certain index
     *
     * @param string $name
     * @return boolean
     * @throws Exception
     */
    public static function hasQuery($name)
    {
        if (is_string($name) === false) {
            throw new Exception('Invalid parameter type.');
        }

        return isset($_GET[$name]);
    }

    /**
     * Checks whether $_SERVER superglobal has certain index
     *
     * @param string $name
     * @return mixed
     * @throws Exception
     */
    public static function hasServer($name)
    {
        if (is_string($name) === false) {
            throw new Exception('Invalid parameter type.');
        }

        return isset($_SERVER[$name]);
    }

    /**
     * Gets HTTP header from request data
     *
     * @param string $header
     * @return string
     * @throws Exception
     */
    public static function getHeader($header)
    {
        if (is_string($header) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (isset($_SERVER[$header]) === true) {
            return $_SERVER[$header];
        } else {
            if (isset($_SERVER['HTTP_' . $header]) === true) {
                return $_SERVER['HTTP_' . $header];
            }
        }

        return '';
    }


    public static function getScheme()
    {
        $https = self::getServer('HTTPS');
        if (empty($https) === false) {
            if ($https === 'off') {
                $scheme = 'http';
            } else {
                $scheme = 'https';
            }
        } else {
            $scheme = 'http';
        }

        return $scheme;
    }


    public static function isAjax()
    {
        return (self::getHeader('HTTP_X_REQUESTED_WITH') == 'XMLHttpRequest' ? true : false);
    }


    public static function isSoapRequested()
    {
        if (isset($_SERVER['HTTP_SOAPACTION']) === true) {
            return true;
        } elseif (isset($_SERVER['CONTENT_TYPE']) === true) {
            if (strpos($_SERVER['CONTENT_TYPE'], 'application/soap+xml') !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks whether request has been made using any secure layer
     *
     * @return boolean
     */
    public static function isSecureRequest()
    {
        return (self::getScheme() === 'https' ? true : false);
    }

    /**
     * Gets HTTP raw request body
     *
     * @return string
     */
    public static function getRawBody($name = null, $filters = null, $defaultValue = null)
    {
        if (is_string(self::$rawBody)) {
            return self::$rawBody;
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $raw = file_get_contents('php://input');

            if ($raw === false) {
                $raw = [];
            }

            self::$rawBody = $raw;
            return $raw;
        }

        return '';
    }

    /**
     * Gets decoded JSON HTTP raw request body
     *
     * @return mixed
     */
    public static function getJsonRawBody()
    {
        $rawBody = self::getRawBody();
        if (is_string($rawBody) === true) {
            return json_decode($rawBody, 1);
        }
    }

    /**
     * Gets decoded JSON HTTP raw request body
     *
     * @return mixed
     */
    public static function getInput($name = null, $filters = null, $defaultValue = null)
    {
        if (is_string($name) === false && is_null($name) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($filters) === false && is_string($filters) === false &&
            is_array($filters) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $raw = json_decode(self::getRawBody(), true);
        if (!is_array($raw)) {
            throw new Exception('Invalid raw body.');
        }

        if (is_null($name) === false) {
            if (isset($raw[$name]) === true) {
                $value = $raw[$name];

                if (!is_null($filters)) {
                    if (!is_object(self::$filter)) {
                        self::$filter = new Filter();
                    }

                    return self::$filter->sanitize($value, $filters);
                }

                return $value;
            }

            return $defaultValue;
        }
        return $raw;
    }


    public static function getServerAddress()
    {
        if (isset($_SERVER['SERVER_ADDR']) === true) {
            return $_SERVER['SERVER_ADDR'];
        }

        return gethostbyname('localhost');
    }

    /**
     * Gets active server name
     *
     * @return string
     */
    public static function getServerName()
    {
        if (isset($_SERVER['SERVER_NAME']) === true) {
            return $_SERVER['SERVER_NAME'];
        }

        return 'localhost';
    }

    /**
     * Gets information about schema, host and port used by the request
     *
     * @return string
     */
    public static function getHttpHost()
    {
        //Get the server name from _SERVER['HTTP_HOST']
        $httpHost = self::getServer('HTTP_HOST');
        if (isset($httpHost) === true) {
            return $httpHost;
        }

        //Get current scheme
        $scheme = self::getScheme();

        //Get the server name from _SERVER['SERVER_NAME']
        $serverName = self::getServer('SERVER_NAME');

        //Get the server port from _SERVER['SERVER_PORT']
        $serverPort = self::getServer('SERVER_PORT');

        //Check if the request is a standard http
        $isStdName = ($scheme === 'http' ? true : false);
        $isStdPort = ($serverPort === 80 ? true : false);
        $isStdHttp = ($isStdName && $isStdPort ? true : false);

        //Check if the request is a secure http request
        $isSecureScheme = ($scheme === 'https' ? true : false);
        $isSecurePort   = ($serverPort === 443 ? true : false);
        $isSecureHttp   = ($isSecureScheme && $isSecurePort ? true : false);

        //If is is a standard http we return the server name only
        if ($isStdHttp === true ||
            $isSecureHttp === true) {
            return $serverName;
        }

        return $serverName . ':' . $serverPort;
    }

    /**
     * Gets most possible client IPv4 Address. This method search in $_SERVER['REMOTE_ADDR'] and optionally in $_SERVER['HTTP_X_FORWARDED_FOR']
     *
     * @param boolean|null $trustForwardedHeader
     * @return string
     * @throws Exception
     */
    public static function getClientAddress($trustForwardedHeader = null)
    {
        if (is_null($trustForwardedHeader) === true) {
            $trustForwardedHeader = false;
        } elseif (is_bool($trustForwardedHeader) === false) {
            throw new Exception('Invalid parameter type.');
        }

        //Proxies use this IP
        if ($trustForwardedHeader === true &&
            isset($_SERVER['HTTP_X_FORWARDED_FOR']) === true) {
            $address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        if (isset($address) === false) {
            if (isset($_SERVER['REMOTE_ADDR']) === true) {
                $address = $_SERVER['REMOTE_ADDR'];
            }
        }

        if (isset($address) === true) {
            if (strpos($address, ',') !== false) {
                //The client address has multiple parts, only return the first part
                $addresses = explode(',', $address);
                return $addresses[0];
            }

            return $address;
        }

        return false;
    }

    /**
     * Gets HTTP method which request has been made
     *
     * @return string
     */
    public static function getMethod()
    {
        if (isset($_SERVER['REQUEST_METHOD']) === true) {
            return $_SERVER['REQUEST_METHOD'];
        }

        return '';
    }

    /**
     * Gets HTTP user agent used to made the request
     *
     * @return string
     */
    public static function getUserAgent()
    {
        if (isset($_SERVER['HTTP_USER_AGENT']) === true) {
            return $_SERVER['HTTP_USER_AGENT'];
        } else {
            return '';
        }
    }

    /**
     * Check if HTTP method match any of the passed methods
     *
     * @param string|array $methods
     * @return boolean
     */
    public static function isMethod($methods)
    {
        $methodHttp = self::getMethod();

        if (is_string($methods) === true) {
            return ($methods == $methodHttp ? true : false);
        } else {
            foreach ($methods as $method) {
                if ($method === $methodHttp) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Checks whether HTTP method is POST. if $_SERVER['REQUEST_METHOD']=='POST'
     *
     * @return boolean
     */
    public static function isPost()
    {
        return (self::getMethod() === 'POST' ? true : false);
    }

    /**
     * Checks whether HTTP method is GET. if $_SERVER['REQUEST_METHOD']=='GET'
     *
     * @return boolean
     */
    public static function isGet()
    {
        return (self::getMethod() === 'GET' ? true : false);
    }

    /**
     * Checks whether HTTP method is PUT. if $_SERVER['REQUEST_METHOD']=='PUT'
     *
     * @return boolean
     */
    public static function isPut()
    {
        return (self::getMethod() === 'PUT' ? true : false);
    }

    /**
     * Checks whether HTTP method is PATCH. if $_SERVER['REQUEST_METHOD']=='PATCH'
     *
     * @return boolean
     */
    public static function isPatch()
    {
        return (self::getMethod() === 'PATCH' ? true : false);
    }

    /**
     * Checks whether HTTP method is HEAD. if $_SERVER['REQUEST_METHOD']=='HEAD'
     *
     * @return boolean
     */
    public static function isHead()
    {
        return (self::getMethod() === 'HEAD' ? true : false);
    }

    /**
     * Checks whether HTTP method is DELETE. if $_SERVER['REQUEST_METHOD']=='DELETE'
     *
     * @return boolean
     */
    public static function isDelete()
    {
        return (self::getMethod() === 'DELETE' ? true : false);
    }

    /**
     * Checks whether HTTP method is OPTIONS. if $_SERVER['REQUEST_METHOD']=='OPTIONS'
     *
     * @return boolean
     */
    public static function isOptions()
    {
        return (self::getMethod() === 'OPTIONS' ? true : false);
    }

    /**
     * Checks whether request includes attached files
     *
     * @param null|boolean $notErrored
     * @return boolean
     * @throws Exception
     */
    public static function hasFiles($notErrored = null)
    {
        if (is_null($notErrored) === true) {
            $notErrored = true;
        } elseif (is_bool($notErrored) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($_FILES) === false) {
            return 0;
        }

        $count = 0;
        foreach ($_FILES as $file) {
            if ($notErrored === false) {
                ++$count;
            } else {
                if (isset($file['error']) === true) {
                    foreach ($file['error'] as $error) {
                        if ($error === \UPLOAD_ERR_OK) {
                            ++$count;
                            break;
                        }
                    }
                }
            }
        }

        return $count;
    }


    /**
     * Returns the available headers in the request
     *
     * @return array
     */
    public static function getHeaders()
    {
        if (is_array($_SERVER) === false) {
            return;
        }

        $result = array();
        foreach ($_SERVER as $key => $value) {
            if (Text::startsWith($key, 'HTTP_') === true) {
                $result[] = substr($key, 5);
            }
        }

        return $result;
    }

    /**
     * Gets web page that refers active request. ie: http://www.google.com
     *
     * @return string
     */
    public static function getHTTPReferer()
    {
        if (isset($_SERVER['HTTP_REFERER']) === true) {
            return $_SERVER['HTTP_REFERER'];
        }

        return '';
    }

    /**
     * Process a request header and return the one with best quality
     *
     * @param array $qualityParts
     * @param string $name
     * @return string
     * @throws Exception
     */
    protected static function _getBestQuality($qualityParts, $name)
    {
        if (is_array($qualityParts) === false ||
            is_string($name) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $quality = 0;
        $i       = 0;

        foreach ($qualityParts as $accept) {
            if ($i === 0) {
                $quality      = $accept['quality'];
                $selectedName = $accept[$name];
            } else {
                if ($quality < $accept['quality']) {
                    $quality      = $accept['quality'];
                    $selectedName = $accept[$name];
                }
            }

            ++$i;
        }

        return $selectedName;
    }

    /**
     * Gets languages array and their quality accepted by the browser/client from $_SERVER['HTTP_ACCEPT_LANGUAGE']
     *
     * @return array
     */
    public static function getLanguages()
    {
        return self::_getQualityHeader('HTTP_ACCEPT_LANGUAGE', 'language');
    }

    /**
     * Gets best language accepted by the browser/client from $_SERVER['HTTP_ACCEPT_LANGUAGE']
     *
     * @return string
     */
    public static function getBestLanguage()
    {
        return self::_getBestQuality(self::getLanguages(), 'language');
    }

    public static function isDevMode()
    {
        if(self::getServer("HTTP_ENV_MODE") === "development")
            return true;
        return false;
    }
}
