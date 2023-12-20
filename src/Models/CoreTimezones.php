<?php
namespace Fogito\Models;

use Fogito\Config;
use Fogito\Db\ModelManager;
use Fogito\Http\Request;
use Fogito\Lib\Cache;

class CoreTimezones extends \Fogito\Db\RemoteModelManager
{
    public static $data = false;

    public static $defaultTimezone = 100;
    public static $userTimezone = 101;
    public static $defaultDateFormat = "Y-m-d H:i:s";

    public static function getServer()
    {
        return Config::getUrl("s2s");
    }

    public static function fetch()
    {
        if($list=self::$data)
        {
            return $list;
        }
        elseif($list=self::getCache())
        {
            self::$data = $list;
            return $list;
        }
        else
        {
            $result = self::curl(Config::getUrl("s2s") . "/default/timezones", []);
        }

        if ($result)
            $result = json_decode($result, true);
        if($result && $result["status"] === "success")
        {
            $list = [];
            foreach ($result["data"] as $value)
                $list[$value["id"]] = $value;
            self::$data = $list;
            self::setCache($list);
            return $list;
        }
        return false;
    }

    public static function getCachKey()
    {
        return "core-timezones";
    }

    public static function getCache()
    {
        return json_decode(Cache::get(self::getCachKey()), true);
    }

    public static function setCache($data)
    {
        return Cache::set(self::getCachKey(), json_encode($data), 3600);
    }


    /*
     * datetime: timestamp / datetime / mongodate
     *
     * options: [
     *    tzfrom: id from timezone list, default: You can define constant DEFAULT_TIMEZONE in settings.php if not defined value: 100 - server time GMT 0
     *    tzto: id from timezone list, default: You can define constant USER_TIMEZONE in AclApi.php if not defined value: 101 - Denmark
     *    formatfrom: in settings.php You can define constant "DEFAULT_DATE_FORMAT", if not defined default: "Y-m-d H:i:s"
     *    formatto: in settings.php You can define constant "DEFAULT_DATE_FORMAT", if not defined default: "Y-m-d H:i:s"
     * ]
     *
     *
     * $dateTo = TimeZones::date(strtotime($date), false, ["tzfrom" => 102, "tzto" => 101]);
     */
    public static function date($datetime=0, $realFormatto=false, $options=[])
    {
        $tzfrom         = self::getById($options["tzfrom"]) ?  (int)$options["tzfrom"]: self::$defaultTimezone;
        $tzto           = self::getById($options["tzto"]) ?  (int)$options["tzto"]: self::$userTimezone;
        $formatfrom     = $options["formatfrom"] ?  $options["formatfrom"]: self::$defaultDateFormat;
        $formatto       = $realFormatto ?  $realFormatto: self::$defaultDateFormat;

        if (method_exists($datetime, "toDateTime")) {
            $datetime = @$datetime->toDateTime()->format("Y-m-d H:i:s");
        }elseif(is_numeric($datetime)){
            $datetime = date("Y-m-d H:i:s", $datetime);
        }

        $dt1 = new \DateTime($datetime, new \DateTimeZone(self::getById($tzfrom)["slug"]));
        $toTimezone = new \DateTimeZone(self::getById($tzto)["slug"]);
        $dt1->setTimezone($toTimezone);
        $datetime = $dt1->format('Y-m-d H:i:s');

        $timestamp = strtotime($datetime);

        if($realFormatto === "unix" || $realFormatto === "unixtime")
            return $timestamp;
        if($realFormatto === "mongo")
            return ModelManager::getDate($timestamp);
        return date($formatto, $timestamp);
    }

    /*
     * countryCode: $_SERVER["HTTP_CF_IPCOUNTRY"], Cloudflare returns country code: AZ
     */
    public static function detectTz($datetime=false)
    {
        $timezone = self::getBySlug(Request::get("tz"));
        if(!$timezone)
            $timezone = self::getById(101);
        $currentDate = self::date(time(), false, ["tzfrom" => 100, "tzto" => $timezone["id"]]);
        return [
            "id"            => $timezone["id"],
            "slug"          => $timezone["slug"],
            "offset"        => $timezone["offset"],
            "title"         => $timezone["title"],
        ];
    }

    public static function getBySlug($slug)
    {
        foreach (self::getList() as $value)
        {
            if(mb_strtolower($value["slug"]) === mb_strtolower($slug))
                return $value;
        }
        return false;
    }

    public static function getByCountryCode($countryCode)
    {
        foreach (self::getList() as $value)
        {
            if($value["country_code"] === mb_strtolower($countryCode))
                return $value;
        }
        return false;
    }

    public static function getById($id)
    {
        return self::getList()[(int)$id];
    }


