<?php
// Isolated regression checks: no application bootstrap, network or real cache.
namespace Fogito {
    class Exception extends \Exception {}
}

namespace Fogito\Http {
    class Response
    {
        public static function error($message)
        {
            throw new \RuntimeException('Permission denied: ' . $message);
        }
    }
}

namespace Fogito\Lib {
    class Cache
    {
        public static $value = false;
        public static $reads = [];
        public static $writes = [];
        public static function get($key) { self::$reads[] = $key; return self::$value; }
        public static function set($key, $value, $ttl)
        {
            self::$writes[] = [$key, $value, $ttl];
            self::$value = $value;
            return true;
        }
    }
    class Lang
    {
        public static $lang = 'en';
        public static function setLang($lang) { self::$lang = $lang; }
        public static function getLang() { return self::$lang; }
        public static function setData($data) {}
        public static function get($key, $fallback = null) { return $fallback ?: $key; }
    }
    class Company
    {
        public static $data;
        public static function setData($data) { self::$data = (object)$data; }
        public static function getData() { return self::$data; }
        public static function getId() { return self::$data->id ?? null; }
    }
}

namespace Fogito\Models {
    class CoreSettings
    {
        public static $response;
        public static $requests = [];
        public static $data;
        public static function getServer() { return 'https://settings.invalid'; }
        public static function curl($url, $data)
        {
            self::$requests[] = [$url, $data];
            return self::$response;
        }
        public static function setData($data) { self::$data = $data; }
    }
}

namespace {
    use Fogito\Http\Request;
    use Fogito\Lib\Auth;
    use Fogito\Lib\Cache;
    use Fogito\Lib\Company;
    use Fogito\Lib\Lang;
    use Fogito\Models\CoreSettings;

    require __DIR__ . '/../src/Http/Request.php';
    require __DIR__ . '/../src/Lib/Auth.php';

    error_reporting(E_ALL);
    set_error_handler(function ($severity, $message, $file, $line) {
        throw new \ErrorException($message, 0, $severity, $file, $line);
    });

    $checks = 0;
    function gpsAssertSame($expected, $actual, $label)
    {
        global $checks;
        ++$checks;
        if ($expected !== $actual)
            throw new \RuntimeException($label . ': unexpected result');
    }
    function gpsSetStatic($class, $name, $value)
    {
        $property = new \ReflectionProperty($class, $name);
        $property->setAccessible(true);
        $property->setValue(null, $value);
    }
    function gpsRequest($request = [], $json = '{}', $argv = [])
    {
        $_REQUEST = $request;
        $_COOKIE = [];
        $_FILES = [];
        $_SERVER = ['argv' => $argv, 'HTTP_ORIGIN' => 'https://gps.invalid'];
        gpsSetStatic(Request::class, 'rawBody', $json);
    }
    function gpsSuccess()
    {
        return ['status' => 'success', 'data' => [
            'account' => ['id' => 'fixture-user', 'type' => 'employee', 'level' => 1],
            'company' => ['id' => 'fixture-company', 'business_model' => 8],
            'permissions' => ['reports' => ['allow' => true, 'selected' => ['101']]],
            'pricing' => ['plan' => 'fixture'],
            'translations' => ['fixture' => 'value'],
        ]];
    }
    function gpsAuthReset($cache = false, $remote = null)
    {
        gpsRequest(['token' => 'fixture-token', 'token_user' => 'fixture-user', 'lang' => 'da']);
        foreach (['_data' => null, '_permissions' => [], '_pricing' => [], '_error' => false] as $name => $value)
            gpsSetStatic(Auth::class, $name, $value);
        Auth::setCacheDuration(30);
        Cache::$value = $cache;
        Cache::$reads = Cache::$writes = [];
        Company::$data = null;
        Lang::$lang = 'en';
        CoreSettings::$requests = [];
        CoreSettings::$response = $remote === null ? json_encode(gpsSuccess()) : $remote;
    }

