<?php

namespace Fogito\Lib;

use Fogito\Config;
use Fogito\Http\Response;

class Cache
{
    public static $data;

    public static function get($key)
    {
        if (!self::$data)
            self::connect();
        if (self::$data)
            return self::$data->get($key);
        return false;
    }


    public static function set($key, $value, $time=0)
    {
        if (!self::$data)
            self::connect();
        if (self::$data)
        {
            $server = self::getServer();
            if ($server && $server->type === 'redis')
            {
                if($time>0)
                {
                    return self::$data->setex($key, $time, $value);
                }
                else
                {
                    return self::$data->set($key, $value);
                }
            }
            else
            {
                return self::$data->set($key, $value, $time);
            }
        }
        return false;
    }

    public static function remove($key)
    {
        if (!self::$data)
            self::connect();
        if (self::$data)
            return self::$data->delete($key);
        return false;
    }

    public static function getServer()
    {
        $server = false;
        if(Config::getData("cache_server") && Config::getData("cache_servers"))
            $server = Config::getData("cache_servers")->{Config::getData("cache_server")};
        return $server;
    }
    public static function connect()
    {
        $server = self::getServer();

        if ($server && $server->type === 'redis')
        {
            if (class_exists('Redis'))
            {
                $db = new \Redis();
                try
                {
                    $db->connect(
                        $server ? $server->host : '127.0.0.2',
                        $server ? $server->port : 6379
                    );
                    if ($server && $server->password)
                        $db->auth('password');
                    if ($db->ping())
                    {
                        self::$data = $db;
                    }
                    else
                    {
                        Response::error("Cache server error: 22");
                    }
                } catch (\RedisException $e){
                    Response::error("Cache server error: 22");
                }
            }
            else
            {
                Response::error("Cache server error: 21");
            }
        }
        else if(!$server || $server->type === 'memcache')
        {
            if (class_exists('Memcached'))
            {
                try
                {
                    $db = new \Memcached();
                    $db->setOption(\Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
                    $db->addServer(
                        $server ? $server->host : 'localhost',
                        $server ? $server->port : 11211
                    );
                } catch (\MemcachedException $e){
                    Response::error("Cache server error: 12");
                }

                self::$data = $db;
            }
            else
            {
                Response::error("Cache server error: 11");
            }
        }


        return false;
    }

    public static function is_brute_force($key, $limits)
    {
        $minute_limit = $limits["minute"];
        $hour_limit = $limits["hour"];
        $day_limit = $limits["day"];
        $error = false;
        if (strlen($key) > 1)
        {
            ######################### START BRUTE FORCE CHECK IN MINUTE ###################
            if ($minute_limit > 0)
            {
                $brute_force_key = md5(date("mdHi") . "-" . $key);
                $brute_force_count = Cache::get($brute_force_key);
                if ($brute_force_count >= $minute_limit)
                {
                    $error = "Technical error. Please try again after a minute";
                }
                Cache::set($brute_force_key, $brute_force_count + 1, time() + 60);
            }
            ######################### END BRUTE FORCE CHECK IN MINUTE ###################

            ######################### START BRUTE FORCE CHECK IN HOUR ###################
            if ($hour_limit > 0 && !$error)
            {
                $brute_force_key = md5(date("mdH") . "-" . $key);
                $brute_force_count = Cache::get($brute_force_key);
                if ($brute_force_count >= $day_limit)
                {
                    $error = "Technical error. Please try again after an hour";
                }
                Cache::set($brute_force_key, $brute_force_count + 1, time() + 2 * 3600);
            }
            ######################### END BRUTE FORCE CHECK IN HOUR ###################

            ######################### START BRUTE FORCE CHECK IN DAY ###################
            if ($day_limit > 0 && !$error)
            {
                $brute_force_key = md5(date("md") . "-" . $key);
                $brute_force_count = Cache::get($brute_force_key);
                if ($brute_force_count >= $day_limit)
                {
                    $error = "Technical error. Please try again after a day";
                }
                Cache::set($brute_force_key, $brute_force_count + 1, time() + 24 * 3600);
            }
            ######################### END BRUTE FORCE CHECK IN DAY ###################

            //if($error) $error .= ", in minute: ".$brute_force_m_count.", in day: ".$brute_force_h_count.", key: ".$key;
        }
        return ($error) ? true : false;
    }
}