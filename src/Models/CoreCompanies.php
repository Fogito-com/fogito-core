<?php
namespace Fogito\Models;

use Fogito\Config;
use Fogito\Lib\Company;

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
}
