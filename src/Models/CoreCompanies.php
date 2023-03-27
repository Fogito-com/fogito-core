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
        if(trim($companyId) === Company::getId())
        {
            return true;
        }
        else if(in_array(trim($companyId), Company::getData()->branch_ids))
        {
            return true;
        }
        else if(in_array(trim($companyId), Company::getData()->parent_ids))
        {
            return false;
        }
        else if(trim($companyId) === Company::getData()->parent_super_id)
        {
            return false;
        }
        else if(!self::isBranch($companyId, $parentId))
        {
            if($stop)
            {
                Response::error(Lang::get("CompanyNotFound", "Company was not found"));
                return false;
            }
            else
            {
                return false;
            }
        }
        return true;
    }

    public static function canShareWithCompany($companyId)
    {
        if(trim($companyId) === Company::getId())
        {
            return true;
        }
        else if(in_array(trim($companyId), Company::getData()->branch_ids))
        {
            return true;
        }
        else if(in_array(trim($companyId), Company::getData()->parent_ids))
        {
            return true;
        }
        else if(trim($companyId) === Company::getData()->parent_super_id)
        {
            return true;
        }
        else
        {
            $bindRemote = [
                'id' => $companyId,
                'is_deleted' => ['$ne' => 1],
                'parent_super_id' => Company::getData()->parent_super_id
            ];
            $query = CoreCompanies::findFirst([$bindRemote]);
            if($query)
                return true;
        }

        return false;
    }
}
