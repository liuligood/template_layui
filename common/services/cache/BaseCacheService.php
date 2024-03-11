<?php
namespace common\services\cache;

use Yii;

/**
 * 缓存的基类
 * Class ServiceDistrictService
 * @package common\services\cache
 */
class BaseCacheService
{

    public $cache_time = 1000;

    /**
     * 键值分隔符
     */
    const DELIMITER = ':';

    const DEFAULT_TIME = 1000;

    const DATA_CACHE_PERFIX = 'com:base_cache_data:';

    /**
     * 获取Key
     * @param  string $prefix
     * @param array $data
     * @return string
     */
    public function getKey($prefix,$data)
    {
        $res = $prefix;
        foreach ($data as $one){
            $res .= self::DELIMITER . $one;
        }
        return $res;
    }

    /**
     * 获取
     * @param string $key
     * @return array
     */
    public function get($key)
    {

        $data = Yii::$app->redis->get($key);
        if(!is_null($data)){
            return json_decode($data, true);
        }else{
            return null;
        }

    }


    /**
     * 更新或添加
     * @param $key
     * @param $data
     * @param int $cache_time
     */
    public function setCache($key,$data,$cache_time = self::DEFAULT_TIME)
    {
        Yii::$app->redis->set($key, json_encode($data));
        Yii::$app->redis->expire($key,$cache_time);
    }

    /**
     * 删除缓存
     * @param $prefix string 缓存前缀
     * @param $params array 缓存Key的参数
     */
    public function delete($prefix,$params){
        $key_rule = $this->getKey($prefix,$params). self::DELIMITER.'*';
        $this->deleteByRule($key_rule);
    }

    /**
     * 删除缓存
     * @param $key_rule string 查找Key的规则
     */
    public function deleteByRule($key_rule){
        $keys = Yii::$app->redis->keys($key_rule);
        if (!empty($keys)) {
            foreach ($keys as $key) {
                $this->deleteOne($key);
            }
        }
    }

    /**
     * 删除一个缓存
     * @param $key
     */
    public function deleteOne($key){
        return Yii::$app->redis->del($key);
    }

}