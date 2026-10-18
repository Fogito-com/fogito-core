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
        $companyId = trim((string)$companyId);
        $companyData = Company::getData();
        if($companyId === Company::getId())
        {
            return true;
        }
        else if(in_array($companyId, (array)($companyData->branch_ids ?? [])))
        {
            return true;
        }
        else if(in_array($companyId, (array)($companyData->parent_ids ?? [])))
        {
            return false;
        }
        else if($companyId === ($companyData->parent_super_id ?? null))
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
        $companyId = trim((string)$companyId);
        $companyData = Company::getData();
        if($companyId === Company::getId())
        {
            return true;
        }
        else if(in_array($companyId, (array)($companyData->branch_ids ?? [])))
        {
            return true;
        }
        else if(in_array($companyId, (array)($companyData->parent_ids ?? [])))
        {
            return true;
        }
        else if($companyId === ($companyData->parent_super_id ?? null))
        {
            return true;
        }
        else
        {
            $bindRemote = [
                'id' => $companyId,
                'is_deleted' => ['$ne' => 1],
                'parent_super_id' => $companyData->parent_super_id ?? null
            ];
            $query = CoreCompanies::findFirst([$bindRemote]);
            if($query)
                return true;
        }

        return false;
    }
}
