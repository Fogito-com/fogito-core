<?php
declare(strict_types=1);

set_error_handler(static function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

require dirname(__DIR__).'/src/Exception.php';
require dirname(__DIR__).'/src/Text.php';
require dirname(__DIR__).'/src/Filter.php';
require dirname(__DIR__).'/src/Http/Request.php';
require dirname(__DIR__).'/src/Http/Response.php';
require dirname(__DIR__).'/src/App.php';
require dirname(__DIR__).'/src/Config.php';
require dirname(__DIR__).'/src/Lib/Company.php';
require dirname(__DIR__).'/src/Lib/Cache.php';
require dirname(__DIR__).'/src/Db/ModelManager.php';
require dirname(__DIR__).'/src/Db/RemoteModelManager.php';
require dirname(__DIR__).'/src/Models/CoreUsers.php';
require dirname(__DIR__).'/src/Models/CoreCompanies.php';
require dirname(__DIR__).'/src/Models/CoreTimezones.php';
require dirname(__DIR__).'/src/Router/Route.php';
require dirname(__DIR__).'/src/Router.php';

function warningCheck($condition, $message)
{
    if (!$condition)
        throw new RuntimeException($message);
}

$_REQUEST = [];
$_GET = [];
$_POST = [];
$_FILES = [];
$_SERVER = array('argv' => array('script.php', 'flag-without-value'));

warningCheck(Fogito\Http\Request::get() === [], 'CLI arguments without values must be ignored');
warningCheck(Fogito\Http\Request::getRawBody() === '', 'Missing request method must be safe');
warningCheck(Fogito\Http\Request::getClientAddress() === '', 'Missing client address must be safe');
warningCheck(Fogito\Http\Request::getIP() === '', 'Missing IP must be safe');
warningCheck(Fogito\Http\Request::envMode() === 'production', 'Missing host must use production mode');
warningCheck(Fogito\Http\Request::getBestLanguage() === null, 'An empty quality header must return null');
$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US,en;q=0.8,az;q=0.9';
warningCheck(Fogito\Http\Request::getBestLanguage() === 'en-US', 'Language quality values must be parsed');

$filter = new Fogito\Filter();
warningCheck($filter->sanitize(null, 'trim') === '', 'Null trim input must be normalized');
warningCheck($filter->sanitize(null, 'lower') === '', 'Null lowercase input must be normalized');
warningCheck($filter->sanitize(null, 'striptags') === '', 'Null strip-tags input must be normalized');

$app = new Fogito\App();
warningCheck($app->get('missing') === null, 'Missing services must return null without warnings');
$app->set('config', new Fogito\Config(array()));
warningCheck(Fogito\Config::getDomain() === 'app.', 'Missing host must be accepted by domain generation');

Fogito\Lib\Company::setData(array());
warningCheck(Fogito\Lib\Company::getId() === null, 'Company data without an ID must be safe');
warningCheck(Fogito\Lib\Cache::is_brute_force(null, array()) === false, 'Empty brute-force settings must be safe');

final class WarningModel extends Fogito\Db\ModelManager
{
}

$model = new WarningModel();
$model->runtimeField = 'value';
warningCheck($model->runtimeField === 'value', 'MongoDB models must allow hydrated runtime fields');

$remoteModel = new Fogito\Models\CoreUsers(array('runtimeField' => 'value'));
warningCheck($remoteModel->runtimeField === 'value', 'Remote models must allow hydrated runtime fields');
$emptyUser = Fogito\Models\CoreUsers::findFirstAndSet(array(), array());
warningCheck(is_array($emptyUser) && $emptyUser['user'] === false, 'Empty user enrichment must be safe');
warningCheck(Fogito\Models\CoreUsers::mergePositions(array(array()))[0]['position'] === '', 'Missing positions must be safe');

Fogito\Lib\Company::setData(array('id' => 'company-id'));
warningCheck(Fogito\Models\CoreCompanies::validateCompanyFilter('company-id') === true, 'Company validation must handle sparse company data');

Fogito\Lib\Cache::$data = new class {
    public function get($key)
    {
        return false;
    }
};
warningCheck(Fogito\Models\CoreTimezones::getCache() === null, 'An empty timezone cache must be safe');

$router = new Fogito\Router(false);
warningCheck($router->getRewriteUri() === '/', 'Missing CLI rewrite arguments must be safe');
$route = $router->add('#^/module/controller/action(/.*)*$#', array(
    'module' => 'module',
    'controller' => 'controller',
    'action' => 'action',
    'params' => 1,
));
$router->handle('/module/controller/action/first/second');
warningCheck($router->getParams() === array('first', 'second'), 'Route parameters must be split safely');

$namespacedRoute = new Fogito\Router\Route('/namespaced', 'module::Vendor\\ExampleController::show');
warningCheck($namespacedRoute->getPaths()['namespace'] === 'Vendor', 'Namespaced route strings must be parsed without get_class errors');
warningCheck($namespacedRoute->getPaths()['controller'] === 'example_controller', 'Namespaced controller names must be normalized');

echo "Warning and deprecation regression checks passed.\n";
