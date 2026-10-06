<?php
declare(strict_types=1);

namespace Fogito { class App { public static $di; } class Config { public static function getUrl($service){return 'http://legacy.invalid/api/'.$service;} } }
namespace Fogito\Http {
    class Request {
        public static function getServer($key){return $key==='REQUEST_URI'?'/owned-fixture':null;}
        public static function get($key){return '';}
    }
}
namespace Fogito\Lib {
    class Auth {
        public static $enabled=true;
        public static function getData(){return ['id'=>'synthetic-actor'];} public static function getId(){return 'synthetic-actor';}
        public static function getToken(){return 'synthetic-human';} public static function getTokenUser(){return '';}
        public static function tokenAllowed(){return self::$enabled;}
    }
    class Lang { public static function getLang(){return 'da';} }
}
namespace Fogito\Db {
    // Test-only cURL boundary: the unchanged legacy branch is observable without a socket.
    final class RequestTransportProbe {
        public static $calls=0; public static $last; public static $error=0;
        public static $raw='{"status":"success","data":{"id":"synthetic-row"}}';
    }
    function curl_init($url){$handle=(object)['url'=>$url,'options'=>[]];RequestTransportProbe::$last=$handle;return $handle;}
    function curl_setopt($handle,$option,$value){$handle->options[$option]=$value;return true;}
    function curl_exec($handle){++RequestTransportProbe::$calls;return RequestTransportProbe::$raw;}
    function curl_errno($handle){return RequestTransportProbe::$error;}
}
namespace {
    use Fogito\Db\{RemoteModelManager,RequestTransportProbe};
    use Fogito\Models\{CoreUsers,CoreCompanies,CoreFiles};
    use Fogito\Lib\Auth;
    final class RequestTransportConfig {
        public function toArray(){return ['app_id'=>207,'server_token'=>'synthetic-service'];}
    }
    \Fogito\App::$di=(object)['config'=>(object)['s2s'=>new RequestTransportConfig()]];
    $root=dirname(__DIR__);
    require $root.'/src/Db/RemoteModelManager.php';
    foreach(['CoreUsers','CoreCompanies','CoreFiles'] as $class){require $root.'/src/Models/'.$class.'.php';}
    $checks=[];
    function checkTransport(bool $condition,string $label):void{
        global $checks;if(!$condition){throw new \RuntimeException('FAIL: '.$label);}$checks[]=$label;echo 'ok '.count($checks).' - '.$label."\n";
    }
    $input=['data'=>['filter'=>['id'=>'synthetic-row']]];
    $raw=RemoteModelManager::curl('http://legacy.invalid/read',$input);
    $legacy=RequestTransportProbe::$last;
    $expected=$input+['app_id'=>207,'server_token'=>'synthetic-service'];
    $expected['lang']='da';$expected['token_user']='synthetic-actor';$expected['http_origin']=null;$expected['request_uri']='/owned-fixture';$expected['token']='synthetic-human';
    $sent=json_decode($legacy->options[CURLOPT_POSTFIELDS],true,16,JSON_THROW_ON_ERROR);
    checkTransport($raw===RequestTransportProbe::$raw&&RequestTransportProbe::$calls===1&&$legacy->url==='http://legacy.invalid/read'&&$sent==$expected,
        'Unset hook retains the prior raw response, legacy URL and merged request data');
    checkTransport($legacy->options===[CURLOPT_POST=>1,CURLOPT_POSTFIELDS=>json_encode($sent),
        CURLOPT_HTTPHEADER=>['Content-Type:application/json'],CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>2,CURLOPT_TIMEOUT=>60,
        CURLOPT_SSL_VERIFYHOST=>false,CURLOPT_SSL_VERIFYPEER=>false],
        'Unset hook leaves every existing cURL option unchanged');
    RequestTransportProbe::$error=7;
    checkTransport(RemoteModelManager::curl('http://legacy.invalid/read',$input)===json_encode(['status'=>'error','description'=>'Connection Error','error_code'=>1003]),
        'Unset hook preserves the existing legacy connection-error envelope');
    RequestTransportProbe::$error=0;$calls=[];
    $hook=static function($url,$data)use(&$calls){$calls[]=['url'=>$url,'data'=>$data];return RequestTransportProbe::$raw;};
    checkTransport(RemoteModelManager::setRequestTransport($hook)===null,'First opt-in is explicit and returns the previously unset callback');
    $before=RequestTransportProbe::$calls;
    $users=CoreUsers::findFirst(['filter'=>['id'=>'synthetic-user'],0=>[]],['result'=>false]);
    $company=CoreCompanies::findById('synthetic-company',['result'=>false]);
    $file=CoreFiles::findFirst(['filter'=>['id'=>'synthetic-file','company_id'=>'synthetic-company','user_id'=>'synthetic-owner'],0=>[]],['result'=>false]);
    checkTransport(array_column($calls,'url')===['http://legacy.invalid/api/s2s/users/findfirst','http://legacy.invalid/api/s2s/companies/findfirst',
        'http://legacy.invalid/api/files/s2s/findfirst']&&$users===$company&&$company===$file&&$file===['id'=>'synthetic-row']
        &&RequestTransportProbe::$calls===$before,
        'Real CoreUsers, CoreCompanies and CoreFiles inherited calls all use the hook without the default cURL branch');
    checkTransport($calls[0]['data']['app_id']===207&&$calls[0]['data']['server_token']==='synthetic-service'
        &&$calls[0]['data']['token']==='synthetic-human'&&$calls[0]['data']['token_user']==='synthetic-actor'
        &&$calls[2]['data']['data']['filter']===['id'=>'synthetic-file','company_id'=>'synthetic-company','user_id'=>'synthetic-owner'],
        'Hook sees the original filtering, runtime identity and exact permanent-file filters');
    Auth::$enabled=false;CoreUsers::find(['filter'=>['id'=>['$in'=>['synthetic-user']]],0=>[]],['result'=>false]);
    checkTransport(!array_key_exists('token',$calls[3]['data'])&&$calls[3]['data']['token_user']==='synthetic-actor',
        'Native token-disabled reads remain token-disabled before the hook');
    Auth::$enabled=true;
    $fail=static function($url,$data){throw new \RuntimeException('Synthetic safe transport refusal.');};
    checkTransport(RemoteModelManager::setRequestTransport($fail)===$hook,'Callback replacement returns the exact previous request-local callback');
    $caught=false;try{CoreUsers::findById('synthetic-user',['result'=>false]);}catch(\RuntimeException $e){$caught=true;}
    checkTransport($caught&&RequestTransportProbe::$calls===$before,'A refused hook call never falls back to legacy HTTP');
    checkTransport(RemoteModelManager::setRequestTransport(null)===$fail,'Explicit clearing removes the callback');
    $raw=RemoteModelManager::curl('http://legacy.invalid/read',$input);
    checkTransport($raw===RequestTransportProbe::$raw&&RequestTransportProbe::$calls===$before+1,
        'A cleared hook restores the unchanged default branch for unconfigured callers');
    $result=['check_count'=>count($checks),'checks'=>$checks,'network_calls'=>0,'database_calls'=>0,'legacy_curl_executions_mocked'=>RequestTransportProbe::$calls];
    if($path=getenv('CORE_REQUEST_TRANSPORT_EVIDENCE')){file_put_contents($path,json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n");}
    echo 'Core request transport: '.count($checks)." checks passed; no network/database calls.\n";
}
