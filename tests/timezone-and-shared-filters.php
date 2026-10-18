<?php
declare(strict_types=1);

namespace Fogito {
    class App { public static $di; }
}
namespace Fogito\Lib {
    class Company {
        public static $data;
        public static function getData() { return self::$data; }
        public static function getId() { return 'current-company'; }
    }
}
namespace Fogito\Db { class RemoteModelManager {} }
namespace {
    error_reporting(E_ALL);
    set_error_handler(function ($level, $message, $file, $line) {
        throw new ErrorException($message, 0, $level, $file, $line);
    });
    date_default_timezone_set('UTC');
    require dirname(__DIR__) . '/src/Db/ModelManager.php';
    require dirname(__DIR__) . '/src/Models/CoreTimezones.php';

    $checks = 0;
    $assert = function ($condition, $message) use (&$checks) {
        ++$checks;
        if (!$condition) throw new RuntimeException($message);
    };
    class EmptyCollectionList { public function toArray() { return []; } }
    \Fogito\App::$di = (object)['config' => (object)[
        'skipped_filtering_collections' => new EmptyCollectionList(),
        'skip_filter_business_type' => false, 'skip_filter_company_id' => false,
    ]];
    define('BUSINESS_TYPE', 1);
    define('COMPANY_ID', 'current-company');
    class SharedFilterProbe extends \Fogito\Db\ModelManager {
        protected static $_shared = true;
        public static function setSourceForTest() { self::$_source = 'fixture'; }
    }
    class PrivateFilterProbe extends \Fogito\Db\ModelManager {}
    SharedFilterProbe::setSourceForTest();
    \Fogito\Lib\Company::$data = (object)['branch_ids' => ['branch-company']];
    foreach ([[], ['company_ids' => null], ['company_ids' => []]] as $filter) {
        $result = SharedFilterProbe::filterBinds($filter);
        $assert($result['company_ids'] === ['$in' => ['branch-company', 'current-company']], 'Missing/empty shared scope retains branch/current-company authorization');
        $assert($result['business_type'] === 1, 'Business scope retained');
    }
    $explicit = ['company_ids' => ['$in' => ['allowed-company']], 'company_id' => 'exact-tenant'];
    $result = SharedFilterProbe::filterBinds($explicit);
    $assert($result['company_ids'] === $explicit['company_ids'] && $result['company_id'] === 'exact-tenant', 'Explicit scope is not broadened');
    \Fogito\Lib\Company::$data = (object)[];
    $assert(SharedFilterProbe::filterBinds([])['company_ids'] === ['$in' => ['current-company']], 'Missing branch list still scopes to current company');
    $assert(PrivateFilterProbe::filterBinds([])['company_id'] === 'current-company', 'Non-shared model scoping unchanged');

    \Fogito\Models\CoreTimezones::$data = [
        100 => ['id' => 100, 'slug' => 'Atlantic/Reykjavik'],
        101 => ['id' => 101, 'slug' => 'Asia/Baku'],
    ];
    $date = '2026-10-19 12:00:00';
    $assert(\Fogito\Models\CoreTimezones::date($date) === '2026-10-19 16:00:00', 'Empty options use default source and user zones without warnings');
    $assert(\Fogito\Models\CoreTimezones::date($date, 'H:i', ['tzfrom' => 101, 'tzto' => 100]) === '08:00', 'Explicit source/target conversion preserved');
    $assert(\Fogito\Models\CoreTimezones::date($date, false, ['tzfrom' => 999, 'tzto' => 999]) === '2026-10-19 16:00:00', 'Unknown IDs use existing default fallback without undefined keys');
    $assert(\Fogito\Models\CoreTimezones::date(strtotime($date)) === '2026-10-19 16:00:00', 'Numeric input remains supported');
    $mongoLike = new class {
        public function toDateTime() { return new DateTimeImmutable('2026-10-19T12:00:00Z'); }
    };
    $assert(\Fogito\Models\CoreTimezones::date($mongoLike) === '2026-10-19 16:00:00', 'Driver-like dates remain supported');
    $assert(\Fogito\Models\CoreTimezones::date($date, 'unix') === strtotime('2026-10-19 16:00:00'), 'Legacy unix return semantics preserved');
    $assert(\Fogito\Models\CoreTimezones::getById(999) === false, 'Unknown timezone lookup returns false');
    unset(\Fogito\Models\CoreTimezones::$data[100]);
    try {
        \Fogito\Models\CoreTimezones::date($date);
        $assert(false, 'Missing default must fail explicitly');
    } catch (InvalidArgumentException $exception) {
        $assert($exception->getMessage() === 'Default timezone is unavailable', 'Missing default fails without warning cascade');
    }
    echo "Timezone/shared-filter regression checks passed: $checks assertions\n";
}
