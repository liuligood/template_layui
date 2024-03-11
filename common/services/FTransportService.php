<?php
namespace common\services;

use common\components\statics\Base;
use common\services\goods\GoodsService;
use common\services\transport\TransportService;
use yii\base\Exception;
use yii\helpers\ArrayHelper;

/**
 * 物流服务工厂类
 * @package common\models\service
 */
class FTransportService {

    /**
     * @param $transport_code
     * @throws Exception
     * @return \common\services\transport\BaseTransportService
     */
    public static function factory($transport_code){
        $transport = TransportService::getTransportInfo($transport_code);
        if(empty($transport)){
            throw new Exception("不存在该物流商",8900);
        }

        $transport_class_name = 'common\services\transport\\'.ucfirst($transport_code).'TransportService';
        if(class_exists($transport_class_name)){
            $param = json_decode($transport['param'],true);
            $transport_class =  new $transport_class_name($param);
            return $transport_class->setTransportCode($transport_code);
        }

        throw new Exception("找不到BaseTransportService类",8900);
    }

}