    foreach (['native array' => gpsSuccess(), 'JSON' => json_encode(gpsSuccess())] as $format => $cached) {
        gpsAuthReset($cached);
        Auth::setCacheDuration(45);
        Auth::init();
        gpsAssertSame('fixture-user', Auth::getId(), $format . ' cache authenticates');
        gpsAssertSame(0, count(CoreSettings::$requests), $format . ' cache avoids network');
        gpsAssertSame(45, Cache::$writes[0][2], $format . ' keeps configured TTL');
        gpsAssertSame(Cache::$reads[0], Cache::$writes[0][0], $format . ' keeps cache key');
        gpsAssertSame(gpsSuccess(), json_decode(Cache::$writes[0][1], true), $format . ' writes JSON cache');
        gpsAssertSame(false, Auth::getError(), $format . ' cache has no auth error');
        gpsAssertSame('da', Lang::getLang(), $format . ' respects request language');
    }

    foreach ([false, '{broken', 'true', '25', 'null', '"text"', [], ['unexpected' => true]] as $cached) {
        gpsAuthReset($cached);
        Auth::init();
        gpsAssertSame(1, count(CoreSettings::$requests), 'missing/malformed cache refetches once');
        gpsAssertSame('fixture-user', Auth::getId(), 'missing/malformed cache recovers');
        gpsAssertSame(30, Cache::$writes[0][2], 'refetched success keeps default TTL');
    }

    foreach ([null, false, 25, 'invalid-data', []] as $data) {
        $malformed = ['status' => 'success', 'data' => $data];
        foreach ([$malformed, json_encode($malformed)] as $cached) {
            gpsAuthReset($cached);
            Auth::init();
            gpsAssertSame(1, count(CoreSettings::$requests), 'malformed successful cache refetches once');
            gpsAssertSame('fixture-user', Auth::getId(), 'malformed successful cache recovers');
        }
        gpsAuthReset(false, json_encode($malformed));
        Auth::init();
        gpsAssertSame(null, Auth::getData(), 'malformed successful remote response stays unauthenticated');
        gpsAssertSame(1000, Auth::getError()['code'], 'malformed successful response returns connection error');
    }

    $denied = ['status' => 'error', 'error_code' => 1001, 'description' => 'Expired fixture'];
    gpsAuthReset(false, json_encode($denied));
    Auth::init();
    gpsAssertSame(null, Auth::getData(), 'failed lookup stays unauthenticated');
    gpsAssertSame(['code' => 1001, 'description' => 'Expired fixture'], Auth::getError(), 'failed lookup preserves error');
    gpsAssertSame(4, Cache::$writes[0][2], 'failed lookup keeps short error TTL');
    foreach ([$denied, json_encode($denied)] as $cached) {
        gpsAuthReset($cached);
        Auth::init();
        gpsAssertSame(0, count(CoreSettings::$requests), 'cached denial does not refetch');
        gpsAssertSame(null, Auth::getData(), 'cached denial stays unauthenticated');
        gpsAssertSame(4, Cache::$writes[0][2], 'cached denial keeps error TTL');
    }
    foreach (['not-json', 'false', '123', 'null'] as $remote) {
        gpsAuthReset(false, $remote);
        Auth::init();
        gpsAssertSame(null, Auth::getData(), 'invalid remote response stays unauthenticated');
        gpsAssertSame(1000, Auth::getError()['code'], 'invalid remote response is connection error');
        gpsAssertSame([], Cache::$writes, 'invalid remote response is not cached');
    }

