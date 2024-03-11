<?php

namespace common\services\id;

use common\models\purchase\PurchaseOrder;
use Yii;

class PurchaseOrderIdService extends IdService
{

    public function checkIdExist($id)
    {
        $order_id = PurchaseOrder::ID_PREFIX . $id;
        if (PurchaseOrder::findOne(['order_id' => $order_id])) {
            return true;
        }
        return false;
    }

}