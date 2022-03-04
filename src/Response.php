<?php
namespace Fogito;

use Fogito\Exception;

class Response
{
    protected static $format       = self::FORMAT_JSON;
    protected static $xmlContainer = 'response';
    protected static $callback;

    protected static $status   = self::STATUS_SUCCESS;
    protected static $code     = self::CODE_SUCCESS;
    protected static $message  = null;
    protected static $data     = [];
    protected static $headers  = [];
    protected static $response = [];

    public static $keyStatus  = 'status';
    public static $keyCode    = 'code';
    public static $keyMessage = 'message';
    public static $keyData    = 'data';

    const STATUS_SUCCESS       = 'success';
    const STATUS_ERROR         = 'error';
    const CODE_SUCCESS         = 100;
    const CODE_INVALID_REQUEST = 1003;

    const FORMAT_JSON = 'json';
    const FORMAT_XML  = 'xml';

    /**
     * error
     *
     * @param  mixed $message
     * @param  mixed $code
     * @param  mixed $details
     * @return void
     */
    public static function error($message, $code = self::CODE_INVALID_REQUEST, $details = [])
    {
        $exception = new Exception($message, $code);
        if ($details) {
            $exception->addErrorDetails($details);
        }

        throw $exception;
    }

    /**
     * send
     *
     * @return void
     */
    public static function send()
    {
        $response = [
            self::$keyStatus  => self::$status,
            self::$keyCode    => self::$code,
            self::$keyMessage => self::$message,
        ];

        if (self::$code == self::STATUS_SUCCESS) {
            $response[self::$keyData] = self::$data;
        }

        if (self::$response) {
            if (\is_array(self::$response)) {
                $response = \array_merge($response, self::$response);
            }
        }

        if (self::$callback) {
            $response = \call_user_func_array(self::$callback, [$response]);
        } else {
            switch (self::$format) {
                case self::FORMAT_JSON:
                    self::setHeader('Content-Type', 'application/json; charset=utf-8');
                    $response = \json_encode($response, true);
                    break;

                case self::FORMAT_XML:
                    self::setHeader('Content-Type', 'application/xml; charset=utf-8');
                    $xmlReader = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><' . self::$xmlContainer . '/>');
                    $xml       = self::arrayToXml($response, $xmlReader);
                    $response  = preg_replace('~[\r\n\t]+~', '', $xml->asXML());
                    break;

                default;
                    break;
            }
        }

        \ob_clean();
        foreach (self::getHeaders() as $key => $value) {
            \header($key . ': ' . $value);
        }
        echo $response;
        exit;
    }

    /**
     * arrayToXml
     *
     * @param  mixed $arr
     * @param  mixed $xml
     * @return void
     */
    public static function arrayToXml(array $arr, \SimpleXMLElement $xml)
    {
        foreach ($arr as $k => $v) {
            is_array($v)
            ? self::arrayToXml($v, $xml->addChild($k))
            : $xml->addChild($k, $v);
        }
        return $xml;
    }

    /**
     * setFormat
     *
     * @param  mixed $format
     * @return void
     */
    public static function setFormat($format)
    {
        self::$format = $format;
    }

    /**
     * setXmlContainer
     *
     * @param  mixed $value
     * @return void
     */
    public static function setXmlContainer($value)
    {
        self::$xmlContainer = $value;
    }

    /**
     * setCallback
     *
     * @param  mixed $callback
     * @return void
     */
    public static function setCallback($callback)
    {
        if (!\is_callable($callback)) {
            throw new \Exception('Callback is not function', self::CODE_INVALID_REQUEST);
        }
        self::$callback = $callback;
    }

    /**
     * setResponse
     *
     * @param  mixed $response
     * @return void
     */
    public static function setResponse($response = [])
    {
        if (!\is_array($response)) {
            throw new \Exception('Response is not array', self::CODE_INVALID_REQUEST);
        }
        self::$response = $response;
    }

    /**
     * getResponse
     *
     * @return void
     */
    public static function getResponse()
    {
        return self::$response;
    }

    /**
     * setStatus
     *
     * @param  mixed $status
     * @return void
     */
    public static function setStatus($status)
    {
        self::$status = $status;
    }

    /**
     * getStatus
     *
     * @return void
     */
    public static function getStatus()
    {
        return self::$status;
    }

    /**
     * setCode
     *
     * @param  mixed $code
     * @return void
     */
    public static function setCode($code)
    {
        self::$code = $code;
    }

    /**
     * getCode
     *
     * @return void
     */
    public static function getCode()
    {
        return self::$code;
    }

    /**
     * setMessage
     *
     * @param  mixed $message
     * @return void
     */
    public static function setMessage($message)
    {
        self::$message = $message;
    }

    /**
     * getMessage
     *
     * @return void
     */
    public static function getMessage()
    {
        return self::$message;
    }

    /**
     * setData
     *
     * @param  mixed $data
     * @return void
     */
    public static function setData($data)
    {
        self::$data = $data;
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
     * setHeaders
     *
     * @param  mixed $headers
     * @return void
     */
    public static function setHeaders($headers = [])
    {
        foreach ($headers as $key => $value) {
            self::setHeader($key, $value);
        }
    }

    /**
     * setHeader
     *
     * @param  mixed $key
     * @param  mixed $value
     * @return void
     */
    public static function setHeader($key, $value)
    {
        self::$headers[$key] = $value;
    }

    /**
     * getHeaders
     *
     * @return void
     */
    public static function getHeaders()
    {
        return self::$headers;
    }

    /**
     * setStatusKey
     *
     * @param  mixed $value
     * @return void
     */
    public static function setStatusKey($value)
    {
        self::$keyStatus = $value;
    }

    /**
     * setCodeKey
     *
     * @param  mixed $value
     * @return void
     */
    public static function setCodeKey($value)
    {
        self::$keyCode = $value;
    }

    /**
     * setMessageKey
     *
     * @param  mixed $value
     * @return void
     */
    public static function setMessageKey($value)
    {
        self::$keyMessage = $value;
    }

    /**
     * setDataKey
     *
     * @param  mixed $value
     * @return void
     */
    public static function setDataKey($value)
    {
        self::$keyData = $value;
    }

    /**
     * setJsonContent
     *
     * @param  mixed $response
     * @return void
     */
    public static function setJsonContent($response = [])
    {
        self::setHeader('Content-type', 'application/json');
        self::setResponse($response);
    }

    /**
     * redirect
     *
     * @param  mixed $url
     * @return void
     */
    public static function redirect($url = '/')
    {
        \ob_clean();
        header('Location: ' . $url);
        exit;
    }

    /**
     * dump
     *
     * @param  mixed $data
     * @return void
     */
    public static function dump($data)
    {
        echo '<pre>';
        print_r((array) $data);
        exit;
    }
}