    $permissions = [
        'selected' => ['allow' => true, 'selected' => ['101', '102']],
        'unrestricted' => ['allow' => true],
        'null-selected' => ['allow' => true, 'selected' => null],
        'denied' => ['allow' => false, 'selected' => ['101']],
    ];
    Auth::setPermissions($permissions);
    gpsAssertSame(true, Auth::checkPermission('selected', 101), 'numeric selection remains compatible');
    gpsAssertSame(false, Auth::checkPermission('selected', '999'), 'different selection denied');
    gpsAssertSame($permissions['selected'], Auth::checkPermission('selected'), 'selection metadata preserved');
    gpsAssertSame(true, Auth::checkPermission('unrestricted'), 'unrestricted permission allowed');
    gpsAssertSame(false, Auth::checkPermission('unrestricted', '101'), 'missing selected list denies selected lookup');
    gpsAssertSame(false, Auth::checkPermission('null-selected', '101'), 'null selected list denies selected lookup');
    gpsAssertSame(false, Auth::checkPermission('missing'), 'missing permission denied');
    gpsAssertSame(false, Auth::checkPermission('denied', '101'), 'disabled permission denied');
    gpsAssertSame($permissions['selected'], Auth::isAllowed('selected', '101'), 'isAllowed selection succeeds');
    gpsAssertSame(false, Auth::isAllowed('selected', '999'), 'isAllowed selection mismatch denied');
    gpsAssertSame(false, Auth::isAllowed('missing', '101'), 'isAllowed missing permission denied');
    gpsAssertSame(false, Auth::isAllowed('null-selected', '101'), 'isAllowed null selected list denied');
    gpsAssertSame(true, Auth::validatePermission('selected', '101'), 'validation delegates without recursion');
    $didDeny = false;
    try { Auth::validatePermission('selected', '999'); }
    catch (\RuntimeException $error) { $didDeny = strpos($error->getMessage(), 'Permission denied:') === 0; }
    gpsAssertSame(true, $didDeny, 'validation rejects disallowed selection');

    gpsRequest(['source' => 'request', 'keep' => 'original'], '{"source":"json","count":2}');
    gpsAssertSame(['source' => 'json', 'keep' => 'original', 'count' => 2], Request::get(), 'JSON overrides request parameters');
    gpsRequest([], '["one","two"]');
    gpsAssertSame(['one', 'two'], Request::get(), 'JSON list remains supported');
    foreach (['false', 'true', '42', 'null', '"scalar"', '{broken'] as $body) {
        gpsRequest(['keep' => 'value'], $body);
        gpsAssertSame(['keep' => 'value'], Request::get(), 'scalar/invalid JSON is ignored without warnings');
    }
    gpsRequest(['source' => 'request'], '{"source":"json"}', ['script.php', 'source=cli', 'signed=value=with=equals', 'empty=']);
    gpsAssertSame('cli', Request::get('source'), 'CLI retains highest precedence');
    gpsAssertSame('value=with=equals', Request::get('signed'), 'CLI preserves equals in values');
    gpsAssertSame('', Request::get('empty'), 'CLI supports empty values');
    gpsAssertSame(false, array_key_exists('script.php', Request::get()), 'CLI ignores arguments without assignment');
    gpsAssertSame('fallback', Request::get('absent', null, 'fallback'), 'missing field preserves default');
    gpsRequest();
    unset($_SERVER['argv']);
    gpsSetStatic(Request::class, 'rawBody', null);
    gpsAssertSame([], Request::get(), 'missing method and argv cause no warnings');
    gpsAssertSame('', Request::getClientAddress(), 'missing remote address causes no warning');
    gpsAssertSame('', Request::getIP(), 'missing IP causes no warning');
    gpsAssertSame('production', Request::envMode(), 'missing host defaults to production');
    gpsAssertSame(false, Request::isDevMode(), 'missing host is not development');
    $_SERVER['HTTP_ENV_MODE'] = 'development';
    gpsAssertSame('development', Request::envMode(), 'explicit development mode preserved');
    gpsAssertSame(true, Request::isDevMode(), 'explicit development mode detected');
    $_SERVER['HTTP_ENV_MODE'] = 'onpremise';
    gpsAssertSame('production', Request::envMode(), 'unknown explicit mode keeps baseline production fallback');
    unset($_SERVER['HTTP_ENV_MODE']);
    $_SERVER['HTTP_HOST'] = 'beta.example.invalid';
    gpsAssertSame('beta', Request::envMode(), 'beta host mode preserved');
    $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'da-DK;q=0.7, en;q=0.9, de;q=0.4';
    gpsAssertSame('en', Request::getBestLanguage(), 'weighted language header picks highest quality');
    unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
    gpsAssertSame([], Request::getLanguages(), 'missing language header is empty');
    gpsAssertSame(null, Request::getBestLanguage(), 'missing language has no best choice');
    $_FILES = ['single' => ['error' => UPLOAD_ERR_OK], 'failed' => ['error' => UPLOAD_ERR_NO_FILE]];
    gpsAssertSame(1, Request::hasFiles(), 'single-file error fields supported');

    restore_error_handler();
    echo 'PASS: ' . $checks . " isolated Auth/Request checks\n";
}