    public static function getList()
    {
        if(self::$data)
            return self::$data;

        if($data=self::fetch())
            return $data;

        return [
            280 => ["id" => 280, "active" => 0, "slug" => "Pacific/Midway", "offset" => "-11", "title" => "Pacific/Midway"],
            332 => ["id" => 332, "active" => 0, "slug" => "Pacific/Niue", "offset" => "-11", "title" => "Pacific/Niue"],
            399 => ["id" => 399, "active" => 0, "slug" => "Pacific/Pago_Pago", "offset" => "-11", "title" => "Pacific/Pago_Pago"],
            103 => ["id" => 103, "active" => 0, "slug" => "Pacific/Honolulu", "offset" => "-10", "title" => "Pacific/Honolulu"],
            104 => ["id" => 104, "active" => 0, "slug" => "Pacific/Rarotonga", "offset" => "-10", "title" => "Pacific/Rarotonga"],
            105 => ["id" => 105, "active" => 0, "slug" => "Pacific/Tahiti", "offset" => "-10", "title" => "Pacific/Tahiti"],
            106 => ["id" => 106, "active" => 0, "slug" => "Pacific/Marquesas", "offset" => "-9.5", "title" => "Pacific/Marquesas"],
            107 => ["id" => 107, "active" => 0, "slug" => "America/Adak", "offset" => "-9", "title" => "America/Adak"],
            108 => ["id" => 108, "active" => 0, "slug" => "Pacific/Gambier", "offset" => "-9", "title" => "Pacific/Gambier"],
            109 => ["id" => 109, "active" => 0, "slug" => "America/Anchorage", "offset" => "-8", "title" => "America/Anchorage"],
            110 => ["id" => 110, "active" => 0, "slug" => "America/Juneau", "offset" => "-8", "title" => "America/Juneau"],
            111 => ["id" => 111, "active" => 0, "slug" => "America/Metlakatla", "offset" => "-8", "title" => "America/Metlakatla"],
            112 => ["id" => 112, "active" => 0, "slug" => "America/Nome", "offset" => "-8", "title" => "America/Nome"],
            113 => ["id" => 113, "active" => 0, "slug" => "America/Sitka", "offset" => "-8", "title" => "America/Sitka"],
            114 => ["id" => 114, "active" => 0, "slug" => "America/Yakutat", "offset" => "-8", "title" => "America/Yakutat"],
            115 => ["id" => 115, "active" => 0, "slug" => "Pacific/Pitcairn", "offset" => "-8", "title" => "Pacific/Pitcairn"],
            116 => ["id" => 116, "active" => 0, "slug" => "America/Creston", "offset" => "-7", "title" => "America/Creston"],
            117 => ["id" => 117, "active" => 0, "slug" => "America/Dawson", "offset" => "-7", "title" => "America/Dawson"],
            118 => ["id" => 118, "active" => 0, "slug" => "America/Dawson_Creek", "offset" => "-7", "title" => "America/Dawson_Creek"],
            119 => ["id" => 119, "active" => 0, "slug" => "America/Fort_Nelson", "offset" => "-7", "title" => "America/Fort_Nelson"],
            120 => ["id" => 120, "active" => 0, "slug" => "America/Hermosillo", "offset" => "-7", "title" => "America/Hermosillo"],
            121 => ["id" => 121, "active" => 0, "slug" => "America/Los_Angeles", "offset" => "-7", "title" => "America/Los_Angeles"],
            122 => ["id" => 122, "active" => 0, "slug" => "America/Phoenix", "offset" => "-7", "title" => "America/Phoenix"],
            123 => ["id" => 123, "active" => 0, "slug" => "America/Tijuana", "offset" => "-7", "title" => "America/Tijuana"],
            124 => ["id" => 124, "active" => 0, "slug" => "America/Vancouver", "offset" => "-7", "title" => "America/Vancouver"],
            125 => ["id" => 125, "active" => 0, "slug" => "America/Whitehorse", "offset" => "-7", "title" => "America/Whitehorse"],
            126 => ["id" => 126, "active" => 0, "slug" => "America/Belize", "offset" => "-6", "title" => "America/Belize"],
            127 => ["id" => 127, "active" => 0, "slug" => "America/Boise", "offset" => "-6", "title" => "America/Boise"],
            128 => ["id" => 128, "active" => 0, "slug" => "America/Cambridge_Bay", "offset" => "-6", "title" => "America/Cambridge_Bay"],
            129 => ["id" => 129, "active" => 0, "slug" => "America/Chihuahua", "offset" => "-6", "title" => "America/Chihuahua"],
            130 => ["id" => 130, "active" => 0, "slug" => "America/Costa_Rica", "offset" => "-6", "title" => "America/Costa_Rica"],
            131 => ["id" => 131, "active" => 0, "slug" => "America/Denver", "offset" => "-6", "title" => "America/Denver"],
            132 => ["id" => 132, "active" => 0, "slug" => "America/Edmonton", "offset" => "-6", "title" => "America/Edmonton"],
            133 => ["id" => 133, "active" => 0, "slug" => "America/El_Salvador", "offset" => "-6", "title" => "America/El_Salvador"],
            134 => ["id" => 134, "active" => 0, "slug" => "America/Guatemala", "offset" => "-6", "title" => "America/Guatemala"],
            135 => ["id" => 135, "active" => 0, "slug" => "America/Inuvik", "offset" => "-6", "title" => "America/Inuvik"],
            136 => ["id" => 136, "active" => 0, "slug" => "America/Managua", "offset" => "-6", "title" => "America/Managua"],
            137 => ["id" => 137, "active" => 0, "slug" => "America/Mazatlan", "offset" => "-6", "title" => "America/Mazatlan"],
            138 => ["id" => 138, "active" => 0, "slug" => "America/Ojinaga", "offset" => "-6", "title" => "America/Ojinaga"],
            139 => ["id" => 139, "active" => 0, "slug" => "America/Regina", "offset" => "-6", "title" => "America/Regina"],
            140 => ["id" => 140, "active" => 0, "slug" => "America/Swift_Current", "offset" => "-6", "title" => "America/Swift_Current"],
            141 => ["id" => 141, "active" => 0, "slug" => "America/Tegucigalpa", "offset" => "-6", "title" => "America/Tegucigalpa"],
            142 => ["id" => 142, "active" => 0, "slug" => "America/Yellowknife", "offset" => "-6", "title" => "America/Yellowknife"],
            143 => ["id" => 143, "active" => 0, "slug" => "Pacific/Galapagos", "offset" => "-6", "title" => "Pacific/Galapagos"],
            144 => ["id" => 144, "active" => 0, "slug" => "America/Atikokan", "offset" => "-5", "title" => "America/Atikokan"],
            145 => ["id" => 145, "active" => 0, "slug" => "America/Bahia_Banderas", "offset" => "-5", "title" => "America/Bahia_Banderas"],
            146 => ["id" => 146, "active" => 0, "slug" => "America/Bogota", "offset" => "-5", "title" => "America/Bogota"],
            147 => ["id" => 147, "active" => 0, "slug" => "America/Cancun", "offset" => "-5", "title" => "America/Cancun"],
            148 => ["id" => 148, "active" => 0, "slug" => "America/Cayman", "offset" => "-5", "title" => "America/Cayman"],
            149 => ["id" => 149, "active" => 0, "slug" => "America/Chicago", "offset" => "-5", "title" => "America/Chicago"],
            150 => ["id" => 150, "active" => 0, "slug" => "America/Eirunepe", "offset" => "-5", "title" => "America/Eirunepe"],
            151 => ["id" => 151, "active" => 0, "slug" => "America/Guayaquil", "offset" => "-5", "title" => "America/Guayaquil"],
            152 => ["id" => 152, "active" => 0, "slug" => "America/Indiana/Knox", "offset" => "-5", "title" => "America/Indiana/Knox"],
            153 => ["id" => 153, "active" => 0, "slug" => "America/Indiana/Tell_City", "offset" => "-5", "title" => "America/Indiana/Tell_City"],
            154 => ["id" => 154, "active" => 0, "slug" => "America/Jamaica", "offset" => "-5", "title" => "America/Jamaica"],
            155 => ["id" => 155, "active" => 0, "slug" => "America/Lima", "offset" => "-5", "title" => "America/Lima"],
            156 => ["id" => 156, "active" => 0, "slug" => "America/Matamoros", "offset" => "-5", "title" => "America/Matamoros"],
            157 => ["id" => 157, "active" => 0, "slug" => "America/Menominee", "offset" => "-5", "title" => "America/Menominee"],
            158 => ["id" => 158, "active" => 0, "slug" => "America/Merida", "offset" => "-5", "title" => "America/Merida"],
            159 => ["id" => 159, "active" => 0, "slug" => "America/Mexico_City", "offset" => "-5", "title" => "America/Mexico_City"],
            160 => ["id" => 160, "active" => 0, "slug" => "America/Monterrey", "offset" => "-5", "title" => "America/Monterrey"],
            161 => ["id" => 161, "active" => 0, "slug" => "America/North_Dakota/Beulah", "offset" => "-5", "title" => "America/North_Dakota/Beulah"],
            162 => ["id" => 162, "active" => 0, "slug" => "America/North_Dakota/Center", "offset" => "-5", "title" => "America/North_Dakota/Center"],
            163 => ["id" => 163, "active" => 0, "slug" => "America/North_Dakota/New_Salem", "offset" => "-5", "title" => "America/North_Dakota/New_Salem"],
            164 => ["id" => 164, "active" => 0, "slug" => "America/Panama", "offset" => "-5", "title" => "America/Panama"],
            165 => ["id" => 165, "active" => 0, "slug" => "America/Rainy_River", "offset" => "-5", "title" => "America/Rainy_River"],
            166 => ["id" => 166, "active" => 0, "slug" => "America/Rankin_Inlet", "offset" => "-5", "title" => "America/Rankin_Inlet"],
            167 => ["id" => 167, "active" => 0, "slug" => "America/Resolute", "offset" => "-5", "title" => "America/Resolute"],
            168 => ["id" => 168, "active" => 0, "slug" => "America/Rio_Branco", "offset" => "-5", "title" => "America/Rio_Branco"],
            169 => ["id" => 169, "active" => 0, "slug" => "America/Winnipeg", "offset" => "-5", "title" => "America/Winnipeg"],
            170 => ["id" => 170, "active" => 0, "slug" => "Pacific/Easter", "offset" => "-5", "title" => "Pacific/Easter"],
            171 => ["id" => 171, "active" => 0, "slug" => "America/Anguilla", "offset" => "-4", "title" => "America/Anguilla"],
            172 => ["id" => 172, "active" => 0, "slug" => "America/Antigua", "offset" => "-4", "title" => "America/Antigua"],
            173 => ["id" => 173, "active" => 0, "slug" => "America/Aruba", "offset" => "-4", "title" => "America/Aruba"],
            174 => ["id" => 174, "active" => 0, "slug" => "America/Asuncion", "offset" => "-4", "title" => "America/Asuncion"],
            175 => ["id" => 175, "active" => 0, "slug" => "America/Barbados", "offset" => "-4", "title" => "America/Barbados"],
            176 => ["id" => 176, "active" => 0, "slug" => "America/Blanc-Sablon", "offset" => "-4", "title" => "America/Blanc-Sablon"],
            177 => ["id" => 177, "active" => 0, "slug" => "America/Boa_Vista", "offset" => "-4", "title" => "America/Boa_Vista"],
            178 => ["id" => 178, "active" => 0, "slug" => "America/Campo_Grande", "offset" => "-4", "title" => "America/Campo_Grande"],
            179 => ["id" => 179, "active" => 0, "slug" => "America/Caracas", "offset" => "-4", "title" => "America/Caracas"],
            180 => ["id" => 180, "active" => 0, "slug" => "America/Cuiaba", "offset" => "-4", "title" => "America/Cuiaba"],
            181 => ["id" => 181, "active" => 0, "slug" => "America/Curacao", "offset" => "-4", "title" => "America/Curacao"],
            182 => ["id" => 182, "active" => 0, "slug" => "America/Detroit", "offset" => "-4", "title" => "America/Detroit"],
            183 => ["id" => 183, "active" => 0, "slug" => "America/Dominica", "offset" => "-4", "title" => "America/Dominica"],
            184 => ["id" => 184, "active" => 0, "slug" => "America/Grand_Turk", "offset" => "-4", "title" => "America/Grand_Turk"],
            185 => ["id" => 185, "active" => 0, "slug" => "America/Grenada", "offset" => "-4", "title" => "America/Grenada"],
            186 => ["id" => 186, "active" => 0, "slug" => "America/Guadeloupe", "offset" => "-4", "title" => "America/Guadeloupe"],
            187 => ["id" => 187, "active" => 0, "slug" => "America/Guyana", "offset" => "-4", "title" => "America/Guyana"],
            188 => ["id" => 188, "active" => 0, "slug" => "America/Havana", "offset" => "-4", "title" => "America/Havana"],
            189 => ["id" => 189, "active" => 0, "slug" => "America/Indiana/Indianapolis", "offset" => "-4", "title" => "America/Indiana/Indianapolis"],
            190 => ["id" => 190, "active" => 0, "slug" => "America/Indiana/Marengo", "offset" => "-4", "title" => "America/Indiana/Marengo"],
            191 => ["id" => 191, "active" => 0, "slug" => "America/Indiana/Petersburg", "offset" => "-4", "title" => "America/Indiana/Petersburg"],
            192 => ["id" => 192, "active" => 0, "slug" => "America/Indiana/Vevay", "offset" => "-4", "title" => "America/Indiana/Vevay"],
            193 => ["id" => 193, "active" => 0, "slug" => "America/Indiana/Vincennes", "offset" => "-4", "title" => "America/Indiana/Vincennes"],
            194 => ["id" => 194, "active" => 0, "slug" => "America/Indiana/Winamac", "offset" => "-4", "title" => "America/Indiana/Winamac"],
            195 => ["id" => 195, "active" => 0, "slug" => "America/Iqaluit", "offset" => "-4", "title" => "America/Iqaluit"],
            196 => ["id" => 196, "active" => 0, "slug" => "America/Kentucky/Louisville", "offset" => "-4", "title" => "America/Kentucky/Louisville"],
            197 => ["id" => 197, "active" => 0, "slug" => "America/Kentucky/Monticello", "offset" => "-4", "title" => "America/Kentucky/Monticello"],
            198 => ["id" => 198, "active" => 0, "slug" => "America/Kralendijk", "offset" => "-4", "title" => "America/Kralendijk"],
            199 => ["id" => 199, "active" => 0, "slug" => "America/La_Paz", "offset" => "-4", "title" => "America/La_Paz"],
            200 => ["id" => 200, "active" => 0, "slug" => "America/Lower_Princes", "offset" => "-4", "title" => "America/Lower_Princes"],
            201 => ["id" => 201, "active" => 0, "slug" => "America/Manaus", "offset" => "-4", "title" => "America/Manaus"],
            202 => ["id" => 202, "active" => 0, "slug" => "America/Marigot", "offset" => "-4", "title" => "America/Marigot"],
            203 => ["id" => 203, "active" => 0, "slug" => "America/Martinique", "offset" => "-4", "title" => "America/Martinique"],
            204 => ["id" => 204, "active" => 0, "slug" => "America/Montserrat", "offset" => "-4", "title" => "America/Montserrat"],
            205 => ["id" => 205, "active" => 0, "slug" => "America/Nassau", "offset" => "-4", "title" => "America/Nassau"],
            206 => ["id" => 206, "active" => 1, "slug" => "America/New_York", "offset" => "-4", "title" => "New York"],
            207 => ["id" => 207, "active" => 0, "slug" => "America/Nipigon", "offset" => "-4", "title" => "America/Nipigon"],
            208 => ["id" => 208, "active" => 0, "slug" => "America/Pangnirtung", "offset" => "-4", "title" => "America/Pangnirtung"],
            209 => ["id" => 209, "active" => 0, "slug" => "America/Port-au-Prince", "offset" => "-4", "title" => "America/Port-au-Prince"],
            210 => ["id" => 210, "active" => 0, "slug" => "America/Port_of_Spain", "offset" => "-4", "title" => "America/Port_of_Spain"],
            211 => ["id" => 211, "active" => 0, "slug" => "America/Porto_Velho", "offset" => "-4", "title" => "America/Porto_Velho"],
            212 => ["id" => 212, "active" => 0, "slug" => "America/Puerto_Rico", "offset" => "-4", "title" => "America/Puerto_Rico"],
            213 => ["id" => 213, "active" => 0, "slug" => "America/Santo_Domingo", "offset" => "-4", "title" => "America/Santo_Domingo"],
            214 => ["id" => 214, "active" => 0, "slug" => "America/St_Barthelemy", "offset" => "-4", "title" => "America/St_Barthelemy"],
            215 => ["id" => 215, "active" => 0, "slug" => "America/St_Kitts", "offset" => "-4", "title" => "America/St_Kitts"],
            216 => ["id" => 216, "active" => 0, "slug" => "America/St_Lucia", "offset" => "-4", "title" => "America/St_Lucia"],
            217 => ["id" => 217, "active" => 0, "slug" => "America/St_Thomas", "offset" => "-4", "title" => "America/St_Thomas"],
            218 => ["id" => 218, "active" => 0, "slug" => "America/St_Vincent", "offset" => "-4", "title" => "America/St_Vincent"],
            219 => ["id" => 219, "active" => 0, "slug" => "America/Thunder_Bay", "offset" => "-4", "title" => "America/Thunder_Bay"],
            220 => ["id" => 220, "active" => 0, "slug" => "America/Toronto", "offset" => "-4", "title" => "America/Toronto"],
            221 => ["id" => 221, "active" => 0, "slug" => "America/Tortola", "offset" => "-4", "title" => "America/Tortola"],
            222 => ["id" => 222, "active" => 0, "slug" => "America/Araguaina", "offset" => "-3", "title" => "America/Araguaina"],
            223 => ["id" => 223, "active" => 0, "slug" => "America/Argentina/Buenos_Aires", "offset" => "-3", "title" => "America/Argentina/Buenos_Aires"],
            224 => ["id" => 224, "active" => 0, "slug" => "America/Argentina/Catamarca", "offset" => "-3", "title" => "America/Argentina/Catamarca"],
            225 => ["id" => 225, "active" => 0, "slug" => "America/Argentina/Cordoba", "offset" => "-3", "title" => "America/Argentina/Cordoba"],
            226 => ["id" => 226, "active" => 0, "slug" => "America/Argentina/Jujuy", "offset" => "-3", "title" => "America/Argentina/Jujuy"],
            227 => ["id" => 227, "active" => 0, "slug" => "America/Argentina/La_Rioja", "offset" => "-3", "title" => "America/Argentina/La_Rioja"],
            228 => ["id" => 228, "active" => 0, "slug" => "America/Argentina/Mendoza", "offset" => "-3", "title" => "America/Argentina/Mendoza"],
            229 => ["id" => 229, "active" => 0, "slug" => "America/Argentina/Rio_Gallegos", "offset" => "-3", "title" => "America/Argentina/Rio_Gallegos"],
            230 => ["id" => 230, "active" => 0, "slug" => "America/Argentina/Salta", "offset" => "-3", "title" => "America/Argentina/Salta"],
            231 => ["id" => 231, "active" => 0, "slug" => "America/Argentina/San_Juan", "offset" => "-3", "title" => "America/Argentina/San_Juan"],
            232 => ["id" => 232, "active" => 0, "slug" => "America/Argentina/San_Luis", "offset" => "-3", "title" => "America/Argentina/San_Luis"],
            233 => ["id" => 233, "active" => 0, "slug" => "America/Argentina/Tucuman", "offset" => "-3", "title" => "America/Argentina/Tucuman"],
            234 => ["id" => 234, "active" => 0, "slug" => "America/Argentina/Ushuaia", "offset" => "-3", "title" => "America/Argentina/Ushuaia"],
            235 => ["id" => 235, "active" => 0, "slug" => "America/Bahia", "offset" => "-3", "title" => "America/Bahia"],
            236 => ["id" => 236, "active" => 0, "slug" => "America/Belem", "offset" => "-3", "title" => "America/Belem"],
            237 => ["id" => 237, "active" => 0, "slug" => "America/Cayenne", "offset" => "-3", "title" => "America/Cayenne"],
            238 => ["id" => 238, "active" => 0, "slug" => "America/Fortaleza", "offset" => "-3", "title" => "America/Fortaleza"],
            239 => ["id" => 239, "active" => 0, "slug" => "America/Glace_Bay", "offset" => "-3", "title" => "America/Glace_Bay"],
            240 => ["id" => 240, "active" => 0, "slug" => "America/Goose_Bay", "offset" => "-3", "title" => "America/Goose_Bay"],
            241 => ["id" => 241, "active" => 0, "slug" => "America/Halifax", "offset" => "-3", "title" => "America/Halifax"],
            242 => ["id" => 242, "active" => 0, "slug" => "America/Maceio", "offset" => "-3", "title" => "America/Maceio"],
            243 => ["id" => 243, "active" => 0, "slug" => "America/Moncton", "offset" => "-3", "title" => "America/Moncton"],
            244 => ["id" => 244, "active" => 0, "slug" => "America/Montevideo", "offset" => "-3", "title" => "America/Montevideo"],
            245 => ["id" => 245, "active" => 0, "slug" => "America/Paramaribo", "offset" => "-3", "title" => "America/Paramaribo"],
            246 => ["id" => 246, "active" => 0, "slug" => "America/Punta_Arenas", "offset" => "-3", "title" => "America/Punta_Arenas"],
            247 => ["id" => 247, "active" => 0, "slug" => "America/Recife", "offset" => "-3", "title" => "America/Recife"],
            248 => ["id" => 248, "active" => 0, "slug" => "America/Santarem", "offset" => "-3", "title" => "America/Santarem"],
            249 => ["id" => 249, "active" => 0, "slug" => "America/Santiago", "offset" => "-3", "title" => "America/Santiago"],
            250 => ["id" => 250, "active" => 0, "slug" => "America/Sao_Paulo", "offset" => "-3", "title" => "America/Sao_Paulo"],
            251 => ["id" => 251, "active" => 0, "slug" => "America/Thule", "offset" => "-3", "title" => "America/Thule"],
            252 => ["id" => 252, "active" => 0, "slug" => "Antarctica/Palmer", "offset" => "-3", "title" => "Antarctica/Palmer"],
            253 => ["id" => 253, "active" => 0, "slug" => "Antarctica/Rothera", "offset" => "-3", "title" => "Antarctica/Rothera"],
            254 => ["id" => 254, "active" => 0, "slug" => "Atlantic/Bermuda", "offset" => "-3", "title" => "Atlantic/Bermuda"],
            255 => ["id" => 255, "active" => 0, "slug" => "Atlantic/Stanley", "offset" => "-3", "title" => "Atlantic/Stanley"],
            256 => ["id" => 256, "active" => 0, "slug" => "America/St_Johns", "offset" => "-2.5", "title" => "America/St_Johns"],
            257 => ["id" => 257, "active" => 0, "slug" => "America/Miquelon", "offset" => "-2", "title" => "America/Miquelon"],
            258 => ["id" => 258, "active" => 0, "slug" => "America/Noronha", "offset" => "-2", "title" => "America/Noronha"],
            259 => ["id" => 259, "active" => 0, "slug" => "America/Nuuk", "offset" => "-2", "title" => "America/Nuuk"],
            260 => ["id" => 260, "active" => 0, "slug" => "Atlantic/South_Georgia", "offset" => "-2", "title" => "Atlantic/South_Georgia"],
            261 => ["id" => 261, "active" => 0, "slug" => "Atlantic/Cape_Verde", "offset" => "-1", "title" => "Atlantic/Cape_Verde"],
            262 => ["id" => 262, "active" => 0, "slug" => "Africa/Abidjan", "offset" => "0", "title" => "Africa/Abidjan"],
            263 => ["id" => 263, "active" => 0, "slug" => "Africa/Accra", "offset" => "0", "title" => "Africa/Accra"],
            264 => ["id" => 264, "active" => 0, "slug" => "Africa/Bamako", "offset" => "0", "title" => "Africa/Bamako"],
            265 => ["id" => 265, "active" => 0, "slug" => "Africa/Banjul", "offset" => "0", "title" => "Africa/Banjul"],
            266 => ["id" => 266, "active" => 0, "slug" => "Africa/Bissau", "offset" => "0", "title" => "Africa/Bissau"],
            267 => ["id" => 267, "active" => 0, "slug" => "Africa/Conakry", "offset" => "0", "title" => "Africa/Conakry"],
            268 => ["id" => 268, "active" => 0, "slug" => "Africa/Dakar", "offset" => "0", "title" => "Africa/Dakar"],
            269 => ["id" => 269, "active" => 0, "slug" => "Africa/Freetown", "offset" => "0", "title" => "Africa/Freetown"],
            270 => ["id" => 270, "active" => 0, "slug" => "Africa/Lome", "offset" => "0", "title" => "Africa/Lome"],
            271 => ["id" => 271, "active" => 0, "slug" => "Africa/Monrovia", "offset" => "0", "title" => "Africa/Monrovia"],
            272 => ["id" => 272, "active" => 0, "slug" => "Africa/Nouakchott", "offset" => "0", "title" => "Africa/Nouakchott"],
            273 => ["id" => 273, "active" => 0, "slug" => "Africa/Ouagadougou", "offset" => "0", "title" => "Africa/Ouagadougou"],
            274 => ["id" => 274, "active" => 0, "slug" => "Africa/Sao_Tome", "offset" => "0", "title" => "Africa/Sao_Tome"],
            275 => ["id" => 275, "active" => 0, "slug" => "America/Danmarkshavn", "offset" => "0", "title" => "America/Danmarkshavn"],
            276 => ["id" => 276, "active" => 0, "slug" => "America/Scoresbysund", "offset" => "0", "title" => "America/Scoresbysund"],
            277 => ["id" => 277, "active" => 0, "slug" => "Atlantic/Azores", "offset" => "0", "title" => "Atlantic/Azores"],
            278 => ["id" => 278, "active" => 0, "slug" => "Atlantic/Reykjavik", "offset" => "0", "title" => "Atlantic/Reykjavik"],
            279 => ["id" => 279, "active" => 0, "slug" => "Atlantic/St_Helena", "offset" => "0", "title" => "Atlantic/St_Helena"],
            100 => ["id" => 100, "active" => 0, "slug" => "UTC", "offset" => "0", "title" => "UTC"],
            281 => ["id" => 281, "active" => 0, "slug" => "Africa/Algiers", "offset" => "1", "title" => "Africa/Algiers"],
            282 => ["id" => 282, "active" => 0, "slug" => "Africa/Bangui", "offset" => "1", "title" => "Africa/Bangui"],
            283 => ["id" => 283, "active" => 0, "slug" => "Africa/Brazzaville", "offset" => "1", "title" => "Africa/Brazzaville"],
            284 => ["id" => 284, "active" => 0, "slug" => "Africa/Casablanca", "offset" => "1", "title" => "Africa/Casablanca"],
            285 => ["id" => 285, "active" => 0, "slug" => "Africa/Douala", "offset" => "1", "title" => "Africa/Douala"],
            286 => ["id" => 286, "active" => 0, "slug" => "Africa/El_Aaiun", "offset" => "1", "title" => "Africa/El_Aaiun"],
            287 => ["id" => 287, "active" => 0, "slug" => "Africa/Kinshasa", "offset" => "1", "title" => "Africa/Kinshasa"],
            288 => ["id" => 288, "active" => 0, "slug" => "Africa/Lagos", "offset" => "1", "title" => "Africa/Lagos"],
            289 => ["id" => 289, "active" => 0, "slug" => "Africa/Libreville", "offset" => "1", "title" => "Africa/Libreville"],
            290 => ["id" => 290, "active" => 0, "slug" => "Africa/Luanda", "offset" => "1", "title" => "Africa/Luanda"],
            291 => ["id" => 291, "active" => 0, "slug" => "Africa/Malabo", "offset" => "1", "title" => "Africa/Malabo"],
            292 => ["id" => 292, "active" => 0, "slug" => "Africa/Ndjamena", "offset" => "1", "title" => "Africa/Ndjamena"],
            293 => ["id" => 293, "active" => 0, "slug" => "Africa/Niamey", "offset" => "1", "title" => "Africa/Niamey"],
            294 => ["id" => 294, "active" => 0, "slug" => "Africa/Porto-Novo", "offset" => "1", "title" => "Africa/Porto-Novo"],
            295 => ["id" => 295, "active" => 0, "slug" => "Africa/Tunis", "offset" => "1", "title" => "Africa/Tunis"],
            296 => ["id" => 296, "active" => 0, "slug" => "Atlantic/Canary", "offset" => "1", "title" => "Atlantic/Canary"],
            297 => ["id" => 297, "active" => 0, "slug" => "Atlantic/Faroe", "offset" => "1", "title" => "Atlantic/Faroe"],
            298 => ["id" => 298, "active" => 0, "slug" => "Atlantic/Madeira", "offset" => "1", "title" => "Atlantic/Madeira"],
            299 => ["id" => 299, "active" => 0, "slug" => "Europe/Dublin", "offset" => "1", "title" => "Europe/Dublin"],
            300 => ["id" => 300, "active" => 0, "slug" => "Europe/Guernsey", "offset" => "1", "title" => "Europe/Guernsey"],
            301 => ["id" => 301, "active" => 0, "slug" => "Europe/Isle_of_Man", "offset" => "1", "title" => "Europe/Isle_of_Man"],
            302 => ["id" => 302, "active" => 0, "slug" => "Europe/Jersey", "offset" => "1", "title" => "Europe/Jersey"],
            303 => ["id" => 303, "active" => 0, "slug" => "Europe/Lisbon", "offset" => "1", "title" => "Europe/Lisbon"],
            304 => ["id" => 304, "active" => 0, "slug" => "Europe/London", "offset" => "1", "title" => "Europe/London"],
            305 => ["id" => 305, "active" => 0, "slug" => "Africa/Blantyre", "offset" => "2", "title" => "Africa/Blantyre"],
            306 => ["id" => 306, "active" => 0, "slug" => "Africa/Bujumbura", "offset" => "2", "title" => "Africa/Bujumbura"],
            307 => ["id" => 307, "active" => 0, "slug" => "Africa/Cairo", "offset" => "2", "title" => "Africa/Cairo"],
            308 => ["id" => 308, "active" => 0, "slug" => "Africa/Ceuta", "offset" => "2", "title" => "Africa/Ceuta"],
            309 => ["id" => 309, "active" => 0, "slug" => "Africa/Gaborone", "offset" => "2", "title" => "Africa/Gaborone"],
            310 => ["id" => 310, "active" => 0, "slug" => "Africa/Harare", "offset" => "2", "title" => "Africa/Harare"],
            311 => ["id" => 311, "active" => 0, "slug" => "Africa/Johannesburg", "offset" => "2", "title" => "Africa/Johannesburg"],
            312 => ["id" => 312, "active" => 0, "slug" => "Africa/Juba", "offset" => "2", "title" => "Africa/Juba"],
            313 => ["id" => 313, "active" => 0, "slug" => "Africa/Khartoum", "offset" => "2", "title" => "Africa/Khartoum"],
            314 => ["id" => 314, "active" => 0, "slug" => "Africa/Kigali", "offset" => "2", "title" => "Africa/Kigali"],
            315 => ["id" => 315, "active" => 0, "slug" => "Africa/Lubumbashi", "offset" => "2", "title" => "Africa/Lubumbashi"],
            316 => ["id" => 316, "active" => 0, "slug" => "Africa/Lusaka", "offset" => "2", "title" => "Africa/Lusaka"],
            317 => ["id" => 317, "active" => 0, "slug" => "Africa/Maputo", "offset" => "2", "title" => "Africa/Maputo"],
            318 => ["id" => 318, "active" => 0, "slug" => "Africa/Maseru", "offset" => "2", "title" => "Africa/Maseru"],
            319 => ["id" => 319, "active" => 0, "slug" => "Africa/Mbabane", "offset" => "2", "title" => "Africa/Mbabane"],
            320 => ["id" => 320, "active" => 0, "slug" => "Africa/Tripoli", "offset" => "2", "title" => "Africa/Tripoli"],
            321 => ["id" => 321, "active" => 0, "slug" => "Africa/Windhoek", "offset" => "2", "title" => "Africa/Windhoek"],
            322 => ["id" => 322, "active" => 0, "slug" => "Antarctica/Troll", "offset" => "2", "title" => "Antarctica/Troll"],
            323 => ["id" => 323, "active" => 0, "slug" => "Arctic/Longyearbyen", "offset" => "2", "title" => "Arctic/Longyearbyen"],
            324 => ["id" => 324, "active" => 0, "slug" => "Europe/Amsterdam", "offset" => "2", "title" => "Europe/Amsterdam"],
            325 => ["id" => 325, "active" => 0, "slug" => "Europe/Andorra", "offset" => "2", "title" => "Europe/Andorra"],
            326 => ["id" => 326, "active" => 0, "slug" => "Europe/Belgrade", "offset" => "2", "title" => "Europe/Belgrade"],
            327 => ["id" => 327, "active" => 1, "slug" => "Europe/Berlin", "offset" => "2", "title" => "Europe/Berlin"],
            328 => ["id" => 328, "active" => 0, "slug" => "Europe/Bratislava", "offset" => "2", "title" => "Europe/Bratislava"],
            329 => ["id" => 329, "active" => 0, "slug" => "Europe/Brussels", "offset" => "2", "title" => "Europe/Brussels"],
            330 => ["id" => 330, "active" => 0, "slug" => "Europe/Budapest", "offset" => "2", "title" => "Europe/Budapest"],
            331 => ["id" => 331, "active" => 0, "slug" => "Europe/Busingen", "offset" => "2", "title" => "Europe/Busingen"],
            101 => ["id" => 101, "active" => 1, "slug" => "Europe/Copenhagen", "offset" => "2", "title" => "Copenhagen"],
            333 => ["id" => 333, "active" => 0, "slug" => "Europe/Gibraltar", "offset" => "2", "title" => "Europe/Gibraltar"],
            334 => ["id" => 334, "active" => 0, "slug" => "Europe/Kaliningrad", "offset" => "2", "title" => "Europe/Kaliningrad"],
            335 => ["id" => 335, "active" => 0, "slug" => "Europe/Ljubljana", "offset" => "2", "title" => "Europe/Ljubljana"],
            336 => ["id" => 336, "active" => 0, "slug" => "Europe/Luxembourg", "offset" => "2", "title" => "Europe/Luxembourg"],
            337 => ["id" => 337, "active" => 1, "slug" => "Europe/Madrid", "offset" => "2", "title" => "Europe/Madrid"],
            338 => ["id" => 338, "active" => 0, "slug" => "Europe/Malta", "offset" => "2", "title" => "Europe/Malta"],
            339 => ["id" => 339, "active" => 0, "slug" => "Europe/Monaco", "offset" => "2", "title" => "Europe/Monaco"],
            340 => ["id" => 340, "active" => 0, "slug" => "Europe/Oslo", "offset" => "2", "title" => "Europe/Oslo"],
            341 => ["id" => 341, "active" => 1, "slug" => "Europe/Paris", "offset" => "2", "title" => "Europe/Paris"],
            342 => ["id" => 342, "active" => 0, "slug" => "Europe/Podgorica", "offset" => "2", "title" => "Europe/Podgorica"],
            343 => ["id" => 343, "active" => 0, "slug" => "Europe/Prague", "offset" => "2", "title" => "Europe/Prague"],
            344 => ["id" => 344, "active" => 1, "slug" => "Europe/Rome", "offset" => "2", "title" => "Europe/Rome"],
            345 => ["id" => 345, "active" => 0, "slug" => "Europe/San_Marino", "offset" => "2", "title" => "Europe/San_Marino"],
            346 => ["id" => 346, "active" => 0, "slug" => "Europe/Sarajevo", "offset" => "2", "title" => "Europe/Sarajevo"],
            347 => ["id" => 347, "active" => 0, "slug" => "Europe/Skopje", "offset" => "2", "title" => "Europe/Skopje"],
            348 => ["id" => 348, "active" => 0, "slug" => "Europe/Stockholm", "offset" => "2", "title" => "Europe/Stockholm"],
            349 => ["id" => 349, "active" => 0, "slug" => "Europe/Tirane", "offset" => "2", "title" => "Europe/Tirane"],
            350 => ["id" => 350, "active" => 0, "slug" => "Europe/Vaduz", "offset" => "2", "title" => "Europe/Vaduz"],
            351 => ["id" => 351, "active" => 0, "slug" => "Europe/Vatican", "offset" => "2", "title" => "Europe/Vatican"],
            352 => ["id" => 352, "active" => 0, "slug" => "Europe/Vienna", "offset" => "2", "title" => "Europe/Vienna"],
            353 => ["id" => 353, "active" => 1, "slug" => "Europe/Warsaw", "offset" => "2", "title" => "Europe/Warsaw"],
            354 => ["id" => 354, "active" => 0, "slug" => "Europe/Zagreb", "offset" => "2", "title" => "Europe/Zagreb"],
            355 => ["id" => 355, "active" => 0, "slug" => "Europe/Zurich", "offset" => "2", "title" => "Europe/Zurich"],
            356 => ["id" => 356, "active" => 0, "slug" => "Africa/Addis_Ababa", "offset" => "3", "title" => "Africa/Addis_Ababa"],
            357 => ["id" => 357, "active" => 0, "slug" => "Africa/Asmara", "offset" => "3", "title" => "Africa/Asmara"],
            358 => ["id" => 358, "active" => 0, "slug" => "Africa/Dar_es_Salaam", "offset" => "3", "title" => "Africa/Dar_es_Salaam"],
            359 => ["id" => 359, "active" => 0, "slug" => "Africa/Djibouti", "offset" => "3", "title" => "Africa/Djibouti"],
            360 => ["id" => 360, "active" => 0, "slug" => "Africa/Kampala", "offset" => "3", "title" => "Africa/Kampala"],
            361 => ["id" => 361, "active" => 0, "slug" => "Africa/Mogadishu", "offset" => "3", "title" => "Africa/Mogadishu"],
            362 => ["id" => 362, "active" => 0, "slug" => "Africa/Nairobi", "offset" => "3", "title" => "Africa/Nairobi"],
            363 => ["id" => 363, "active" => 0, "slug" => "Antarctica/Syowa", "offset" => "3", "title" => "Antarctica/Syowa"],
            364 => ["id" => 364, "active" => 0, "slug" => "Asia/Aden", "offset" => "3", "title" => "Asia/Aden"],
            365 => ["id" => 365, "active" => 0, "slug" => "Asia/Amman", "offset" => "3", "title" => "Asia/Amman"],
            366 => ["id" => 366, "active" => 0, "slug" => "Asia/Baghdad", "offset" => "3", "title" => "Asia/Baghdad"],
            367 => ["id" => 367, "active" => 0, "slug" => "Asia/Bahrain", "offset" => "3", "title" => "Asia/Bahrain"],
            368 => ["id" => 368, "active" => 0, "slug" => "Asia/Beirut", "offset" => "3", "title" => "Asia/Beirut"],
            369 => ["id" => 369, "active" => 0, "slug" => "Asia/Damascus", "offset" => "3", "title" => "Asia/Damascus"],
            370 => ["id" => 370, "active" => 0, "slug" => "Asia/Famagusta", "offset" => "3", "title" => "Asia/Famagusta"],
            371 => ["id" => 371, "active" => 0, "slug" => "Asia/Gaza", "offset" => "3", "title" => "Asia/Gaza"],
            372 => ["id" => 372, "active" => 0, "slug" => "Asia/Hebron", "offset" => "3", "title" => "Asia/Hebron"],
            373 => ["id" => 373, "active" => 0, "slug" => "Asia/Jerusalem", "offset" => "3", "title" => "Asia/Jerusalem"],
            374 => ["id" => 374, "active" => 0, "slug" => "Asia/Kuwait", "offset" => "3", "title" => "Asia/Kuwait"],
            375 => ["id" => 375, "active" => 0, "slug" => "Asia/Nicosia", "offset" => "3", "title" => "Asia/Nicosia"],
            376 => ["id" => 376, "active" => 1, "slug" => "Asia/Qatar", "offset" => "3", "title" => "Asia/Qatar"],
            377 => ["id" => 377, "active" => 0, "slug" => "Asia/Riyadh", "offset" => "3", "title" => "Asia/Riyadh"],
            378 => ["id" => 378, "active" => 0, "slug" => "Europe/Athens", "offset" => "3", "title" => "Europe/Athens"],
            379 => ["id" => 379, "active" => 0, "slug" => "Europe/Bucharest", "offset" => "3", "title" => "Europe/Bucharest"],
            380 => ["id" => 380, "active" => 0, "slug" => "Europe/Chisinau", "offset" => "3", "title" => "Europe/Chisinau"],
            381 => ["id" => 381, "active" => 0, "slug" => "Europe/Helsinki", "offset" => "3", "title" => "Europe/Helsinki"],
            382 => ["id" => 382, "active" => 1, "slug" => "Europe/Istanbul", "offset" => "3", "title" => "Europe/Istanbul"],
            383 => ["id" => 383, "active" => 1, "slug" => "Europe/Kiev", "offset" => "3", "title" => "Europe/Kiev"],
            384 => ["id" => 384, "active" => 0, "slug" => "Europe/Kirov", "offset" => "3", "title" => "Europe/Kirov"],
            385 => ["id" => 385, "active" => 0, "slug" => "Europe/Mariehamn", "offset" => "3", "title" => "Europe/Mariehamn"],
            386 => ["id" => 386, "active" => 0, "slug" => "Europe/Minsk", "offset" => "3", "title" => "Europe/Minsk"],
            387 => ["id" => 387, "active" => 1, "slug" => "Europe/Moscow", "offset" => "3", "title" => "Moscow"],
            388 => ["id" => 388, "active" => 0, "slug" => "Europe/Riga", "offset" => "3", "title" => "Europe/Riga"],
            389 => ["id" => 389, "active" => 0, "slug" => "Europe/Simferopol", "offset" => "3", "title" => "Europe/Simferopol"],
            390 => ["id" => 390, "active" => 0, "slug" => "Europe/Sofia", "offset" => "3", "title" => "Europe/Sofia"],
            391 => ["id" => 391, "active" => 0, "slug" => "Europe/Tallinn", "offset" => "3", "title" => "Europe/Tallinn"],
            392 => ["id" => 392, "active" => 0, "slug" => "Europe/Uzhgorod", "offset" => "3", "title" => "Europe/Uzhgorod"],
            393 => ["id" => 393, "active" => 0, "slug" => "Europe/Vilnius", "offset" => "3", "title" => "Europe/Vilnius"],
            394 => ["id" => 394, "active" => 0, "slug" => "Europe/Volgograd", "offset" => "3", "title" => "Europe/Volgograd"],
            395 => ["id" => 395, "active" => 0, "slug" => "Europe/Zaporozhye", "offset" => "3", "title" => "Europe/Zaporozhye"],
            396 => ["id" => 396, "active" => 0, "slug" => "Indian/Antananarivo", "offset" => "3", "title" => "Indian/Antananarivo"],
            397 => ["id" => 397, "active" => 0, "slug" => "Indian/Comoro", "offset" => "3", "title" => "Indian/Comoro"],
            398 => ["id" => 398, "active" => 0, "slug" => "Indian/Mayotte", "offset" => "3", "title" => "Indian/Mayotte"],
            102 => ["id" => 102, "active" => 1, "slug" => "Asia/Baku", "offset" => "4", "title" => "Azerbaijan Standard Time"],
            400 => ["id" => 400, "active" => 1, "slug" => "Asia/Dubai", "offset" => "4", "title" => "Dubai"],
            401 => ["id" => 401, "active" => 0, "slug" => "Asia/Muscat", "offset" => "4", "title" => "Asia/Muscat"],
            402 => ["id" => 402, "active" => 0, "slug" => "Asia/Tbilisi", "offset" => "4", "title" => "Asia/Tbilisi"],
            403 => ["id" => 403, "active" => 0, "slug" => "Asia/Yerevan", "offset" => "4", "title" => "Asia/Yerevan"],
            404 => ["id" => 404, "active" => 0, "slug" => "Europe/Astrakhan", "offset" => "4", "title" => "Europe/Astrakhan"],
            405 => ["id" => 405, "active" => 0, "slug" => "Europe/Samara", "offset" => "4", "title" => "Europe/Samara"],
            406 => ["id" => 406, "active" => 0, "slug" => "Europe/Saratov", "offset" => "4", "title" => "Europe/Saratov"],
            407 => ["id" => 407, "active" => 0, "slug" => "Europe/Ulyanovsk", "offset" => "4", "title" => "Europe/Ulyanovsk"],
            408 => ["id" => 408, "active" => 0, "slug" => "Indian/Mahe", "offset" => "4", "title" => "Indian/Mahe"],
            409 => ["id" => 409, "active" => 0, "slug" => "Indian/Mauritius", "offset" => "4", "title" => "Indian/Mauritius"],
            410 => ["id" => 410, "active" => 0, "slug" => "Indian/Reunion", "offset" => "4", "title" => "Indian/Reunion"],
            411 => ["id" => 411, "active" => 0, "slug" => "Asia/Kabul", "offset" => "4.5", "title" => "Asia/Kabul"],
            412 => ["id" => 412, "active" => 0, "slug" => "Asia/Tehran", "offset" => "4.5", "title" => "Asia/Tehran"],
            413 => ["id" => 413, "active" => 0, "slug" => "Antarctica/Mawson", "offset" => "5", "title" => "Antarctica/Mawson"],
            414 => ["id" => 414, "active" => 0, "slug" => "Asia/Aqtau", "offset" => "5", "title" => "Asia/Aqtau"],
            415 => ["id" => 415, "active" => 0, "slug" => "Asia/Aqtobe", "offset" => "5", "title" => "Asia/Aqtobe"],
            416 => ["id" => 416, "active" => 0, "slug" => "Asia/Ashgabat", "offset" => "5", "title" => "Asia/Ashgabat"],
            417 => ["id" => 417, "active" => 0, "slug" => "Asia/Atyrau", "offset" => "5", "title" => "Asia/Atyrau"],
            418 => ["id" => 418, "active" => 0, "slug" => "Asia/Dushanbe", "offset" => "5", "title" => "Asia/Dushanbe"],
            419 => ["id" => 419, "active" => 0, "slug" => "Asia/Karachi", "offset" => "5", "title" => "Asia/Karachi"],
            420 => ["id" => 420, "active" => 0, "slug" => "Asia/Oral", "offset" => "5", "title" => "Asia/Oral"],
            421 => ["id" => 421, "active" => 0, "slug" => "Asia/Qyzylorda", "offset" => "5", "title" => "Asia/Qyzylorda"],
            422 => ["id" => 422, "active" => 0, "slug" => "Asia/Samarkand", "offset" => "5", "title" => "Asia/Samarkand"],
            423 => ["id" => 423, "active" => 0, "slug" => "Asia/Tashkent", "offset" => "5", "title" => "Asia/Tashkent"],
            424 => ["id" => 424, "active" => 0, "slug" => "Asia/Yekaterinburg", "offset" => "5", "title" => "Asia/Yekaterinburg"],
            425 => ["id" => 425, "active" => 0, "slug" => "Indian/Kerguelen", "offset" => "5", "title" => "Indian/Kerguelen"],
            426 => ["id" => 426, "active" => 0, "slug" => "Indian/Maldives", "offset" => "5", "title" => "Indian/Maldives"],
            427 => ["id" => 427, "active" => 0, "slug" => "Asia/Colombo", "offset" => "5.5", "title" => "Asia/Colombo"],
            428 => ["id" => 428, "active" => 0, "slug" => "Asia/Kolkata", "offset" => "5.5", "title" => "Asia/Kolkata"],
            429 => ["id" => 429, "active" => 0, "slug" => "Asia/Kathmandu", "offset" => "5.75", "title" => "Asia/Kathmandu"],
            430 => ["id" => 430, "active" => 0, "slug" => "Antarctica/Vostok", "offset" => "6", "title" => "Antarctica/Vostok"],
            431 => ["id" => 431, "active" => 0, "slug" => "Asia/Almaty", "offset" => "6", "title" => "Asia/Almaty"],
            432 => ["id" => 432, "active" => 0, "slug" => "Asia/Bishkek", "offset" => "6", "title" => "Asia/Bishkek"],
            433 => ["id" => 433, "active" => 0, "slug" => "Asia/Dhaka", "offset" => "6", "title" => "Asia/Dhaka"],
            434 => ["id" => 434, "active" => 0, "slug" => "Asia/Omsk", "offset" => "6", "title" => "Asia/Omsk"],
            435 => ["id" => 435, "active" => 0, "slug" => "Asia/Qostanay", "offset" => "6", "title" => "Asia/Qostanay"],
            436 => ["id" => 436, "active" => 0, "slug" => "Asia/Thimphu", "offset" => "6", "title" => "Asia/Thimphu"],
            437 => ["id" => 437, "active" => 0, "slug" => "Asia/Urumqi", "offset" => "6", "title" => "Asia/Urumqi"],
            438 => ["id" => 438, "active" => 0, "slug" => "Indian/Chagos", "offset" => "6", "title" => "Indian/Chagos"],
            439 => ["id" => 439, "active" => 0, "slug" => "Asia/Yangon", "offset" => "6.5", "title" => "Asia/Yangon"],
            440 => ["id" => 440, "active" => 0, "slug" => "Indian/Cocos", "offset" => "6.5", "title" => "Indian/Cocos"],
            441 => ["id" => 441, "active" => 0, "slug" => "Antarctica/Davis", "offset" => "7", "title" => "Antarctica/Davis"],
            442 => ["id" => 442, "active" => 0, "slug" => "Asia/Bangkok", "offset" => "7", "title" => "Asia/Bangkok"],
            443 => ["id" => 443, "active" => 0, "slug" => "Asia/Barnaul", "offset" => "7", "title" => "Asia/Barnaul"],
            444 => ["id" => 444, "active" => 0, "slug" => "Asia/Ho_Chi_Minh", "offset" => "7", "title" => "Asia/Ho_Chi_Minh"],
            445 => ["id" => 445, "active" => 0, "slug" => "Asia/Hovd", "offset" => "7", "title" => "Asia/Hovd"],
            446 => ["id" => 446, "active" => 0, "slug" => "Asia/Jakarta", "offset" => "7", "title" => "Asia/Jakarta"],
            447 => ["id" => 447, "active" => 0, "slug" => "Asia/Krasnoyarsk", "offset" => "7", "title" => "Asia/Krasnoyarsk"],
            448 => ["id" => 448, "active" => 0, "slug" => "Asia/Novokuznetsk", "offset" => "7", "title" => "Asia/Novokuznetsk"],
            449 => ["id" => 449, "active" => 0, "slug" => "Asia/Novosibirsk", "offset" => "7", "title" => "Asia/Novosibirsk"],
            450 => ["id" => 450, "active" => 0, "slug" => "Asia/Phnom_Penh", "offset" => "7", "title" => "Asia/Phnom_Penh"],
            451 => ["id" => 451, "active" => 0, "slug" => "Asia/Pontianak", "offset" => "7", "title" => "Asia/Pontianak"],
            452 => ["id" => 452, "active" => 0, "slug" => "Asia/Tomsk", "offset" => "7", "title" => "Asia/Tomsk"],
            453 => ["id" => 453, "active" => 0, "slug" => "Asia/Vientiane", "offset" => "7", "title" => "Asia/Vientiane"],
            454 => ["id" => 454, "active" => 0, "slug" => "Indian/Christmas", "offset" => "7", "title" => "Indian/Christmas"],
            455 => ["id" => 455, "active" => 0, "slug" => "Asia/Brunei", "offset" => "8", "title" => "Asia/Brunei"],
            456 => ["id" => 456, "active" => 0, "slug" => "Asia/Choibalsan", "offset" => "8", "title" => "Asia/Choibalsan"],
            457 => ["id" => 457, "active" => 0, "slug" => "Asia/Hong_Kong", "offset" => "8", "title" => "Asia/Hong_Kong"],
            458 => ["id" => 458, "active" => 0, "slug" => "Asia/Irkutsk", "offset" => "8", "title" => "Asia/Irkutsk"],
            459 => ["id" => 459, "active" => 0, "slug" => "Asia/Kuala_Lumpur", "offset" => "8", "title" => "Asia/Kuala_Lumpur"],
            460 => ["id" => 460, "active" => 0, "slug" => "Asia/Kuching", "offset" => "8", "title" => "Asia/Kuching"],
            461 => ["id" => 461, "active" => 0, "slug" => "Asia/Macau", "offset" => "8", "title" => "Asia/Macau"],
            462 => ["id" => 462, "active" => 0, "slug" => "Asia/Makassar", "offset" => "8", "title" => "Asia/Makassar"],
            463 => ["id" => 463, "active" => 0, "slug" => "Asia/Manila", "offset" => "8", "title" => "Asia/Manila"],
            464 => ["id" => 464, "active" => 0, "slug" => "Asia/Shanghai", "offset" => "8", "title" => "Asia/Shanghai"],
            465 => ["id" => 465, "active" => 0, "slug" => "Asia/Singapore", "offset" => "8", "title" => "Asia/Singapore"],
            466 => ["id" => 466, "active" => 0, "slug" => "Asia/Taipei", "offset" => "8", "title" => "Asia/Taipei"],
            467 => ["id" => 467, "active" => 0, "slug" => "Asia/Ulaanbaatar", "offset" => "8", "title" => "Asia/Ulaanbaatar"],
            468 => ["id" => 468, "active" => 0, "slug" => "Australia/Perth", "offset" => "8", "title" => "Australia/Perth"],
            469 => ["id" => 469, "active" => 0, "slug" => "Australia/Eucla", "offset" => "8.75", "title" => "Australia/Eucla"],
            470 => ["id" => 470, "active" => 0, "slug" => "Asia/Chita", "offset" => "9", "title" => "Asia/Chita"],
            471 => ["id" => 471, "active" => 0, "slug" => "Asia/Dili", "offset" => "9", "title" => "Asia/Dili"],
            472 => ["id" => 472, "active" => 0, "slug" => "Asia/Jayapura", "offset" => "9", "title" => "Asia/Jayapura"],
            473 => ["id" => 473, "active" => 0, "slug" => "Asia/Khandyga", "offset" => "9", "title" => "Asia/Khandyga"],
            474 => ["id" => 474, "active" => 0, "slug" => "Asia/Pyongyang", "offset" => "9", "title" => "Asia/Pyongyang"],
            475 => ["id" => 475, "active" => 0, "slug" => "Asia/Seoul", "offset" => "9", "title" => "Asia/Seoul"],
            476 => ["id" => 476, "active" => 0, "slug" => "Asia/Tokyo", "offset" => "9", "title" => "Asia/Tokyo"],
            477 => ["id" => 477, "active" => 0, "slug" => "Asia/Yakutsk", "offset" => "9", "title" => "Asia/Yakutsk"],
            478 => ["id" => 478, "active" => 0, "slug" => "Pacific/Palau", "offset" => "9", "title" => "Pacific/Palau"],
            479 => ["id" => 479, "active" => 0, "slug" => "Australia/Adelaide", "offset" => "9.5", "title" => "Australia/Adelaide"],
            480 => ["id" => 480, "active" => 0, "slug" => "Australia/Broken_Hill", "offset" => "9.5", "title" => "Australia/Broken_Hill"],
            481 => ["id" => 481, "active" => 0, "slug" => "Australia/Darwin", "offset" => "9.5", "title" => "Australia/Darwin"],
            482 => ["id" => 482, "active" => 0, "slug" => "Antarctica/DumontDUrville", "offset" => "10", "title" => "Antarctica/DumontDUrville"],
            483 => ["id" => 483, "active" => 0, "slug" => "Antarctica/Macquarie", "offset" => "10", "title" => "Antarctica/Macquarie"],
            484 => ["id" => 484, "active" => 0, "slug" => "Asia/Ust-Nera", "offset" => "10", "title" => "Asia/Ust-Nera"],
            485 => ["id" => 485, "active" => 0, "slug" => "Asia/Vladivostok", "offset" => "10", "title" => "Asia/Vladivostok"],
            486 => ["id" => 486, "active" => 0, "slug" => "Australia/Brisbane", "offset" => "10", "title" => "Australia/Brisbane"],
            487 => ["id" => 487, "active" => 0, "slug" => "Australia/Hobart", "offset" => "10", "title" => "Australia/Hobart"],
            488 => ["id" => 488, "active" => 0, "slug" => "Australia/Lindeman", "offset" => "10", "title" => "Australia/Lindeman"],
            489 => ["id" => 489, "active" => 0, "slug" => "Australia/Melbourne", "offset" => "10", "title" => "Australia/Melbourne"],
            490 => ["id" => 490, "active" => 0, "slug" => "Australia/Sydney", "offset" => "10", "title" => "Australia/Sydney"],
            491 => ["id" => 491, "active" => 0, "slug" => "Pacific/Chuuk", "offset" => "10", "title" => "Pacific/Chuuk"],
            492 => ["id" => 492, "active" => 0, "slug" => "Pacific/Guam", "offset" => "10", "title" => "Pacific/Guam"],
            493 => ["id" => 493, "active" => 0, "slug" => "Pacific/Port_Moresby", "offset" => "10", "title" => "Pacific/Port_Moresby"],
            494 => ["id" => 494, "active" => 0, "slug" => "Pacific/Saipan", "offset" => "10", "title" => "Pacific/Saipan"],
            495 => ["id" => 495, "active" => 0, "slug" => "Australia/Lord_Howe", "offset" => "10.5", "title" => "Australia/Lord_Howe"],
            496 => ["id" => 496, "active" => 0, "slug" => "Antarctica/Casey", "offset" => "11", "title" => "Antarctica/Casey"],
            497 => ["id" => 497, "active" => 0, "slug" => "Asia/Magadan", "offset" => "11", "title" => "Asia/Magadan"],
            498 => ["id" => 498, "active" => 0, "slug" => "Asia/Sakhalin", "offset" => "11", "title" => "Asia/Sakhalin"],
            499 => ["id" => 499, "active" => 0, "slug" => "Asia/Srednekolymsk", "offset" => "11", "title" => "Asia/Srednekolymsk"],
            500 => ["id" => 500, "active" => 0, "slug" => "Pacific/Bougainville", "offset" => "11", "title" => "Pacific/Bougainville"],
            501 => ["id" => 501, "active" => 0, "slug" => "Pacific/Efate", "offset" => "11", "title" => "Pacific/Efate"],
            502 => ["id" => 502, "active" => 0, "slug" => "Pacific/Guadalcanal", "offset" => "11", "title" => "Pacific/Guadalcanal"],
            503 => ["id" => 503, "active" => 0, "slug" => "Pacific/Kosrae", "offset" => "11", "title" => "Pacific/Kosrae"],
            504 => ["id" => 504, "active" => 0, "slug" => "Pacific/Norfolk", "offset" => "11", "title" => "Pacific/Norfolk"],
            505 => ["id" => 505, "active" => 0, "slug" => "Pacific/Noumea", "offset" => "11", "title" => "Pacific/Noumea"],
            506 => ["id" => 506, "active" => 0, "slug" => "Pacific/Pohnpei", "offset" => "11", "title" => "Pacific/Pohnpei"],
            507 => ["id" => 507, "active" => 0, "slug" => "Antarctica/McMurdo", "offset" => "12", "title" => "Antarctica/McMurdo"],
            508 => ["id" => 508, "active" => 0, "slug" => "Asia/Anadyr", "offset" => "12", "title" => "Asia/Anadyr"],
            509 => ["id" => 509, "active" => 0, "slug" => "Asia/Kamchatka", "offset" => "12", "title" => "Asia/Kamchatka"],
            510 => ["id" => 510, "active" => 0, "slug" => "Pacific/Auckland", "offset" => "12", "title" => "Pacific/Auckland"],
            511 => ["id" => 511, "active" => 0, "slug" => "Pacific/Fiji", "offset" => "12", "title" => "Pacific/Fiji"],
            512 => ["id" => 512, "active" => 0, "slug" => "Pacific/Funafuti", "offset" => "12", "title" => "Pacific/Funafuti"],
            513 => ["id" => 513, "active" => 0, "slug" => "Pacific/Kwajalein", "offset" => "12", "title" => "Pacific/Kwajalein"],
            514 => ["id" => 514, "active" => 0, "slug" => "Pacific/Majuro", "offset" => "12", "title" => "Pacific/Majuro"],
            515 => ["id" => 515, "active" => 0, "slug" => "Pacific/Nauru", "offset" => "12", "title" => "Pacific/Nauru"],
            516 => ["id" => 516, "active" => 0, "slug" => "Pacific/Tarawa", "offset" => "12", "title" => "Pacific/Tarawa"],
            517 => ["id" => 517, "active" => 0, "slug" => "Pacific/Wake", "offset" => "12", "title" => "Pacific/Wake"],
            518 => ["id" => 518, "active" => 0, "slug" => "Pacific/Wallis", "offset" => "12", "title" => "Pacific/Wallis"],
            519 => ["id" => 519, "active" => 0, "slug" => "Pacific/Chatham", "offset" => "12.75", "title" => "Pacific/Chatham"],
            520 => ["id" => 520, "active" => 0, "slug" => "Pacific/Apia", "offset" => "13", "title" => "Pacific/Apia"],
            521 => ["id" => 521, "active" => 0, "slug" => "Pacific/Fakaofo", "offset" => "13", "title" => "Pacific/Fakaofo"],
            522 => ["id" => 522, "active" => 0, "slug" => "Pacific/Kanton", "offset" => "13", "title" => "Pacific/Kanton"],
            523 => ["id" => 523, "active" => 0, "slug" => "Pacific/Tongatapu", "offset" => "13", "title" => "Pacific/Tongatapu"],
            524 => ["id" => 524, "active" => 0, "slug" => "Pacific/Kiritimati", "offset" => "14", "title" => "Pacific/Kiritimati"],
        ];
    }
}
