<?php
namespace Lib;

class Response extends \Fogito\Http\Response
{
    const KEY_STATUS  = 'status';
    const KEY_CODE    = 'code';
    const KEY_MESSAGE = 'message';
    const KEY_DATA    = 'data';
    const KEY_COUNT   = 'count';

    const STATUS_SUCCESS = 'success';
    const STATUS_ERROR   = 'error';

    const CODE_SUCCESS = 100;
    const CODE_ERROR   = 1003;
    
    /**
     * send
     *
     * @return void
     */
    public static function send()
    {
        // set headers before sent
        self::setContentType('application/json', 'utf-8');
        self::setHeader('Access-Control-Allow-Headers', 'Content-Type, Accept');
        self::setHeader('Access-Control-Allow-Methods', 'GET, POST');
        self::setHeader('Access-Control-Allow-Credentials', 'true');

        // output
        parent::send();
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
            // set custom error code
            $code = self::CODE_ERROR;
        }

        // output
        parent::error($message, $code, $details);
    }

    /**
     * success
     *
     * @param  mixed $message
     * @param  mixed $data
     * @param  mixed $count
     * @return void
     */
    public static function success($message = null, $data = [], $count = null)
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
