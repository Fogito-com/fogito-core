<?php
namespace Fogito\Http\Response;

use Fogito\Http\Response\HeadersInterface;
use Fogito\Exception;

/**
 * Fogito\Http\Response\Headers
 *
 * This class is a bag to manage the response headers
 */
class Headers implements HeadersInterface
{
    /**
     * Headers
     *
     * @var null|array
     * @access protected
    */
    protected $_headers;

    /**
     * Sets a header to be sent at the end of the request
     *
     * @param string $name
     * @param string $value
     * @throws Exception
     */
    public function set($name, $value)
    {
        if (is_string($name) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($this->_headers) === false) {
            $this->_headers = array();
        }

        $this->_headers[$name] = $value;
    }

    /**
     * Gets a header value from the internal bag
     *
     * @param string $name
     * @return string|boolean
     * @throws Exception
     */
    public function get($name)
    {
        if (is_string($name) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($this->_headers) === false) {
            $this->_headers = array();
        }

        if (isset($this->_headers[$name]) === true) {
            return $this->_headers[$name];
        }

        return false;
    }

    /**
     * Sets a raw header to be sent at the end of the request
     *
     * @param string $header
     * @throws Exception
     */
    public function setRaw($header)
    {
        if (is_string($header) === false) {
            throw new Exception('Invalid parameter type.');
        }

        if (is_array($this->_headers) === false) {
            $this->_headers = array();
        }

        $this->_headers[$header] = null;
    }

    /**
     * Sends the headers to the client
     *
     * @return boolean
     */
    public function send()
    {
        if (headers_sent() === false) {
            foreach ($this->_headers as $header => $value) {
                if (empty($value) === false) {
                    //Default header
                    header($header.': '.$value);
                } else {
                    //Raw header
                    header($header);
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Reset set headers
     */
    public function reset()
    {
        $this->_headers = array();
    }

    /**
     * Restore a \Fogito\Http\Response\Headers object
     *
     * @param array $data
     * @return \Fogito\Http\Response\Headers
     * @throws Exception
     */
    public static function __set_state($data)
    {
        if (is_array($data) === false) {
            throw new Exception('Invalid parameter type.');
        }

        $headers = new Headers();
        if (isset($data['_headers']) === true &&
            is_array($data['_headers']) === true) {
            foreach ($data['_headers'] as $key => $value) {
                //@note this doesn't work for raw headers!
                $headers->set($key, $value);
            }
        }

        return $headers;
    }
}
