<?php
// Offline checks: actual model, user and route classes; no database or HTTP calls.
namespace Fogito {
    class App
    {
        public static $di;
    }
}

namespace Fogito\Db {
    class RemoteModelManager
    {
        public static $findCalls = [];
        public static $findRows = [];

        public static function find($parameters = [])
        {
            self::$findCalls[] = $parameters;
            return self::$findRows;
        }
    }
}

namespace {
    error_reporting(E_ALL & ~E_DEPRECATED);
    set_error_handler(function ($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) return false;
        throw new \ErrorException($message, 0, $severity, $file, $line);
    });

    $root = dirname(__DIR__);
    require $root . '/src/Exception.php';
    require $root . '/src/Lib/Company.php';
    require $root . '/src/Db/ModelManager.php';
    require $root . '/src/Models/CoreUsers.php';
    require $root . '/src/Text.php';
    require $root . '/src/Router/Route.php';

    class GpsSharedModelFixture extends \Fogito\Db\ModelManager
    {
        protected static $_shared = true;
        public static $findFilters = [];
        public static $insertFilters = [];
        public static $findFilterCalls = 0;
        public static $insertFilterCalls = 0;

        public static function getFindFilters()
        {
            self::$findFilterCalls++;
            return self::$findFilters;
        }

        public static function getInsertFilters()
        {
            self::$insertFilterCalls++;
            return self::$insertFilters;
        }
    }

    class GpsPrivateModelFixture extends \Fogito\Db\ModelManager
    {
        protected static $_shared = false;
    }

    class GpsSkippedCollectionsFixture
    {
        private $values;

        public function __construct($values)
        {
            $this->values = $values;
        }

        public function toArray()
        {
            return $this->values;
        }
    }

    $checks = 0;
    function same($expected, $actual, $label)
    {
        global $checks;
        $checks++;
        if ($expected !== $actual) {
            throw new \RuntimeException($label . ': expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
        }
    }

    define('BUSINESS_TYPE', 8);
    define('COMPANY_ID', 'company-current');
    \Fogito\App::$di = (object)[
        'config' => (object)[
            'skipped_filtering_collections' => null,
            'skip_filter_business_type' => false,
            'skip_filter_company_id' => false,
        ],
    ];
    \Fogito\Lib\Company::setData(['id' => COMPANY_ID, 'branch_ids' => ['branch-one'], 'business_model' => BUSINESS_TYPE]);
    GpsSharedModelFixture::setSource('gps_fixture');

    $emptyValues = [null, false, [], '', 0];
    foreach (array_merge([['missing' => true]], array_map(function ($value) {
        return ['company_ids' => $value];
    }, $emptyValues)) as $filter) {
        $actual = GpsSharedModelFixture::filterBinds($filter);
        same(['$in' => ['branch-one', COMPANY_ID]], $actual['company_ids'], 'Empty shared read scope receives current company and branches');
        same(BUSINESS_TYPE, $actual['business_type'], 'Read business scope remains active');
        $actual = GpsSharedModelFixture::filterInsertBinds($filter);
        same([COMPANY_ID], $actual['company_ids'], 'Empty shared insert scope receives current company');
        same(COMPANY_ID, $actual['company_id'], 'Insert company scope remains active');
    }

    $explicitScope = ['$in' => ['explicit-company']];
    same($explicitScope, GpsSharedModelFixture::filterBinds(['company_ids' => $explicitScope])['company_ids'], 'Explicit read scope preserved');
    same($explicitScope, GpsSharedModelFixture::filterInsertBinds(['company_ids' => $explicitScope])['company_ids'], 'Explicit insert scope preserved');
    same(99, GpsSharedModelFixture::filterBinds(['business_type' => 99])['business_type'], 'Explicit business scope preserved');

    foreach ([null, false, []] as $extra) {
        GpsSharedModelFixture::$findFilters = $extra;
        GpsSharedModelFixture::$insertFilters = $extra;
        GpsSharedModelFixture::$findFilterCalls = 0;
        GpsSharedModelFixture::$insertFilterCalls = 0;
        same(['$in' => ['branch-one', COMPANY_ID]], GpsSharedModelFixture::filterBinds([])['company_ids'], 'Empty optional find filters preserve scope');
        same([COMPANY_ID], GpsSharedModelFixture::filterInsertBinds([])['company_ids'], 'Empty optional insert filters preserve scope');
        same(1, GpsSharedModelFixture::$findFilterCalls, 'Find filters evaluated once');
        same(1, GpsSharedModelFixture::$insertFilterCalls, 'Insert filters evaluated once');
    }
    GpsSharedModelFixture::$findFilters = ['active' => true, 'priority' => 'default'];
    GpsSharedModelFixture::$insertFilters = ['active' => true, 'priority' => 'default'];
    foreach (['filterBinds', 'filterInsertBinds'] as $method) {
        $actual = GpsSharedModelFixture::$method(['priority' => 'caller']);
        same(true, $actual['active'], 'Optional model filters merge');
        same('caller', $actual['priority'], 'Caller filter precedence preserved');
    }
    GpsSharedModelFixture::$findFilters = [];
    GpsSharedModelFixture::$insertFilters = [];

    foreach ([null, false, [], new GpsSkippedCollectionsFixture([])] as $skipped) {
        \Fogito\App::$di->config->skipped_filtering_collections = $skipped;
        same(['$in' => ['branch-one', COMPANY_ID]], GpsSharedModelFixture::filterBinds([])['company_ids'], 'Optional skipped collection configuration');
    }
    foreach ([['gps_fixture'], new GpsSkippedCollectionsFixture(['gps_fixture'])] as $skipped) {
        \Fogito\App::$di->config->skipped_filtering_collections = $skipped;
        same(['unchanged' => true], GpsSharedModelFixture::filterBinds(['unchanged' => true]), 'Configured skipped collection preserved');
    }
    \Fogito\App::$di->config->skipped_filtering_collections = [];
    foreach ([[], ['branch_ids' => null], ['branch_ids' => false]] as $properties) {
        \Fogito\Lib\Company::setData(array_merge(['id' => COMPANY_ID], $properties));
        same(['$in' => [COMPANY_ID]], GpsSharedModelFixture::filterBinds([])['company_ids'], 'Missing or empty company branches');
    }
    \Fogito\Lib\Company::setData(null);
    same([], GpsSharedModelFixture::filterInsertBinds([]), 'No authenticated company introduces no invented insert scope');
    \Fogito\Lib\Company::setData(['id' => COMPANY_ID, 'business_model' => BUSINESS_TYPE]);
    same(COMPANY_ID, GpsPrivateModelFixture::filterBinds([])['company_id'], 'Private model uses current company');
    same('explicit-company', GpsPrivateModelFixture::filterBinds(['company_id' => 'explicit-company'])['company_id'], 'Private model explicit scope preserved');
    \Fogito\App::$di->config->skip_filter_company_id = true;
    same(false, isset(GpsPrivateModelFixture::filterBinds([])['company_id']), 'Configured private-company bypass preserved');
    \Fogito\App::$di->config->skip_filter_company_id = false;

    if (!class_exists('MongoDB\\BSON\\UTCDateTime') || !class_exists('MongoDB\\BSON\\ObjectID')) {
        throw new \RuntimeException('The native MongoDB extension is required; no database connection is made.');
    }
    same('1700000000000', (string)GpsSharedModelFixture::getDate(1700000000), 'Integer seconds become milliseconds');
    same('1700000000125', (string)GpsSharedModelFixture::getDate(1700000000.125), 'Fractional seconds preserve milliseconds');
    same('1700000000125', (string)GpsSharedModelFixture::getDate(1700000000125, false), 'Millisecond input remains unchanged');
    same('1700000000125', (string)GpsSharedModelFixture::getDate('1700000000125', false), 'Numeric millisecond strings remain supported');
    same('1700000000000', (string)GpsSharedModelFixture::getDate(1700000000000.75, false), 'Explicit integer conversion preserves truncation');
    $before = (int)round(microtime(true) * 1000);
    $now = (int)(string)GpsSharedModelFixture::getDate();
    $after = (int)round(microtime(true) * 1000);
    same(true, $now >= $before && $now <= $after, 'Default date uses current time');
    $id = '0123456789abcdef01234567';
    same($id, (string)GpsSharedModelFixture::objectId($id), 'Mongo ID string contract unchanged');
    $objectId = new \MongoDB\BSON\ObjectID($id);
    same($objectId, GpsSharedModelFixture::objectId($objectId), 'Mongo ID object identity unchanged');
    same(false, GpsSharedModelFixture::objectId('abc'), 'Short Mongo ID keeps false return');
    same(true, GpsSharedModelFixture::isMongoId($id), 'Mongo ID validation unchanged');
    same(false, GpsSharedModelFixture::isMongoId('invalid-id'), 'Invalid Mongo ID validation unchanged');

    $user = ['id' => 'user-one', 'fullname' => 'Fixture User', 'avatar_tiny' => ''];
    \Fogito\Db\RemoteModelManager::$findRows = [$user, ['invalid' => true]];
    foreach ([[], ['columns' => null], ['columns' => false], ['columns' => []]] as $options) {
        \Fogito\Db\RemoteModelManager::$findCalls = [];
        $result = \Fogito\Models\CoreUsers::findAndSet([['user' => 'user-one'], ['user' => 'missing-user'], ['unchanged' => true]], $options);
        same(['id', 'fullname', 'avatar_tiny'], \Fogito\Db\RemoteModelManager::$findCalls[0]['columns'], 'User columns use existing defaults');
        same($user, $result[0]['user'], 'Existing user attaches');
        same(false, $result[1]['user'], 'Unmatched user keeps false result');
        same(false, $result[2]['user'], 'Missing user identifier yields false without warning');
    }
    \Fogito\Db\RemoteModelManager::$findCalls = [];
    $result = \Fogito\Models\CoreUsers::findAndSet([['actor' => 'user-one']], ['key_from' => 'actor', 'key_to' => 'profile', 'columns' => ['id']]);
    same(['id'], \Fogito\Db\RemoteModelManager::$findCalls[0]['columns'], 'Custom user columns preserved');
    same($user, $result[0]['profile'], 'Custom user destination preserved');
    same('user-one', $result[0]['actor'], 'Custom user source preserved');
    \Fogito\Db\RemoteModelManager::$findCalls = [];
    same([], \Fogito\Models\CoreUsers::findAndSet([]), 'Empty user list remains empty');
    same([], \Fogito\Db\RemoteModelManager::$findCalls, 'Empty user list does not request data');
    same([['position' => '']], \Fogito\Models\CoreUsers::mergePositions([[]]), 'Missing positions yield empty label');
    same([['positions' => [['title' => 'Driver']], 'position' => 'Driver']], \Fogito\Models\CoreUsers::mergePositions([['positions' => [['title' => 'Driver']]]]), 'Existing position labels preserved');

    foreach ([
        ['gps', ['module' => 'gps']],
        ['gps::History', ['module' => 'gps', 'controller' => 'history']],
        ['gps::History::list', ['module' => 'gps', 'controller' => 'history', 'action' => 'list']],
        [['module' => 'gps', 'controller' => 'history', 'action' => 'list'], ['module' => 'gps', 'controller' => 'history', 'action' => 'list']],
    ] as $case) {
        $route = new \Fogito\Router\Route('/history', $case[0], 'GET');
        same($case[1], $route->getPaths(), 'Existing route path contract preserved');
    }
    $route = new \Fogito\Router\Route('/history', 'gps::Example\\Controllers\\History::list', 'GET');
    same(['module' => 'gps', 'namespace' => 'Example\\Controllers', 'controller' => 'history', 'action' => 'list'], $route->getPaths(), 'Namespaced controller path is parsed as a string');

    echo 'GPS model compatibility: ' . $checks . " checks passed\n";
}
