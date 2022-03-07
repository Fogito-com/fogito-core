<?php
namespace Lib;

use Lib\Auth;
use Lib\Request;
use Models\LogsAttack;

class Cache
{
    public static $data;

    /**
     * get
     *
     * @param  mixed $key
     * @return void
     */
    public static function get($key)
    {
        if (!self::$data) {
            self::connect();
        }

        return self::$data->get($key);
    }

    /**
     * set
     *
     * @param  mixed $key
     * @param  mixed $value
     * @param  mixed $time
     * @return void
     */
    public static function set($key, $value, $time)
    {
        if (!self::$data) {
            self::connect();
        }

        return self::$data->set($key, $value, $time);
    }

    /**
     * remove
     *
     * @param  mixed $key
     * @return void
     */
    public static function remove($key)
    {
        if (!self::$data) {
            self::connect();
        }

        return self::$data->delete($key);
    }

    /**
     * connect
     *
     * @return void
     */
    public static function connect()
    {
        $m = new \Memcached();
        $m->setOption(\Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
        $m->addServer('localhost', 11211);
        self::$data = $m;
    }

    /**
     * is_brute_force
     *
     * @param  mixed $key
     * @param  mixed $limits
     * @return void
     */
    public static function is_brute_force($key, $limits)
    {
        $minute_limit = $limits['minute'];
        $hour_limit   = $limits['hour'];
        $day_limit    = $limits['day'];
        $error        = false;

        if (strlen($key) > 1) {
            if ($minute_limit > 0) {
                $brute_force_key   = md5(date('mdHi') . '-' . $key);
                $brute_force_count = Cache::get($brute_force_key);
                if ($brute_force_count >= $minute_limit) {
                    $error = 'Technical error. Please try again after a minute';
                }
                self::set($brute_force_key, $brute_force_count + 1, time() + 60);
            }

            if ($hour_limit > 0 && !$error) {
                $brute_force_key   = md5(date('mdH') . '-' . $key);
                $brute_force_count = Cache::get($brute_force_key);
                if ($brute_force_count >= $day_limit) {
                    $error = 'Technical error. Please try again after an hour';
                }
                self::set($brute_force_key, $brute_force_count + 1, time() + 2 * 3600);
            }

            if ($day_limit > 0 && !$error) {
                $brute_force_key   = md5(date('md') . '-' . $key);
                $brute_force_count = Cache::get($brute_force_key);
                if ($brute_force_count >= $day_limit) {
                    $error = 'Technical error. Please try again after a day';
                }
                self::set($brute_force_key, $brute_force_count + 1, time() + 24 * 3600);
            }
        }

        if ($error) {
            $i = new LogsAttack();
            if (Auth::isAuth()) {
                $i->user_id = Auth::getId();
            }
            $i->error        = $error;
            $i->attack_count = $brute_force_count;
            $i->query        = array_slice(Request::get(), 0, 100, true);
            $i->set($i);

            return $error;
        }
        return false;
    }
}
