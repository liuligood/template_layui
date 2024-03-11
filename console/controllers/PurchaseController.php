<?php
namespace console\controllers;

use common\components\statics\Base;
use common\extensions\albb\Job;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\GoodsSource;
use common\models\Order;
use common\models\OrderGoods;
use common\models\purchase\PurchaseOrder;
use common\models\purchase\PurchaseOrderGoods;
use common\services\goods\GoodsLockService;
use common\services\goods\GoodsService;
use common\services\purchase\PurchaseOrderService;
use common\services\purchase\PurchaseProposalService;
use yii\console\Controller;

class PurchaseController extends Controller
{

    public function actionTest(){
        $job = new Job();
        $albb_order_info = $job->getOrderInfo('1762552850714784368');
        var_dump($albb_order_info);
    }
    /**
     * 更新采购建议
     */
    public function actionUpdateProposal($warehouse,$sku_no = null)
    {
        $purchase_proposal = new PurchaseProposalService();
        $purchase_proposal->updatePurchaseProposal($warehouse,$sku_no);
        echo '执行成功' . "\n";
        exit;
    }

    /**
     * 更新采购订单状态
     * @throws \Exception
     * @throws \Throwable
     */
    public function actionUpdatePurchaseOrderStatus()
    {
        $purchase_order_lists = PurchaseOrder::find()->where(['source' => Base::PLATFORM_1688, 'order_status' => [
            PurchaseOrder::ORDER_STATUS_WAIT_SHIP,
            //PurchaseOrder::ORDER_STATUS_SHIPPED
        ]])->andWhere(['<','plan_time',time()])->all();
        if (empty($purchase_order_lists)) {
            return;
        }

        foreach ($purchase_order_lists as $order_v) {
            try {
                $job = new Job();
                $albb_order_info = $job->getOrderInfo($order_v['relation_no']);
            }catch (\Exception $e){
                echo date('Y-m-d H:i:s') .' '. $order_v['order_id'] .' '.$e->getMessage() ."\n";
                continue;
            }
            if (empty($albb_order_info['nativeLogistics']) || empty($albb_order_info['nativeLogistics']['logisticsItems']) || $albb_order_info['baseInfo']['status'] == 'waitbuyerpay') {
                $order_v->plan_time = time() + 58 * 60;//下次执行时间
                $order_v->save();
                continue;
            }
            //交易状态，waitbuyerpay:等待买家付款;waitsellersend:等待卖家发货;waitbuyerreceive:等待买家收货;confirm_goods:已收货;success:交易成功;cancel:交易取消;terminated:交易终止;未枚举:其他状态
            $albb_status = $albb_order_info['baseInfo']['status'];

            $change_price = false;
            if ($order_v['order_status'] == PurchaseOrder::ORDER_STATUS_WAIT_SHIP) {
                $logistics = current($albb_order_info['nativeLogistics']['logisticsItems']);
                $track_no = empty($logistics['logisticsBillNo']) ? '' : $logistics['logisticsBillNo'];
                $logistics_channels_id = empty($logistics['logisticsCompanyNo']) ? '' : $logistics['logisticsCompanyNo'];
                $delivered_time = empty($logistics['deliveredTime']) ? '' : $logistics['deliveredTime'];
                if (!empty($delivered_time)) {
                    $delivered_time = substr($delivered_time, 0, 4) . '-' .
                        substr($delivered_time, 4, 2) . '-' .
                        substr($delivered_time, 6, 2) . ' ' .
                        substr($delivered_time, 8, 2) . ':' .
                        substr($delivered_time, 10, 2) . ':' .
                        substr($delivered_time, 12, 2);
                    $delivered_time = strtotime($delivered_time);
                }

                $order_v['track_no'] = $track_no;
                $order_v['logistics_channels_id'] = $logistics_channels_id;
                $order_v['ship_time'] = empty($delivered_time) ? time() : $delivered_time;
                $order_v['order_status'] = PurchaseOrder::ORDER_STATUS_SHIPPED;

                //先按一个处理
                $purchase_order_good_count = PurchaseOrderGoods::find()->where(['order_id' => $order_v['order_id']])->count();
                if($purchase_order_good_count == 1 && count($albb_order_info['productItems']) == 1) {//只处理一个的 多个可能出现价格错乱
                    $purchase_order_good = PurchaseOrderGoods::find()->where(['order_id' => $order_v['order_id']])->one();
                    $product_items = current($albb_order_info['productItems']);
                    $product_price = $product_items['price'];//单价
                    if($product_items['entryDiscount'] < 0) {//存在优惠的情况下单价不准确
                        $product_price = $product_items['itemAmount'] / $purchase_order_good['goods_num'];
                    }else {
                        if (abs($product_items['price'] * $purchase_order_good['goods_num'] - $product_items['itemAmount']) > 0.0001) {
                            $trade_terms = current($albb_order_info['tradeTerms']);
                            if(!empty($trade_terms) && $trade_terms['payStatus'] == 2) {
                                $pay_amount = $trade_terms['phasAmount'];
                                if ($product_items['itemAmount'] == $pay_amount) {
                                    $product_price = $product_items['itemAmount'] / $purchase_order_good['goods_num'];
                                }
                            }
                        }
                    }
                    if (abs($purchase_order_good['goods_price'] - $product_price) > 0.0001) {
                        $purchase_order_good['goods_price'] = $product_price;
                        $purchase_order_good->save();
                        $change_price = true;
                    }

                    if (abs($order_v['freight_price'] - $albb_order_info['baseInfo']['shippingFee']) > 0.0001) {
                        $order_v['freight_price'] = $albb_order_info['baseInfo']['shippingFee'];
                        $change_price = true;
                    }

                    //商品来源价格修改
                    $goods_source = GoodsSource::find()
                        ->where(['goods_no' => $purchase_order_good['goods_no'], 'platform_type' => Base::PLATFORM_1688, 'is_main' => 2])->one();
                    $goods_price = $product_price;//不需要加运费10
                    if (!empty($goods_source)) {
                        if (abs($goods_source['price'] - $goods_price) > 0.0001) {
                            $goods_source->price = $goods_price;
                            $goods_source->save();

                            if(!empty($purchase_order_good['sku_no'])) {
                                //修改订单采购金额
                                $all_order_goods_lists = (new PurchaseProposalService())->getOrderQuery('og.*,o.order_cost_price,o.order_profit', $purchase_order_good['sku_no']);
                                $price = $goods_price + $order_v['freight_price']/$purchase_order_good['goods_num'];
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
                        }
                    }
                    //商品价格调整
                    if(!GoodsLockService::existLockPrice($purchase_order_good['goods_no'])) {
                        $price_data = [];
                        $price_data['price'] = $goods_price;
                        if ($price_data) {
                            (new GoodsService())->updateChildPrice($purchase_order_good['cgoods_no'], $price_data, '采购订单更新');
                        }
                    }
                }
                echo date('Y-m-d H:i:s') .' '. $order_v['order_id'] .' 已发货' ."\n";
            }

            $order_v->plan_time = time() + 24 * 60 * 58;//下次执行时间
            $order_v->save();

            //价格变更
            if ($change_price) {
                PurchaseOrderService::updateOrderPrice($order_v['order_id']);
            }

            //已完成
            /*if ($albb_status == 'confirm_goods' || $albb_status == 'success') {
                $receiving_time = empty($albb_order_info['baseInfo']['receivingTime']) ? null : $albb_order_info['baseInfo']['receivingTime'];
                if (!empty($receiving_time)) {
                    $receiving_time = substr($receiving_time, 0, 4) . '-' .
                        substr($receiving_time, 4, 2) . '-' .
                        substr($receiving_time, 6, 2) . ' ' .
                        substr($receiving_time, 8, 2) . ':' .
                        substr($receiving_time, 10, 2) . ':' .
                        substr($receiving_time, 12, 2);
                    $receiving_time = strtotime($receiving_time);
                }

                //已到货
                $purchase_order_service = new PurchaseOrderService();
                $purchase_order_service->received($order_v['order_id'], $receiving_time);
                echo date('Y-m-d H:i:s') .' '. $order_v['order_id'] .' 已到货'."\n";
            }*/
        }

    }


    /**
     * 更新采购订单物流状态
     * @throws \Exception
     */
    public function actionUpdatePurchaseLogisticsStatus()
    {
        $purchase_order_lists = PurchaseOrder::find()->where(['source' => Base::PLATFORM_1688, 'order_status' => [
            PurchaseOrder::ORDER_STATUS_SHIPPED, PurchaseOrder::ORDER_STATUS_RECEIVED
        ], 'logistics_status' => PurchaseOrder::LOGISTICS_STATUS_WAIT])->all();
        if (empty($purchase_order_lists)) {
            return;
        }

        foreach ($purchase_order_lists as $order_v) {
            try {
                $job = new Job();
                $logistics = $job->getOrderLogisticsTrace($order_v['relation_no']);
                $logistics = current($logistics);
            }catch (\Exception $e){
                echo date('Y-m-d H:i:s') .' '. $order_v['order_id'] .' '.$e->getMessage() ."\n";
                continue;
            }

            $logistics_steps = [];
            if(!empty($logistics) && !empty($logistics['logisticsSteps'])){
                $logistics_steps = $logistics['logisticsSteps'];
                $logistics_steps = array_reverse($logistics_steps);
            }

            //有物流信息
            if(!empty($logistics_steps)) {
                $order_v->logistics_status = PurchaseOrder::LOGISTICS_STATUS_ON_WAY;
                $order_v->save();
                echo date('Y-m-d H:i:s') .' '. $order_v['order_id'] ." 已出发\n";
                continue;
            }
        }

    }
}