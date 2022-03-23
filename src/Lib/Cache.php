<?php
namespace Fogito\Lib;

class Cache
{
    public static $data;

    public static function get($key)
    {
        if(!self::$data)
            self::connect();
        return self::$data->get($key);
    }


    public static function set($key, $value, $time)
    {
        if(!self::$data)
            self::connect();
        return self::$data->set($key, $value, $time);
    }

    public static function remove($key)
    {
        if(!self::$data)
            self::connect();
        return self::$data->delete($key);
    }

    public static function connect()
    {
        $M = new \Memcached();
        $M->setOption(\Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
        $M->addServer('localhost', 11211);

        self::$data = $M;
    }

    public static function is_brute_force($key, $limits)
    {
        $minute_limit   = $limits["minute"];
        $hour_limit     = $limits["hour"];
        $day_limit      = $limits["day"];
        $error = false;
        if(strlen($key) > 1)
        {
            ######################### START BRUTE FORCE CHECK IN MINUTE ###################
            if($minute_limit > 0)
            {
                $brute_force_key    = md5(date("mdHi")."-".$key);
                $brute_force_count  = Cache::get($brute_force_key);
                if($brute_force_count >= $minute_limit)
                {
                    $error = "Technical error. Please try again after a minute";
                }
                Cache::set($brute_force_key, $brute_force_count+1, time()+60);
            }
            ######################### END BRUTE FORCE CHECK IN MINUTE ###################

            ######################### START BRUTE FORCE CHECK IN HOUR ###################
            if($hour_limit > 0 && !$error)
            {
                $brute_force_key    = md5(date("mdH")."-".$key);
                $brute_force_count  = Cache::get($brute_force_key);
                if($brute_force_count >= $day_limit)
                {
                    $error = "Technical error. Please try again after an hour";
                }
                Cache::set($brute_force_key, $brute_force_count+1, time()+2*3600);
            }
            ######################### END BRUTE FORCE CHECK IN HOUR ###################

            ######################### START BRUTE FORCE CHECK IN DAY ###################
            if($day_limit > 0 && !$error)
            {
                $brute_force_key    = md5(date("md")."-".$key);
                $brute_force_count  = Cache::get($brute_force_key);
                if($brute_force_count >= $day_limit)
                {
                    $error = "Technical error. Please try again after a day";
                }
                Cache::set($brute_force_key, $brute_force_count+1, time()+24*3600);
            }
            ######################### END BRUTE FORCE CHECK IN DAY ###################

            //if($error) $error .= ", in minute: ".$brute_force_m_count.", in day: ".$brute_force_h_count.", key: ".$key;
        }
        return ($error) ? true: false;
    }
}