<?php
/**
 * @author Tural Ilyasov <senior2ral@gmail.com>
 * @link https://github.com/Fogito-com/fogito-core
 * @version 1.0.2
 * @package Fogito-Core
*/
namespace Fogito\Http;

interface ResponseInterface
{
    /**
     * Sets the HTTP response code
     *
     * @param int $code
     * @param string $message
     */
    public static function setStatusCode($code, $message);

    /**
     * Returns headers set by the user
     *
     * @return \Fogito\Http\Response\Headers
     */
    public static function getHeaders();

    /**
     * Overwrites a header in the response
     *
     * @param string $name
     * @param string $value
     */
    public static function setHeader($name, $value);

    /**
     * Send a raw header to the response
     *
     * @param string $header
     */
    public static function setRawHeader($header);

    /**
     * Resets all the stablished headers
     */
    public static function resetHeaders();

    /**
     * Sets output expire time header
     *
     * @param DateTime $datetime
     */
    public static function setExpires($datetime);

    /**
     * Sends a Not-Modified response
     */
    public static function setNotModified();

    /**
     * Sets the response content-type mime, optionally the charset
     *
     * @param string $contentType
     * @param string|null $charset
     */
    public static function setContentType($contentType, $charset = null);

    /**
     * Redirect by HTTP to another action or URL
     *
     * @param string|null $location
     * @param boolean|null $externalRedirect
     * @param int|null $statusCode
     */
    public static function redirect($location = null, $externalRedirect = null, $statusCode = null);

    /**
     * Sets HTTP response body
     *
     * @param array $content
     */
    public static function setJsonContent($content = []);

    /**
     * Gets the HTTP response body
     *
     * @return array
     */
    public static function getContent();

    /**
     * Sends headers to the client
     */
    public static function sendHeaders();

    /**
     * Sends cookies to the client
     */
    public static function sendCookies();

    /**
     * Prints out HTTP response to the client
     */
    public static function send();
}
