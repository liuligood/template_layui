<?php
namespace common\services\purchase;

use common\events\OrderGoodsEvent;
use common\models\OrderStockOccupy;
use common\models\purchase\PurchaseOrder;
use common\models\purchase\PurchaseOrderGoods;
use Yii;
use yii\base\Component;
use yii\base\Exception;
use common\components\statics\Base;
use yii\helpers\ArrayHelper;

/**
 * 类目统一，订单商品服务类
 */
class PurchaseOrderGoodsService extends Component
{

    public function init()
    {
        $this->on(Base::EVENT_PURCHASE_ORDER_GOODS_ADD, ['common\services\event\PurchaseOrderGoodsEventService','afterGoodsAdd']);
        $this->on(Base::EVENT_PURCHASE_ORDER_GOODS_DELETE, ['common\services\event\PurchaseOrderGoodsEventService','afterGoodsDelete']);
        $this->on(Base::EVENT_PURCHASE_ORDER_GOODS_UPDATE, ['common\services\event\PurchaseOrderGoodsEventService','afterGoodsUpdate']);
    }

    /**
     * 添加订单商品
     * @param string $order_id 订单号
     * @param array $goods_data 商品信息数组
     * @return integer
     * @throws Exception
     */
    public function addGoods($order_id, $goods_data=[])
    {
        //必须存在的字段 验证
        $goods_data_check = [
            'goods_name', 'goods_num', 'goods_no', 'sku_no', 'cgoods_no'
        ];
        $goods_price_check = ['goods_price'];
        foreach($goods_data_check as $goods_key){
            if(!isset($goods_data[$goods_key])){
                throw new Exception('商品数组必须字段<'.$goods_key.'>不存在！请核实');
            }
        }
        foreach($goods_price_check as $price_key){
            if(!isset($goods_data[$price_key])){
                throw new Exception('商品价格必须字段<'.$price_key.'>不存在！请核实');
            }
        }
        if(empty($order_id) || empty($goods_data)){
            throw new Exception('订单商品信息为空！添加失败');
        }

        $goods_price = isset($goods_data['goods_price'])? $goods_data['goods_price'] : 0;

        $data = [
            'order_id' => $order_id,
            //商品信息
            'goods_name' => $goods_data['goods_name'],
            'goods_num' => $goods_data['goods_num'],
            'goods_no' => $goods_data['goods_no'],
            'cgoods_no' => $goods_data['cgoods_no'],
            'sku_no' => $goods_data['sku_no'],
            'goods_pic' => empty($goods_data['goods_pic'])?'':$goods_data['goods_pic'],
            'goods_price' => $goods_price,
            'goods_weight' => empty($goods_data['goods_weight'])?0:$goods_data['goods_weight'],
            'goods_url' => $goods_data['goods_url'],
            'ovg_id' => empty($goods_data['ovg_id'])?0:$goods_data['ovg_id'],
            //扩展信息
            'goods_status' => PurchaseOrderGoods::GOODS_STATUS_NORMAL,
        ];
        $id = PurchaseOrderGoods::add($data);

        $goods_data = array_merge($goods_data,$data);
        $this->trigger(Base::EVENT_PURCHASE_ORDER_GOODS_ADD, new OrderGoodsEvent([
            'order_id' => $order_id,
            'order_goods_id' => $id,
            'order_goods_data' => $goods_data,
        ]));

        return $id;
    }

    /**
     * 更新订单商品
     * @param $order_id
     * @param array $order_goods
     * @throws Exception
     */
    public function updateOrderGoods($order_id,$order_goods = [])
    {
        $purchase_order = PurchaseOrder::find()->where(['order_id'=>$order_id])->one();
        $old_order_goods = PurchaseOrderService::getOrderGoods($order_id);
        $old_ids = ArrayHelper::getColumn($old_order_goods, 'id');
        $old_order_goods = ArrayHelper::index($old_order_goods, 'id');

        $new_ids = ArrayHelper::getColumn($order_goods, 'id');
        $new_ids = array_filter($new_ids);

        //删除的商品id
        $del_ids = array_diff($old_ids, $new_ids);
        if(!empty($del_ids)) {
            PurchaseOrderGoods::updateAll(['goods_status' => PurchaseOrderGoods::GOODS_STATUS_CANCEL], ['id' => $del_ids]);

            $this->trigger(Base::EVENT_PURCHASE_ORDER_GOODS_DELETE, new OrderGoodsEvent([
                'order_id' => $order_id,
                'order_goods_id' => $del_ids,
            ]));
        }

        foreach ($order_goods as $goods_v) {
            $goods_id = $goods_v['id'];
            $goods_v['order_id'] = $order_id;
            $goods_v['goods_status'] = PurchaseOrderGoods::GOODS_STATUS_NORMAL;
            if (empty($goods_id)) {
                //添加商品
                $id = PurchaseOrderGoods::add($goods_v);
                $goods_v['source'] = $purchase_order['source'];
                $this->trigger(Base::EVENT_PURCHASE_ORDER_GOODS_ADD, new OrderGoodsEvent([
                    'order_id' => $order_id,
                    'order_goods_id' => $id,
                    'order_goods_data' => $goods_v,
                ]));
            } else {
                //修改商品
                PurchaseOrderGoods::updateOneById(['id'=>$goods_id],$goods_v);
                $goods_v['source'] = $purchase_order['source'];
                $this->trigger(Base::EVENT_PURCHASE_ORDER_GOODS_UPDATE, new OrderGoodsEvent([
                    'order_id' => $order_id,
                    'order_goods_id' => $goods_id,
                    'order_goods_data' => $goods_v,
                ]));
            }
        }

        PurchaseOrderService::updateOrderPrice($order_id);

        OrderStockOccupy::deleteAll(['purchase_order_id'=>$order_id]);
        (new PurchaseProposalService())->updatePurchaseProposalToPOrderId($order_id);
    }

}
