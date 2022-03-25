<?php
/**
 * @author Tural Ilyasov <senior2ral@gmail.com>
 * @link https://github.com/Fogito-com/fogito-core
 * @version 1.0.2
 * @package Fogito-Core
*/
namespace Fogito\Http;

use DateTime;
use DateTimeZone;
use Fogito\Exception;
use Fogito\Http\ResponseInterface;
use Fogito\Http\Response\CookiesInterface;
use Fogito\Http\Response\Headers;
use Fogito\Http\Response\HeadersInterface;
use Fogito\Url;

/**
 * Fogito\Http\Response
 *
 * Part of the HTTP cycle is return responses to the clients.
 * Fogito\HTTP\Response is the Fogito component responsible to achieve this task.
 * HTTP responses are usually composed by headers and body.
 *
 *<code>
 *  $response = new Fogito\Http\Response();
 *  $response->setStatusCode(200, "OK");
 *  $response->setJsonContent("<html><body>Hello</body></html>");
 *  $response->send();
 *</code>
 *
 */
class Response implements ResponseInterface
{
    const KEY_STATUS  = 'status';
    const KEY_CODE    = 'code';
    const KEY_MESSAGE = 'message';
    const KEY_DATA    = 'data';
    const KEY_COUNT   = 'count';

    const STATUS_SUCCESS = 'success';
    const STATUS_ERROR   = 'error';

    const CODE_SUCCESS = 200;
    const CODE_ERROR   = 400;

    /**
     * Content
     *
     * @var array
     * @access protected
     */
    protected static $_content = [];

    /**
     * Headers
     *
     * @var null|\Fogito\Http\Response\HeadersInterface
     * @access protected
     */
    protected static $_headers;

    /**
     * Cookies
     *
     * @var null|\Fogito\Ḩttp\Response\CookiesInterface
     * @access protected
     */
    protected static $_cookies;

