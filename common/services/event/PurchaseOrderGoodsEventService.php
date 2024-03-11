<?php
namespace common\services\event;

use common\components\HelperStamp;
use common\components\statics\Base;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\GoodsSource;
use common\models\Order;
use common\models\OrderGoods;
use common\models\purchase\PurchaseOrder;
use common\models\warehousing\OverseasGoodsShipment;
use common\services\goods\GoodsService;
use common\services\order\OrderService;
use common\services\purchase\PurchaseProposalService;
use Yii;
use yii\base\Exception;

/**
 *  商品事件服务类
 */
class PurchaseOrderGoodsEventService
{
    /**
     * 【添加订单商品】事件处理
     */
    public static function afterGoodsAdd($event)
    {
        if (empty($event->order_id)) {
            throw new Exception("事件处理异常，订单ID为空");
        }

        $order_goods = $event->order_goods_data;
        $goods_no = $order_goods['goods_no'];
        $cgoods_no = $order_goods['cgoods_no'];
        $platform_type = $order_goods['source'];
        $goods_url = $order_goods['goods_url'];
        if(empty($goods_url)) {
            $goods_source_tmp = GoodsSource::find()
                ->where(['goods_no' => $goods_no, 'platform_type' => $platform_type])->orderBy('is_main desc')->all();
            foreach ($goods_source_tmp as $source_v) {
                if (!empty($source_v['platform_url'])) {
                    $goods_url = $source_v['platform_url'];
                    break;
                }
            }
        }
        $goods_source = GoodsSource::find()
            ->where(['goods_no' => $goods_no, 'platform_type' => $platform_type, 'is_main' => 2])->all();
        $goods_source_info = [];
        $price = $order_goods['goods_price'];//商品价格不需要加10
        if (empty($goods_source)) {
            $goods_source_info = new GoodsSource();
            $goods_source_info['goods_no'] = $goods_no;
            $goods_source_info['platform_type'] = $platform_type;
            $goods_source_info['is_main'] = 2;
            $goods_source_info['price'] = $price;
        } else {
            $i = 0;
            foreach ($goods_source as $source_v) {
                if ($i == 0) {
                    $goods_source_info = $source_v;
                }
                $i++;
                if ($source_v['platform_url'] == $goods_url) {
                    $goods_source_info = $source_v;
                    break;
                }
            }
        }

        $change = false;
        $change_price = false;
        if ($goods_source_info['platform_url'] != $goods_url) {
            $goods_source_info['platform_url'] = $goods_url;
            $change = true;
        }

        if(abs($goods_source_info['price'] - $price) > 0.001) {
            $goods_source_info['price'] = $price;
            $change = true;

            //误差大于5才更新价格
            /*if ($price > $order_goods['goods_price']) {
                //$change_price = true;
            }*/
        }

        if ($goods_source_info['platform_title'] != $order_goods['goods_name']) {
            $goods_source_info['platform_title'] = $order_goods['goods_name'];
            $change = true;
        }

        if ($change) {
            $goods_source_info->save();
        }

        //价格变更
        $goods = Goods::find()->where(['goods_no' => $goods_no])->one();
        //$goods_child = GoodsChild::find()->where(['cgoods_no' => $cgoods_no])->one();

        $change_goods = false;
        $price_data = [];

        //重量大才更新
        $order_goods['goods_weight'] = $order_goods['goods_weight'] < 0.2?0.21:$order_goods['goods_weight'];
        $price_data['weight'] = $order_goods['goods_weight'];

        //颜色
        if (!empty($order_goods['goods_colour']) && $goods['colour'] != $order_goods['goods_colour']) {
            $change_goods = true;
            $goods['colour'] = $order_goods['goods_colour'];
        }

        //是否带电
        if (isset($order_goods['electric']) && $goods['electric'] != $order_goods['electric']) {
            $change_goods = true;
            $goods['electric'] = $order_goods['electric'];
        }

        //规格
        if (isset($order_goods['specification']) && $goods['specification'] != $order_goods['specification']) {
            $change_goods = true;
            $goods['specification'] = $order_goods['specification'];
        }

        //尺寸
        if (isset($order_goods['size'])) {
            $price_data['package_size'] = $order_goods['size'];
        }

        if ($goods['source_method'] == GoodsService::SOURCE_METHOD_OWN) {
            if (GoodsService::isGrab($goods['source_method_sub'])) {
                if ($price_data['weight'] > 0) {
                    $price_data['price'] = $price;
                }
            } else {
                //误差大于10%才更新价格  关联采购订单可能误差过大先不更新
                /*if ($change_price && abs($price - $goods_child['price']) > $goods_child['price'] * 0.1) {
                    $price_data['price'] = $price;
                }*/
            }

            if ($change_goods) {
                $goods->save();
            }

            if ($price_data) {
                (new GoodsService())->updateChildPrice($cgoods_no,$price_data,'采购订单');
            }

            //修改订单采购金额
            if ($change && !empty($goods['sku_no'])) {
                $all_order_goods_lists = (new PurchaseProposalService())->getOrderQuery('og.*,o.order_cost_price,o.order_profit', $goods['sku_no']);
                foreach ($all_order_goods_lists as $goods_v) {
                    $old_goods_cost_price = $goods_v['goods_cost_price'] * $goods_v['goods_num'];
                    OrderGoods::updateAll(['goods_cost_price' => $price], ['id' => $goods_v['id']]);
                    $goods_cost_price = $price * $goods_v['goods_num'];
                    $change_price = ($goods_cost_price - $old_goods_cost_price);
                    $order_data = [];
                    $order_data['order_cost_price'] = $goods_v['order_cost_price'] + $change_price;
                    $order_data['order_profit'] = $goods_v['order_profit'] - $change_price;
                    Order::updateAll($order_data, ['order_id' => $goods_v['order_id']]);
                }
            }

            //推荐物流
            (new OrderService())->recommendedLogisticsToGoods($goods['sku_no']);
        }

        //海外仓补货的
        if($order_goods['ovg_id'] > 0) {
            $oversea_goods = OverseasGoodsShipment::find()->where(['id' => $order_goods['ovg_id']])->one();
            if ($oversea_goods['num'] != $order_goods['goods_num']) {
                $oversea_goods['num'] = $order_goods['goods_num'];
            }
            $oversea_goods['status'] = OverseasGoodsShipment::STATUS_WAIT_SHIP;
            $oversea_goods['porder_id'] = $event->order_id;
            $oversea_goods['purchase_time'] = time();
            $oversea_goods->save();
        }

    }

    /**
     * 【修改订单商品】事件处理
     */
    public static function afterGoodsUpdate($event)
    {
        if (empty($event->order_id)) {
            throw new Exception("事件处理异常，订单ID为空");
        }

        $pur_order = PurchaseOrder::findOne(['order_id'=>$event->order_id]);
        $order_goods = $event->order_goods_data;
        $goods_source = GoodsSource::find()
            ->where(['goods_no' => $order_goods['goods_no'], 'platform_type' => $pur_order['source'],'is_main' => 2])->one();
        if(!empty($goods_source)){
            $goods_source->platform_title = $order_goods['goods_name'];
            $goods_source->platform_url = $order_goods['goods_url'];
            $goods_source->save();
        }
    }

    /**
     * 【删除订单商品】事件处理
     */
    public static function afterGoodsDelete($event)
    {
        if (empty($event->order_id)) {
            throw new Exception("事件处理异常，订单ID为空");
        }
    }
} 