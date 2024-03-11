<?php

namespace common\services\id;

use common\models\Order;
use Yii;

class OrderIdService extends IdService
{

    public function checkIdExist($id){
        $order_id = Order::ID_PREFIX.$id;
        if(Order::findOne(['order_id'=>$order_id])){
            return true;
        }
        return false;
    }

} 