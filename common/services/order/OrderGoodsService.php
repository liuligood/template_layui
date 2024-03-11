<?php
namespace common\services\order;


use common\events\OrderGoodsEvent;
use common\models\OrderGoods;
use common\services\purchase\PurchaseProposalService;
use Yii;
use yii\base\Component;
use yii\base\Exception;
use common\components\statics\Base;
use yii\helpers\ArrayHelper;

/**
 * 类目统一，订单商品服务类
 */
class OrderGoodsService extends Component
{

    public function init()
    {
        $this->on(Base::EVENT_ORDER_GOODS_ADD, ['common\services\event\OrderGoodsEventService','afterGoodsAdd']);
        $this->on(Base::EVENT_ORDER_GOODS_DELETE, ['common\services\event\OrderGoodsEventService','afterGoodsDelete']);
        $this->on(Base::EVENT_ORDER_GOODS_UPDATE, ['common\services\event\OrderGoodsEventService','afterGoodsUpdate']);
    }

    /**
     * 添加订单商品
     * @param string $order_id  订单号
     * @param array $goods_data 商品信息数组
     * @param array $goods_price 商品价格数组
     * @return integer
     */
    public function addGoods($order_id, $goods_data=[])
    {
        //必须存在的字段 验证
        $goods_data_check = [
            'goods_name', 'goods_num', 'platform_type','platform_asin','source_method',
        ];
        $goods_price_check = ['goods_income_price', 'goods_cost_price'];
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

        $goods_income_price = isset($goods_data['goods_income_price'])? $goods_data['goods_income_price'] : 0;
        $goods_cost_price = isset($goods_data['goods_cost_price'])? $goods_data['goods_cost_price'] : 0;

        $data = [
            'order_id' => $order_id,
            //商品信息
            'goods_name' => $goods_data['goods_name'],
            'goods_num' => $goods_data['goods_num'],
            'platform_type' => $goods_data['platform_type'],
            'platform_asin' => $goods_data['platform_asin'],
            'source_method' => $goods_data['source_method'],
            'goods_no' => isset($goods_data['goods_no'])?$goods_data['goods_no']:'',
            'cgoods_no' => isset($goods_data['cgoods_no'])?$goods_data['cgoods_no']:'',
            'goods_pic' => empty($goods_data['goods_pic'])?'':$goods_data['goods_pic'],
            'goods_specification' => empty($goods_data['goods_specification'])?'':$goods_data['goods_specification'],
            'goods_income_price' => $goods_income_price,
            'goods_cost_price' => $goods_cost_price,
            'has_buy_goods' => empty($goods_data['has_buy_goods'])?0:1,
            'out_stock' => empty($goods_data['out_stock'])?0:$goods_data['out_stock'],
            'error_con' => empty($goods_data['error_con'])?0:$goods_data['error_con'],
            //扩展信息
            //'goods_status' => $status,
        ];
        $id = OrderGoods::add($data);

        $this->trigger(Base::EVENT_ORDER_GOODS_ADD, new OrderGoodsEvent([
            'order_id' => $order_id,
            'order_goods_id' => $id,
            'order_goods_data' => $data,
        ]));

        return $id;
    }

    /**
     * 更新订单商品
     * @param $order_id
     * @param array $order_goods
     */
    public function updateOrderGoods($order_id,$order_goods = [])
    {
        $old_order_goods = OrderService::getOrderGoods($order_id);
        $old_ids = ArrayHelper::getColumn($old_order_goods, 'id');
        $old_order_goods = ArrayHelper::index($old_order_goods, 'id');

        $new_ids = ArrayHelper::getColumn($order_goods, 'id');
        $new_ids = array_filter($new_ids);

        $price_change = false;
        //删除的商品id
        $del_ids = array_diff($old_ids, $new_ids);
        if(!empty($del_ids)) {
            $price_change = true;
            OrderGoods::updateAll(['goods_status' => OrderGoods::GOODS_STATUS_CANCEL], ['id' => $del_ids]);

            $this->trigger(Base::EVENT_ORDER_GOODS_DELETE, new OrderGoodsEvent([
                'order_id' => $order_id,
                'order_goods_id' => $del_ids,
            ]));
        }

        foreach ($order_goods as $goods_v) {
            $goods_id = $goods_v['id'];
            $goods_v['order_id'] = $order_id;
            if (empty($goods_id)) {
                $price_change = true;
                $goods_v['goods_status'] = OrderGoods::GOODS_STATUS_UNCONFIRMED;
                if(!empty($goods_v['platform_type']) && !empty($goods_v['platform_asin'])) {
                    $goods_v['goods_status'] = OrderGoods::GOODS_STATUS_NORMAL;
                }
                //添加商品
                $id = OrderGoods::add($goods_v);

                $this->trigger(Base::EVENT_ORDER_GOODS_ADD, new OrderGoodsEvent([
                    'order_id' => $order_id,
                    'order_goods_id' => $id,
                    'order_goods_data' => $goods_v,
                ]));
            } else {
                if($goods_v['goods_num'] != $old_order_goods[$goods_id]['goods_num'] ||
                    $goods_v['goods_income_price'] != $old_order_goods[$goods_id]['goods_income_price'] ||
                    $goods_v['goods_cost_price'] != $old_order_goods[$goods_id]['goods_cost_price']
                ){
                    $price_change = true;
                }


                if(!empty($goods_v['platform_type']) && !empty($goods_v['platform_asin'])) {
                    $goods_v['goods_status'] = OrderGoods::GOODS_STATUS_NORMAL;
                }
                //修改商品
                OrderGoods::updateOneById(['id'=>$goods_id],$goods_v);

                $this->trigger(Base::EVENT_ORDER_GOODS_UPDATE, new OrderGoodsEvent([
                    'order_id' => $order_id,
                    'order_goods_id' => $goods_id,
                    'order_goods_data' => $goods_v,
                    'old_order_goods_data' => $old_order_goods[$goods_id]
                ]));
            }
        }
        (new PurchaseProposalService())->updatePurchaseProposalToOrderId($order_id,true);
        //if($price_change) {
            OrderService::updateOrderPrice($order_id);
        //}
    }

}
