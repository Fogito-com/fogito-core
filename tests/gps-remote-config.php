<?php
// No network calls: exercise the real request assembly with in-memory cURL peers.
namespace Fogito {
    class App { public static $di; }
    class Exception extends \Exception {}
}
namespace Fogito\Http {
    class Request {
        public static $mode = 'production';
        public static $token = null;
        public static function envMode() { return self::$mode; }
        public static function isDevMode() { return self::$mode === 'development'; }
        public static function getServer($name) { return ''; }
        public static function get($name) { return self::$token; }
    }
    class Response {
        public static function error($description, $code) { throw new \RuntimeException($description, $code); }
    }
}
namespace Fogito\Lib {
    class Auth {
        public static function getData() { return false; }
        public static function getId() { return ''; }
        public static function getToken() { return null; }
        public static function getTokenUser() { return null; }
        public static function tokenAllowed() { return true; }
    }
    class Lang { public static function getLang() { return 'da'; } }
}
namespace Fogito\Db {
    function curl_init($url) { $GLOBALS['gpsCurl'] = ['url' => $url, 'options' => [], 'closed' => false]; return 1; }
    function curl_setopt($handle, $key, $value) { $GLOBALS['gpsCurl']['options'][$key] = $value; return true; }
    function curl_exec($handle) { return $GLOBALS['gpsResponse']; }
    function curl_errno($handle) { return $GLOBALS['gpsErrno']; }
    function curl_close($handle) { $GLOBALS['gpsCurl']['closed'] = true; }
}
namespace {
    error_reporting(E_ALL);
    set_error_handler(function ($severity, $message, $file, $line) {
        throw new \ErrorException($message, 0, $severity, $file, $line);
    });
    require __DIR__ . '/../src/Config.php';
    require __DIR__ . '/../src/Db/RemoteModelManager.php';
    class GpsRemoteFixture extends \Fogito\Db\RemoteModelManager {
        public static function getServer() { return \Fogito\Config::getUrl('core'); }
        public static function getSource() { return 'fixture'; }
    }
    $checks = 0;
    function same($actual, $expected, $label) {
        global $checks;
        if ($actual !== $expected) throw new \RuntimeException($label);
        $checks++;
    }
    function sent() { return json_decode($GLOBALS['gpsCurl']['options'][CURLOPT_POSTFIELDS], true); }
    \Fogito\App::$di = (object)['config' => (object)['s2s' => null]];
    $GLOBALS['gpsErrno'] = 0;
    $GLOBALS['gpsResponse'] = '{"status":"success","data":[{"id":"fixture"}]}';

    foreach (['production' => 'app', 'development' => 'dev', 'beta' => 'beta'] as $mode => $domain) {
        \Fogito\Http\Request::$mode = $mode;
        same(\Fogito\Config::getUrl('core'), 'https://' . $domain . '.fogito.com/api/core', 'HTTPS default and environment routing');
        same(\Fogito\Config::getUrl('core', false), 'https://' . $domain . '.fogito.com/api/core', 'optional URL settings');
        same(\Fogito\Config::getUrl('core', ['protocol' => 'http']), 'http://' . $domain . '.fogito.com/api/core', 'explicit legacy HTTP request');
        same(GpsRemoteFixture::find(['filter' => ['active' => 1]]), [['id' => 'fixture']], 'default result shape');
        same($GLOBALS['gpsCurl']['options'][CURLOPT_SSL_VERIFYHOST], 2, 'TLS hostname validation');
        same($GLOBALS['gpsCurl']['options'][CURLOPT_SSL_VERIFYPEER], true, 'TLS certificate validation');
        same(in_array('Env-Mode: ' . $mode, $GLOBALS['gpsCurl']['options'][CURLOPT_HTTPHEADER], true), true, 'environment header');
        same($GLOBALS['gpsCurl']['closed'], true, 'request handle closed');
    }
    $unknownRejected = false;
    try { \Fogito\Config::getUrl('unknown-fixture'); } catch (\Fogito\Exception $error) { $unknownRejected = true; }
    same($unknownRejected, true, 'unknown service rejected');
    same(count(new \Fogito\Config(['a' => 1, 'b' => 2])), 2, 'config remains countable');

    foreach (['find', 'findFirst', 'count'] as $method) {
        GpsRemoteFixture::$method(['filter' => ['id' => 7], 'limit' => 10]);
        same(sent()['data'], ['filter' => ['id' => 7], 'limit' => 10], 'named filter retained');
        GpsRemoteFixture::$method([['id' => 8], 'sort' => ['id' => 1], 'limit' => 5]);
        $expected = ['sort' => ['id' => 1], 'limit' => 5, 'filter' => ['id' => 8]];
        if ($method === 'count') $expected = [0 => ['id' => 8]] + $expected;
        same(sent()['data'], $expected, 'legacy positional filter and options retained');
        GpsRemoteFixture::$method([false, 'filter' => ['id' => 9]]);
        same(sent()['data']['filter'], ['id' => 9], 'false positional filter does not erase named scope');
        GpsRemoteFixture::$method(false);
        same(sent()['data'], [], 'false optional filter accepted');
    }
    \Fogito\Http\Request::$token = ['invalid'];
    GpsRemoteFixture::find([]);
    same(isset(sent()['remote_server_token']), false, 'non-scalar server token ignored');
    \Fogito\Http\Request::$token = 'fixture-token';
    GpsRemoteFixture::find([]);
    same(sent()['remote_server_token'], 'fixture-token', 'valid server token forwarded');

    $GLOBALS['gpsResponse'] = '{"status":"success"}';
    same(GpsRemoteFixture::find([]), null, 'optional response data');
    same(GpsRemoteFixture::find([], ['result' => true]), ['status' => 'success'], 'complete response option');
    foreach ([false, null, '', 'null', 'false', '42', '{bad'] as $invalid) {
        $GLOBALS['gpsResponse'] = $invalid;
        same(GpsRemoteFixture::find([]), false, 'invalid response fails closed');
        same(GpsRemoteFixture::find([], ['result' => true])['error_code'], 1004, 'invalid response error shape');
    }
    $GLOBALS['gpsResponse'] = '{"status":"error"}';
    $debugRejected = false;
    try { GpsRemoteFixture::find([], ['debug' => true]); }
    catch (\RuntimeException $error) { $debugRejected = $error->getCode() === 1004; }
    same($debugRejected, true, 'missing remote error fields get safe defaults');
    $GLOBALS['gpsErrno'] = 60;
    same(GpsRemoteFixture::find([], ['result' => true])['error_code'], 1003, 'TLS failure retains connection error contract');
    same($GLOBALS['gpsCurl']['closed'], true, 'failed request handle closed');
    echo 'PASS: ' . $checks . " remote/config checks\n";
}
