<?php

namespace common\services\id;
use Yii;
use yii\base\Exception;

class IdService
{

    const ONE_ADD_COUNT = 3000;
    const LEAST_COUNT = 1000;
    const ID_REPEAT_MAX = 3;
    const STANDARD_IDS = 'yshop_ids';


    public  function getNewId()
    {
        //$key = Yii::$app->redis->rpop(self::STANDARD_IDS);
        if (empty($key)) {
            //实时生成 + 告警
            return self::getRealTimeId();
        } else {
            return $key;
        }
    }

    public static function  getRandomNumber($length = 4) {
        return rand(pow(10,($length-1)), pow(10,$length)-1);
    }

    public static function getRandomId(){
        $time = time();
        $id = '0'.substr($time,1,9);
        $random = self::getRandomNumber();
        return $id.$random;
    }

    public function checkIdExist($id){
        return true;
    }

    public function getRealTimeId($repeat_time = 0){
        $id = self::getRandomId();
        $res = $this->checkIdExist($id);
        if($res){
            if($repeat_time < self::ID_REPEAT_MAX){
                $repeat_time += 1;
                return $this->getRealTimeId($repeat_time);
            }else{
                throw new Exception('id error',2000);
            }
        }else{
            return $id;
        }
    }

    public static function addIds()
    {
        $time = time();
        $array = [];
        //避免服务器时间修改引发的问题 当前时间小于队列中时间戳 则取队列中时间戳+1
        $cur_id = Yii::$app->redis->rpop(self::STANDARD_IDS);
        if(!empty($cur_id)){
            $last_time = substr($cur_id,0,10);
            if($time <= $last_time){
                $time += $last_time + 1;
            }
        }
        for ($i = 0; $i < self::ONE_ADD_COUNT; $i++) {
            $index = sprintf('%04d',$i);
            $str = $time . $index;
            $array[] = $str;
        }
        shuffle($array);
        foreach($array as $key){
            Yii::$app->redis->rpush(self::STANDARD_IDS, $key);
        }
    }

    public static function checkIds(){
        $len = Yii::$app->redis->llen(self::STANDARD_IDS);
        if($len < self::LEAST_COUNT){
            self::addIds();
            //echo date('Y-m-d H:i:s'.time());
        }
    }

} 