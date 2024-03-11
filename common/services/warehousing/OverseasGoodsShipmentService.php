<?php
namespace common\services\warehousing;

use common\models\warehousing\OverseasGoodsShipment;

class OverseasGoodsShipmentService
{

    /**
     * 添加采购计划
     * @param $data
     * @return void
     * @throws \yii\base\Exception
     */
    public function addPurchasePlanning($data)
    {
        if(empty($data['num']) || $data['num'] < 0) {
            return false;
        }
        $where = [
            'cgoods_no' => $data['cgoods_no'],
            'warehouse_id' => $data['warehouse_id'],
            'supplier_id' => $data['supplier_id'],
            'status' => OverseasGoodsShipment::STATUS_UNCONFIRMED
        ];
        $overseas_goods_shipment = OverseasGoodsShipment::find()->where($where)->one();
        if (empty($overseas_goods_shipment)) {
            $where['num'] = $data['num'];
            OverseasGoodsShipment::add($where);
        } else {
            $overseas_goods_shipment->num = $overseas_goods_shipment['num'] + $data['num'];
            $overseas_goods_shipment->save();
        }
        return true;
    }

}