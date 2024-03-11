<?php
namespace common\services\cache;

use Yii;

/**
 * 缓存的基类
 * Class ServiceDistrictService
 * @package common\services\cache
 */
class FunCacheService
{

    /**
     * 键值分隔符
     */
    const DELIMITER = ':';

    const DATA_CACHE_PERFIX = 'com:base_fun_cache:';

    /**
     * 默认缓存时间 10分钟
     */
    const DEFAULT_DURATION = 1000;//1000

    /**
     * 缓存时间
     * @var array
     */
    protected static $duration = [

    ];


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
                $res .= self::DELIMITER . $one;
            }
        }
        return $res;
    }

    /**
     * 获取缓存时间
     * @param $key
     * @return int|mixed
     */
    public static function getDuration($key)
    {
        return empty(self::$duration[$key]) ? self::DEFAULT_DURATION : self::$duration[$key];
    }

    /**
     * 设置缓存
     * @param $key
     * @param callable $callable
     * @return array|mixed
     */
    public static function set($key, callable $callable,$duration = null)
    {
        $prefix_key = $key;
        $params = null;
        if (is_array($key)) {
            $prefix_key = !empty($key[0]) ? $key[0] : '';
            $params = !empty($key[1]) ? $key[1] : null;
        }

        $cache_key = self::getKey($prefix_key, $params);

        if(YII_ENV != 'prod') {
            $duration = self::DEFAULT_DURATION;
        }else{
            $duration = is_null($duration) ? self::getDuration($prefix_key) : $duration;
        }

        return self::cacheData($cache_key, $callable, $duration);
    }

    /**
     * 清除缓存
     * @param $key
     * @return mixed
     */
    public static function clearOne($key)
    {
        $prefix_key = $key;
        $params = null;
        if (is_array($key)) {
            $prefix_key = !empty($key[0]) ? $key[0] : '';
            $params = !empty($key[1]) ? $key[1] : null;
        }

        $cache_key = self::getKey($prefix_key, $params);

        $cache_key = self::DATA_CACHE_PERFIX . $cache_key;

        return Yii::$app->redis->del($cache_key);
    }

    /**
     * 清除缓存
     * @param $key
     * @return mixed
     */
    public static function clear($key)
    {
        $key = self::DATA_CACHE_PERFIX . $key . '*';
        $keys = Yii::$app->redis->keys($key);
        if (!empty($keys)) {
            foreach ($keys as $key) {
                Yii::$app->redis->del($key);
            }
        }
    }

    /**
     * 设置数据缓存
     * @param $key
     * @param callable $callable
     * @param int $duration
     * @return array|mixed
     */
    protected static function cacheData($key, callable $callable, $duration = 3600)
    {
        $key = self::DATA_CACHE_PERFIX . $key;

        $data = Yii::$app->redis->get($key);
        if (!is_null($data)) {
            $value = unserialize($data);
        } else {
            $value = call_user_func($callable);
            if(!is_null($value)) {
                Yii::$app->redis->set($key, serialize($value));
                Yii::$app->redis->expire($key, $duration);
            }
        }
        return $value;
    }

}