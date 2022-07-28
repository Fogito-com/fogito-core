<?php
namespace Fogito\Models;

use Fogito\Config;
use Fogito\Http\Response;
use Fogito\Lib\Company;
use Fogito\Lib\Lang;

class CoreCompanies extends \Fogito\Db\RemoteModelManager
{
    public function __construct($properties = [])
    {
        foreach ($properties as $key => $value) {
            $this->{$key} = $value;
        }
    }

    public static function getServer()
    {
        return Config::getUrl("s2s");
    }

    public static function getSource()
    {
        return "companies";
    }

    public static function isBranch($companyId, $parentId=false)
    {
        $parentId = $parentId ? $parentId: Company::getId();
        return self::findFirst([
            [
                "id"            => $companyId,
                "parent_ids"    => $parentId
            ]
        ]);
    }

    public static function validateCompanyFilter($companyId, $parentId=false, $stop=true)
    {
        if(trim($companyId) !== Company::getId() && !self::isBranch($companyId, $parentId))
            if($stop)
            {
                Response::error(Lang::get("CompanyNotFound", "Company was not found"));
                return false;
            }
            else
            {
                return false;
            }
        return true;
    }
}
