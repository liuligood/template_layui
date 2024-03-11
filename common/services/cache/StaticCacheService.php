<?php
namespace common\services\cache;

class StaticCacheService
{

    /**
     * 获取Key
     * @param  string $prefix
     * @param array $data
     * @return string
     */
    public static function getKey($prefix, $data)
    {
        $res = $prefix;
        if(!empty($data)) {
            foreach ($data as $one) {
                if(is_array($one)){
                    $one = implode('-', $one);
                }
                $res .= $one;
            }
        }
        return $res;
    }

    /**
     * 静态缓存
     * @param $key
     * @param callable $callable
     * @return mixed
     */
    public static function cacheData($key,callable $callable)
    {
        $prefix_key = $key;
        $params = null;
        if (is_array($key)) {
            $prefix_key = !empty($key[0]) ? $key[0] : '';
            $params = !empty($key[1]) ? $key[1] : null;
        }

        $cache_key = self::getKey($prefix_key, $params);

        static $_static;
        if (!isset($_static[$cache_key])) {
            $value = call_user_func($callable);
            $_static[$cache_key] = $value;
        }
        return $_static[$cache_key];
    }

}