<?php defined('SYSPATH') OR die('No direct access allowed.');
// The following constants are not defined in versions of PHP prior to 5.4.
defined('JSON_UNESCAPED_SLASHES') OR define('JSON_UNESCAPED_SLASHES', 64);
defined('ENT_HTML5') OR define('ENT_HTML5', 48);
require_once (SYSPATH . 'Log.php');
/**
 * Utilities
 * @file Util.php
 */
class Util
{
    public static function request($url, &$response = null, $method = 'GET', $data = null, $userpassword = null) {
        if (!$url) return null;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if (!is_null($userpassword) && !empty($userpassword)) {
            curl_setopt($ch, CURLOPT_USERPWD, $userpassword);
        }
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        Wx_Log::debug(__METHOD__, "method: $method url: " . $url, __FILE__, __LINE__);
        if ($data) {
            if (is_array($data)) {
                $data = self::my_json_encode($data, JSON_UNESCAPED_SLASHES);
                // Elasticsearch 6 requires Content Type header be set "correctly" on PUTs.
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            }
            Wx_Log::debug(__METHOD__, "data: " . $data, __FILE__, __LINE__);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        $response = curl_exec($ch);
        $ret = true;
        if (curl_errno($ch)) {
            $ret = false;
            $response = curl_error($ch);
            Wx_Log::error(__METHOD__ , "error: " . $response, __FILE__, __LINE__);
        } else {
            Wx_Log::debug(__METHOD__ , "ok: " . $response, __FILE__, __LINE__);
        }
        curl_close($ch);
        return $ret;
    }

    /**
     * Get cached value by key
     * @param string $key the key
     * @return false for cached value not found
     */
    public static function getCache($key) {
        if (empty($key)) {
            return false;
        }

        $redis = self::getRedisInstance();
        if (!isset($redis) || !$redis) {
            return false;
        }
        try {
            $ret = $redis->get($key);
        } catch (Exception $e) {
            return false;
        }
        return $ret;
    }

    /**
     * Cache a value by key
     * @param string $key the key
     * @param string $value the value to set
     * @param string $expire [optional] the cached value will expire in $expire seconds 
     * @return true or false
     */
    public static function setCache($key, $value, $expire = null) {
        if (!$key) {
            return false;
        }

        $redis = self::getRedisInstance();
        if (!isset($redis) || !$redis) {
            return false;
        }
        $arrayKeyExist = false;
        try {
            if(is_array($value)) {
                $arrayKeyExist = $redis->exists($key);
                array_walk($value, function($v) use($redis,$key) {
                    $redis->sAdd($key, $v);
                });
            } else {
                $redis->set($key, $value);
            }
        } catch (Exception $e) {
            return false;
        }
        if (!is_null($expire) && is_int($expire) && !$arrayKeyExist) {
            try {
                $redis->expire($key, $expire);
            } catch (Exception $e) {
                return false;
            }
        }
        return true;
    }

    private static function getRedisInstance() {
        if (!isset(self::$redis)) {
            self::$redis = new Redis();
        }
        if (!isset(self::$redis)) {
            return false;
        }
        try {
            self::$redis->connect(self::$_redis_ip, self::$_redis_port);
        } catch (Exception $e) {
            return false;
        }
        return self::$redis;
    }

    /**
    * Returns the JSON representation of a value. This function supports JSON_UNESCAPED_SLASHES for versions of PHP prior to 5.4 which has a standard library function json_encode that does not support it.
    * @param mixed $value the value being encoded
    * @param int $options [optional] options for json_encode are all available
    * @return a JSON encoded string on success
    */
    private static function my_json_encode($value, $options = 0) {
        if (($options & JSON_UNESCAPED_SLASHES) != 0 && version_compare(PHP_VERSION, '5.4.0') < 0) {
          return str_replace('\/', '/', json_encode($value, $options));
        } else {
          return json_encode($value, $options);
        }
    }

    private static $redis;
    private static $_redis_ip = '127.0.0.1';
    private static $_redis_port = 6379;
}
