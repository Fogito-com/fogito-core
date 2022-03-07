<?php
namespace Lib;

use Lib\App;
use Models\LogsSms;

class Sms
{
    public static $requestData;

    /**
     * solo
     *
     * @param  mixed $phone
     * @param  mixed $message
     * @param  mixed $operator
     * @return void
     */
    public static function solo($phone, $message, $operator = 1)
    {
        if (ENV == 'development') {
            // development response
            return [
                'status'     => 'success',
                'message_id' => (string) Helpers::randomNumber(7),
                'hash'       => (string) \md5(\microtime(true)),
            ];
        }

        $params = [];
        foreach (App::$di->config->sms->credentials as $key => $value) {
            $params[$key] = strtr($value, [
                '%operator%' => $operator,
                '%phone%'    => $phone,
                '%message%'  => $message,
            ]);
        }

        $url = rtrim(App::$di->config->sms->url, '/') . '?' . \http_build_query($params);

        $ch = \curl_init();
        \curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_USERAGENT      => $_SERVER['HTTP_USER_AGENT'],
            CURLOPT_HEADER         => false,
        ]);

        $response = \curl_exec($ch);
        $error    = \curl_error($ch);

        $output = [];
        if (!$error) {
            \parse_str((string) $response, $output);
        }

        self::$requestData = [
            'url'      => $url,
            'params'   => $params,
            'response' => $response,
            'output'   => $output,
        ];

        $hash = \md5(\sha1($phone . $message . \microtime(true) . '^%$#'));

        $sl           = new LogsSms();
        $sl->operator = (int) $operator;
        $sl->phone    = (string) $phone;
        $sl->text     = (string) $message;
        $sl->response = (array) $output;
        if ($output['balance']) {
            $sl->limit = (int) $output['balance'];
        }
        $sl->hash = (string) $hash;
        $sl->save();

        return [
            'status'     => $output['errno'] == '100' ? 'success' : 'error',
            'message_id' => (string) $output['message_id'],
            'hash'       => (string) $hash,
        ];
    }
}