    /**
     * Sets the HTTP response code
     *
     *<code>
     *  $response->setStatusCode(404, "Not Found");
     *</code>
     *
     * @param int $code
     * @param string $message
     * @throws Exception
     */
    public static function setStatusCode($code, $message)
    {
        if (is_int($code) === false ||
            is_string($message) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $headers = self::getHeaders();

        //We use HTTP/1.1 instead of HTTP/1.0
        $headers->setRaw('HTTP/1.1 ' . (string) $code . ' ' . $message);

        //We also define a 'Status' header with the HTTP status
        $headers->set('Status', (string) $code . ' ' . $message);

        self::$_headers = $headers;
    }

    /**
     * Sets a headers bag for the response externally
     *
     * @param \Fogito\Http\Response\HeadersInterface $headers
     * @throws Exception
     */
    public static function setHeaders($headers)
    {
        if (is_object($headers) === false ||
            $headers instanceof HeadersInterface === false) {
            throw new Exception('Invalid parameter type.');
        }

        self::$_headers = $headers;
    }

    /**
     * Returns headers set by the user
     *
     * @return \Fogito\Http\Response\HeadersInterface
     */
    public static function getHeaders()
    {
        if (is_null(self::$_headers) === true) {
            /*
             * A Fogito\Http\Response\Headers bag is temporary used to manage the headers
             * before sent them to the client
             */
            $headers        = new Headers();
            self::$_headers = $headers;
        }

        return self::$_headers;
    }

    /**
     * Sets a cookies bag for the response externally
     *
     * @param \Fogito\Http\Response\CookiesInterface $cookies
     * @throws Exception
     */
    public static function setCookies($cookies)
    {
        if (is_object($cookies) === false ||
            $cookies instanceof CookiesInterface === false) {
            throw new Exception('The cookies bag is not valid');
        }

        self::$_cookies = $cookies;
    }

    /**
     * Returns coookies set by the user
     *
     * @return \Fogito\Http\Response\CookiesInterface|null
     */
    public static function getCookies()
    {
        return self::$_cookies;
    }

    /**
     * Overwrites a header in the response
     *
     *<code>
     *  $response->setHeader("Content-Type", "text/plain");
     *</code>
     *
     * @param string $name
     * @param string $value
     * @throws Exception
     */
    public static function setHeader($name, $value)
    {
        if (is_string($name) === false ||
            is_string($value) === false) {
            throw new Exception('Invalid parameter type.');
        }

        self::getHeaders()->set($name, $value);
    }

    /**
     * Send a raw header to the response
     *
     *<code>
     *  $response->setRawHeader("HTTP/1.1 404 Not Found");
     *</code>
     *
     * @param string $header
     * @throws Exception
     */
    public static function setRawHeader($header)
    {
        if (is_string($header) === false) {
            throw new Exception('Invalid parameter type.');
        }

        self::getHeaders()->setRaw($header);
    }

    /**
     * Resets all the stablished headers
     */
    public static function resetHeaders()
    {
        self::getHeaders()->reset();
    }

    /**
     * Sets a Expires header to use HTTP cache
     *
     *<code>
     *  self::response->setExpires(new DateTime());
     *</code>
     *
     * @param DateTime $datetime
     * @throws Exception
     */
    public static function setExpires($datetime)
    {
        if (is_object($datetime) === false ||
            $datetime instanceof DateTime === false) {
            throw new Exception('datetime parameter must be an instance of DateTime');
        }

        $headers = self::getHeaders();
        try {
            $date = clone $datetime;
        } catch (\Exception $e) {
            return;
        }

        //All the expiration times are sent in UTC
        $timezone = new DateTimeZone('UTC');

        //Change the timezone to UTC
        $date->setTimezone($timezone);
        $utcDate = $date->format('D, d M Y H:i:s') . ' GMT';

        //The 'Expires' header set this info
        self::setHeader('Expires', $utcDate);
    }

    /**
     * Sends a Not-Modified response
     */
    public static function setNotModified()
    {
        self::setStatusCode(304, 'Not modified');
    }

    /**
     * Sets the response content-type mime, optionally the charset
     *
     *<code>
     *  $response->setContentType('application/pdf');
     *  $response->setContentType('text/plain', 'UTF-8');
     *</code>
     *
     * @param string $contentType
     * @param string|null $charset
     * @throws Exception
     */
    public static function setContentType($contentType, $charset = null)
    {
        if (is_string($contentType) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $headers = self::getHeaders();

        if (is_null($charset) === true) {
            $headers->set('Content-Type', $contentType);
        } elseif (is_string($charset) === true) {
            $headers->set('Content-Type', $contentType . '; charset=' . $charset);
        } else {
            throw new Exception('Invalid parameter type.');
        }
    }

    /**
     * Set a custom ETag
     *
     *<code>
     *  $response->setEtag(md5(time()));
     *</code>
     *
     * @param string $etag
     * @throws Exception
     */
    public static function setEtag($etag)
    {
        if (is_string($etag) === false) {
            throw new Exception('Invalid parameter type.');
        }

        self::getHeaders()->set('Etag', $etag);
    }

    /**
     * Redirect by HTTP to another action or URL
     *
     *<code>
     *  //Using a string redirect (internal/external)
     *  $response->redirect("posts/index");
     *  $response->redirect("http://en.wikipedia.org", true);
     *  $response->redirect("http://www.example.com/new-location", true, 301);
     *
     *  //Making a redirection based on a named route
     *  $response->redirect(array(
     *      "for" => "index-lang",
     *      "lang" => "jp",
     *      "controller" => "index"
     *  ));
     *</code>
     *
     * @param string|null $location
     * @param boolean|null $externalRedirect
     * @param int|null $statusCode
     * @throws Exception
     */
    public static function redirect($location = null, $externalRedirect = null, $statusCode = null)
    {
        $redirectPhrases = array(
            /* 300 */'Multiple Choices',
            /* 301 */'Moved Permanently',
            /* 302 */'Found',
            /* 303 */'See Other',
            /* 304 */'Not Modified',
            /* 305 */'Use Proxy',
            /* 306 */'Switch Proxy',
            /* 307 */'Temporary Redirect',
            /* 308 */'Permanent Redirect',
        );

        /* Type check */
        if (is_string($location) === false &&
            is_null($location) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($externalRedirect) === true) {
            $externalRedirect = false;
        } elseif (is_bool($externalRedirect) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_null($statusCode) === true) {
            $statusCode = 302;
        } elseif (is_int($statusCode) === false) {
            $statusCode = (int) $statusCode;
        }

        /* Preprocessing */
        if ($externalRedirect === true) {
            $header = $location;
        } else {
            $url    = new Url();
            $header = $url->get($location);
        }

        /* Execution */
        //The HTTP status is 302 by default, a temporary redirection
        if ($statusCode < 300 || $statusCode > 308) {
            $statusText = 'Redirect';
        } else {
            $statusText = $redirectPhrases[(int) $statusCode - 300];
        }

        self::setStatusCode($statusCode, $statusText);

        //Change the current location using 'Location'
        self::setHeader('Location', $header);
    }

    /**
     * Sets HTTP response body
     *
     *<code>
     *  $response->setJsonContent(["status" => "success", "code" => 100]);
     *</code>
     *
     * @param array $content
     */
    public static function setJsonContent($content = [])
    {
        if (is_array($content) === false) {
            throw new Exception('Invalid parameter type.');
        }
        
        self::$_content = json_encode($content, true);
    }

    /**
     * Gets the HTTP response body
     *
     * @return string|null
     */
    public static function getContent()
    {
        return self::$_content;
    }

    /**
     * Sends headers to the client
     */
    public static function sendHeaders()
    {
        if (is_object(self::$_headers) === true) {
            self::$_headers->send();
        }
    }

    /**
     * Sends cookies to the client
     */
    public static function sendCookies()
    {
        if (is_object(self::$_cookies) === true) {
            self::$_cookies->send();
        }
    }

    /**
     * Prints out HTTP response to the client
     *
     * @throws Exception
     */
    public static function send()
    {
        //Send headers
        self::sendHeaders();
        self::sendCookies();

        //Output the response body
        echo self::$_content;
        exit;
    }
    
    /**
     * error
     *
     * @param  mixed $message
     * @param  mixed $code
     * @param  mixed $details
     * @return void
     */
    public static function error($message, $code = false, $details = [])
    {
        if (!$code) {
            $code = self::CODE_ERROR;
        }
        $exception = new Exception($message, $code);
        if ($details) {
            $exception->setData([
                'details' => $details,
            ]);
        }

        throw $exception;
    }

    public static function success($data = [], $message = null, $count = null)
    {
        $content = [
            self::KEY_STATUS => self::STATUS_SUCCESS,
            self::KEY_CODE   => self::CODE_SUCCESS,
        ];

        if (isset($message)) {
            $content[self::KEY_MESSAGE] = $message;
        }

        if (isset($data)) {
            $content[self::KEY_DATA] = $data;
        }

        if (is_numeric($count)) {
            $content[self::KEY_COUNT] = $count;
        }

        self::setJsonContent($content);
        self::send();
    }


}
