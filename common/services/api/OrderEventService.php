<?php
namespace common\services\api;

use common\models\OrderEvent;

class OrderEventService
{

    /**
     * 添加事件
     * @param $platform
     * @param $shop_id
     * @param $order_id
     * @param $event_type
     * @param $plan_time
     * @return mixed
     * @throws \yii\base\Exception
     */
    public static function addEvent($platform,$shop_id,$order_id,$event_type,$plan_time = null)
    {
        $order_event = OrderEvent::find()->where(['platform'=>$platform,'shop_id'=>$shop_id,'order_id'=>$order_id,'event_type'=>$event_type,'status' => OrderEvent::STATUS_WAIT_RUN])->one();
        if($order_event) {
            return true;
        }
        $data = [
            'platform' => $platform,
            'shop_id' => $shop_id,
            'order_id' => $order_id,
            'event_type' => $event_type,
            'status' => OrderEvent::STATUS_WAIT_RUN,
            'plan_time' => empty($plan_time)?time():$plan_time
        ];
        return OrderEvent::add($data);
    }

}