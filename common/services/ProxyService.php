<?php

namespace common\services;


use common\components\CommonUtil;

class ProxyService
{

    /**
     * 代理地址
     */
    public static function getOneProxy1($re = false,$regionid = '')
    {
        $cache = \Yii::$app->redis;
        $cp_key = md5(gethostname());
        $cache_ip_key = 'com::cloudam::ips'.$cp_key.(empty($regionid)?'':(':r'.$regionid));
        $ip = $cache->get($cache_ip_key);
        if (empty($ip) || $re) {
            //加锁
            $lock = 'com::cloudam::ip:key'.$cp_key;
            $request_num = $cache->incrby($lock,1);
            if($request_num == 1) {
                $cache->expire($lock, 40);
            }
            if($request_num > 1){
                sleep(1);
                return self::getOneProxy($re,$regionid);
            }

            $ip_json = GrabService::getCurl('http://api.tianqiip.com/getip?secret=p0nukas32yjp3dwt&type=json&num=1&time=3&ts=1&port=1');
            CommonUtil::logs($ip_json, 'ip_cloudam');
            $ip_arr = json_decode($ip_json, true);
            if (empty($ip_arr) || !empty($ip_arr['data'])) {
                $cache->del($lock);
                return false;
            }
            //$ip = current($ip_arr);
            $ip = current($ip_json['data']);
            $ip = $ip['ip'] .':'.$ip['port'];
            $cache->setex($cache_ip_key, 60 * 2 + 30, $ip);
            $cache->expire($lock, 4);
            //$cache->set($cache_ip_key, $ip, 60 * 4 + 30);
            //$cache->delete($lock);
        }
        if (!empty($ip)) {
            $ip_arr = json_decode($ip, true);
            $random_keys = array_rand($ip_arr, 1);
            $ip = $ip_arr[$random_keys];
        }
        return $ip;
    }

    /**
     * 代理地址
     */
    public static function getOneProxy($re = false,$regionid = '')
    {
        $cache = \Yii::$app->redis;
        $cp_key = md5(gethostname());
        $cache_ip_key = 'com::cloudam::ips'.$cp_key.(empty($regionid)?'':(':r'.$regionid));
        $ip = $cache->get($cache_ip_key);
        if (empty($ip) || $re) {
            //加锁
            $lock = 'com::cloudam::ip:key'.$cp_key;
            $request_num = $cache->incrby($lock,1);
            if($request_num == 1) {
                $cache->expire($lock, 40);
            }
            if($request_num > 1){
                sleep(1);
                return self::getOneProxy($re,$regionid);
            }

            if(empty($regionid)){
                $regionid = 'europe';
            } else {
                $regionid = 'global';
            }
            $ip_json = GrabService::getCurl('https://www.cloudam.cn/ip/takeip/oo9PaMloNOAxVOCcGMInsgADs7VmEQWb?protocol=proxy&regionid='.$regionid.'&needpwd=false&duplicate=true&amount=1&type=json');
            CommonUtil::logs($ip_json, 'ip_cloudam');
            $ip_arr = json_decode($ip_json, true);
            if (empty($ip_arr) || !empty($ip_arr['errorCode'])) {
                $cache->del($lock);
                return false;
            }
            //$ip = current($ip_arr);
            $ip = $ip_json;
            $cache->setex($cache_ip_key, 60 * 4 + 30, $ip);
            $cache->expire($lock, 4);
            //$cache->set($cache_ip_key, $ip, 60 * 4 + 30);
            //$cache->delete($lock);
        }
        if (!empty($ip)) {
            $ip_arr = json_decode($ip, true);
            $random_keys = array_rand($ip_arr, 1);
            $ip = $ip_arr[$random_keys];
        }
        return $ip;
    }

}