<?php

namespace common\services\goods;

use common\models\goods\GoodsChild;
use common\models\goods\GoodsStockDetails;
use common\models\purchase\PurchaseOrder;
use common\models\purchase\PurchaseOrderGoods;
use common\services\purchase\PurchaseOrderService;
use yii\helpers\ArrayHelper;

class GoodsStockDetailsService
{

    /**
     * 入库
     * @param $cgoods_no
     * @param $warehouse
     * @param $num
     * @param $purchase_order_id
     * @return void
     * @throws \yii\base\Exception
     */
    public function inbound($cgoods_no,$warehouse,$num,$purchase_order_id = '')
    {
        $goods_price = $this->getPrice($cgoods_no,$purchase_order_id);
        for ($i = 1; $i <= $num; $i++) {
            $data = [
                'cgoods_no' => strval($cgoods_no),
                'warehouse' => $warehouse,
                'purchase_order_id' => empty($purchase_order_id)?'':$purchase_order_id,
                //'order_id' => (string)$order_id,
                'type' => empty($purchase_order_id)?GoodsStockDetails::TYPE_ADMIN:GoodsStockDetails::TYPE_PURCHASE,
                'status' => GoodsStockDetails::STATUS_INBOUND,
                'admin_id' => !empty(\Yii::$app->user) ? strval(\Yii::$app->user->getId()) : 0,
                'inbound_time' => time()
            ];
            $data['goods_price'] = empty($goods_price)?0:$goods_price;
            //$data['outgoing_time'] = time();
            //$data['cancel_time'] = time();
            GoodsStockDetails::add($data);
        }
    }

    /**
     * 出库
     * @param $cgoods_no
     * @param $warehouse
     * @param $num
     * @param string $order_id
     * @return void
     * @throws \Exception
     */
    public function outgoing($cgoods_no,$warehouse,$num,$order_id = '')
    {
        $goods_stock_details = GoodsStockDetails::find()
            ->where(['cgoods_no' => $cgoods_no, 'warehouse' => $warehouse, 'status' => GoodsStockDetails::STATUS_INBOUND])
            ->limit($num)->all();
        $ids = ArrayHelper::getColumn($goods_stock_details, 'id');
        if (count($ids) < $num) {
            throw new \Exception('库存不足，出库失败');
        }
        $result = GoodsStockDetails::updateAll([
            'status' => GoodsStockDetails::STATUS_OUTGOING,
            'order_id' => empty($order_id) ? '' : $order_id,
            'outgoing_time' => time()
        ], [
            'status' => GoodsStockDetails::STATUS_INBOUND, 'id' => $ids
        ]);
        if ($result != $num) {
            throw new \Exception('库存不足，出库失败');
        }
    }

    /**
     * 获取价格
     * @param $cgoods_no
     * @param $purchase_order_id
     * @return float
     */
    public function getPrice($cgoods_no,$purchase_order_id = null)
    {
        if (empty($purchase_order_id)) {
            $goods_price = GoodsChild::find()->where(['cgoods_no' => $cgoods_no])->select('price')->scalar();
            return empty($goods_price) ? 0 : $goods_price;
        }
        return PurchaseOrderService::getGoodsPurchaseOrderPrice($purchase_order_id,$cgoods_no);
    }

}