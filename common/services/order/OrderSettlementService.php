<?php
namespace common\services\order;

use common\components\HelperStamp;
use common\components\statics\Base;
use common\models\financial\Collection;
use common\models\ExchangeRate;
use common\models\FinancialPeriodRollover;
use common\models\FinancialPlatformSalesPeriod;
use common\models\goods_shop\GoodsShopOverseasWarehouse;
use common\models\Order;
use common\models\order\OrderRefund;
use common\models\order\OrderTransport;
use common\models\OrderGoods;
use common\models\OrderSettlement;
use common\models\Shop;
use common\models\warehousing\WarehouseProvider;
use common\services\financial\PlatformSalesPeriodService;
use common\services\sys\ExchangeRateService;
use common\services\warehousing\WarehouseService;
use yii\base\Component;

class OrderSettlementService extends Component
{
    /**
     * @param $order_id
     * @return bool
     */
    public function orderSettlement($order_id)
    {
        $order = Order::find()->where(['order_id' => $order_id])->one();
        $model = OrderSettlement::find()->where(['order_id' => $order_id])->one();
        if (empty($model)) {
            $model = new OrderSettlement();
        }
        $platform_type = $order['source'];
        $model['order_id'] = $order['order_id'];
        $model['relation_no'] = $order['relation_no'];
        $model['platform_type'] = $platform_type;
        $model['shop_id'] = $order['shop_id'];
        $model['order_time'] = $order['date'];
        $model['delivery_time'] = $order['delivery_time'];

        $freight = $order['freight_price'];
        $cost_price = $order['order_cost_price'];
        //海外仓
        $warehouse_id = $order['warehouse'];
        $warehouse_type = WarehouseService::getWarehouseProviderType($warehouse_id);
        if ($order['integrated_logistics'] == Order::INTEGRATED_LOGISTICS_NO) {//不是平台物流需要算运费成本
            if (in_array($warehouse_type ,[WarehouseProvider::TYPE_PLATFORM,WarehouseProvider::TYPE_THIRD_PARTY])) {
                $freight = 0;
                $order_goods = OrderGoods::find()->where(['order_id'=>$order['order_id']])->asArray()->all();
                foreach ($order_goods as $v){
                    $ov_warehouse = GoodsShopOverseasWarehouse::find()->where(['cgoods_no'=>$v['cgoods_no'],'shop_id'=>$order['shop_id']])->one();
                    if(!empty($ov_warehouse)) {
                        $freight += round(($ov_warehouse['start_logistics_cost'] > 0 ? $ov_warehouse['start_logistics_cost'] : $ov_warehouse['estimated_start_logistics_cost']) * $v['goods_num'],2);
                    }
                }

                //第三方需要尾程
                if($warehouse_type == WarehouseProvider::TYPE_THIRD_PARTY) {
                    $order_transport = OrderTransport::find()->where(['order_id' => $order['order_id'], 'status' => OrderTransport::STATUS_EXISTING_TRACKING])->one();
                    if ($order_transport['total_fee'] > 0) {
                        $exchange_rate = $order['exchange_rate'];
                        if ($order_transport['currency'] != $order['currency']) {
                            $exchange_rate = ExchangeRateService::getRealConversion($order_transport['currency'], 'CNY');
                        }
                        $freight += round($order_transport['total_fee'] * $exchange_rate, 2);
                    }
                }
            }
            $model['freight'] = $freight;
        }
        $model['procurement_amount'] = $order['order_cost_price'];
        $model['sales_amount'] = 0;
        $model['refund_amount'] = 0;
        $model['commission_amount'] = 0;
        $model['refund_commission_amount'] = 0;
        $model['cancellation_amount'] = 0;
        $model['other_amount'] = 0;
        $model['platform_type_freight'] = 0;
        $model['tax_amount'] = 0;
        $settlement_time = [];
        $financial_id = [];
        $currency = $order['currency'];
        $financial_operation = FinancialPeriodRollover::find()->where(['relation_no' => $order['relation_no'],'shop_id'=>$order['shop_id']])->all();
        $collection_time = 0;
        if(!empty($financial_operation)) {
            $all_financial_amount = 0;
            $refund_time = '';
            foreach ($financial_operation as $financial_v) {
                if($financial_v['collection_time'] > 0 && $financial_v['collection_time'] > $collection_time) {
                    $collection_time = $financial_v['collection_time'];
                }
                $all_financial_amount += $financial_v['amount'];
                $financial_id[] = $financial_v['financial_id'];
                $operation = PlatformSalesPeriodService::findMap($financial_v['operation']);
                $settlement_time[] = $financial_v['date'];
                switch ($operation) {
                    case PlatformSalesPeriodService::OPERATION_ONE : //销售金额
                        $model['sales_amount'] = $financial_v['amount'] + $model['sales_amount'];
                        break;
                    case PlatformSalesPeriodService::OPERATION_FOR : //退款金额
                        $model['refund_amount'] = $financial_v['amount'] + $model['refund_amount'];
                        $refund_time = $financial_v['date'];
                        break;
                    case PlatformSalesPeriodService::OPERATION_SIX: //佣金
                        $model['commission_amount'] = $financial_v['amount'] + $model['commission_amount'];
                        break;
                    case PlatformSalesPeriodService::OPERATION_SER: //退款佣金
                        $model['refund_commission_amount'] = $financial_v['amount'] + $model['refund_commission_amount'];
                        break;
                    case PlatformSalesPeriodService::OPERATION_EIG : //运费
                        $model['platform_type_freight'] = $financial_v['amount'] + $model['platform_type_freight'];
                        break;
                    case PlatformSalesPeriodService::OPERATION_FIR: //取消费用
                        $model['cancellation_amount'] = $financial_v['amount'] + $model['cancellation_amount'];
                        break;
                    case PlatformSalesPeriodService::OPERATION_TWO : //其他费用
                    default:
                        $model['other_amount'] = $financial_v['amount'] + $model['other_amount'];
                        break;
                }
            }
            $currency = $financial_operation[0]['currency'];
            if($warehouse_type == WarehouseProvider::TYPE_PLATFORM && in_array($platform_type,[Base::PLATFORM_OZON,Base::PLATFORM_WORTEN]) && $all_financial_amount > 0) {
                $model['tax_amount'] = - $all_financial_amount * 0.08;
            }
            if($model['sales_amount'] == 0) {
                $model['settlement_status'] = OrderSettlement::SETTLEMENT_STATUS_UNCONFIRMED;
                if($order['source'] == Base::PLATFORM_ALLEGRO) {//allegro销售金额为0 不算结算
                    $model['sales_amount'] = $order['order_income_price'];
                    if ($model['commission_amount'] == 0) {
                        $model['commission_amount'] = -$order['platform_fee'];
                    }
                    if (in_array($order['order_status'], [Order::ORDER_STATUS_REFUND, Order::ORDER_STATUS_CANCELLED]) && $model['refund_amount'] == 0) {
                        $refund_num = OrderRefund::find()->where(['order_id' => $order['order_id']])->select('refund_num')->scalar();
                        $model['refund_amount'] = -$refund_num;
                    }
                } else if(in_array($order['order_status'], [Order::ORDER_STATUS_REFUND, Order::ORDER_STATUS_CANCELLED])) {
                    $model['settlement_status'] = OrderSettlement::SETTLEMENT_STATUS_CONFIRMED;
                    if ($order['order_status'] == Order::ORDER_STATUS_CANCELLED) {
                        $model['freight'] = 0;
                        $model['procurement_amount'] = 0;
                        $freight = 0;
                        $cost_price = 0;
                    }
                }
                if($model['settlement_status'] == OrderSettlement::SETTLEMENT_STATUS_UNCONFIRMED){
                    $collection_time = 0;
                }
            } else {
                $model['settlement_status'] = OrderSettlement::SETTLEMENT_STATUS_CONFIRMED;
            }
            if (!empty($refund_time)) {
                if (!in_array($order['order_status'],[Order::ORDER_STATUS_CANCELLED, Order::ORDER_STATUS_REFUND])) {
                    $order['order_status'] = Order::ORDER_STATUS_REFUND;
                    $order['cancel_time'] = $refund_time;

                    if ($order->save()) {
                        $order_refund = OrderRefund::find()->where(['order_id' => $order_id])->one();
                        if (empty($order_refund)) {
                            PlatformSalesPeriodService::addOrderRefund($order_id, $model['refund_amount'], $refund_time);
                        }
                    }
                }
            }
        } else {
            if ($order['order_status'] != Order::ORDER_STATUS_FINISH && $order['order_status'] != Order::ORDER_STATUS_REFUND) {
                return false;
            }
            if (!empty($model['settlement_status']) && $model['settlement_status'] != OrderSettlement::SETTLEMENT_STATUS_UNCONFIRMED) {
                return false;
            }
            //开始结算时间之前不进行结算
            $shop = Shop::find()->where(['id'=>$order['shop_id']])->one();
            $delivery_time = $order['date'];
            if($order['delivery_time'] > 0 ) {
                $delivery_time = $order['delivery_time'];
            }
            if($order['order_status'] == Order::ORDER_STATUS_FINISH) {//订单完成的b2w使用到货时间
                if ($order['source'] == Base::PLATFORM_B2W) {
                    $delivery_time = $order['delivered_time'];
                    if (empty($delivery_time)) {
                        return false;
                    }
                }
            } else {
                $delivery_time = $order['cancel_time'];
            }
            if($shop['start_settlement_time'] > 0 && $delivery_time < $shop['start_settlement_time']) {
                return false;
            }
            $model['sales_amount'] = $order['order_income_price'];
            $model['commission_amount'] = -$order['platform_fee'];
            if($order['order_status'] == Order::ORDER_STATUS_REFUND) {
                $refund_num = OrderRefund::find()->where(['order_id' => $order['order_id']])->select('refund_num')->scalar();
                $model['refund_amount'] = -$refund_num;
            }
            $model['settlement_status'] = OrderSettlement::SETTLEMENT_STATUS_UNCONFIRMED;
        }
        $model['currency'] = $currency;
        if ($order['currency'] == $currency) {
            $model['exchange_rate'] = $order['exchange_rate'];
        } else {
            $exchange_rate = ExchangeRate::find()->where(['currency_code' => $model['currency']])->select('exchange_rate')->one();
            $model['exchange_rate'] = $exchange_rate['exchange_rate'];
        }
        if(empty($model['exchange_rate'])) {
            $model['exchange_rate'] = ExchangeRateService::getValue($model['currency']);
        }
        $total_amount = $model['sales_amount'] + $model['refund_amount'] + $model['commission_amount'] + $model['refund_commission_amount']
            + $model['cancellation_amount'] + $model['other_amount'] + $model['platform_type_freight'] + $model['tax_amount'];
        $total_amount_rmb =  round($total_amount * $model['exchange_rate'],2);
        $total_profit = $total_amount_rmb - $freight - $cost_price;
        $model['total_amount'] = $total_amount;
        $model['total_profit'] = $total_profit;
        if(!empty($financial_operation)) {
            $model['sales_period_ids'] = implode(',', array_unique($financial_id));
            $model['settlement_time'] = max($settlement_time);//结算时间为最新
        }
        //已回款的数据
        if($collection_time > 0) {
            $model['collection_time'] = $collection_time;
            $model['settlement_status'] = OrderSettlement::SETTLEMENT_STATUS_SETTLEMENT;
        }
        $result = $model->save();
        if($result) {
            if(!empty($financial_operation)) {
                FinancialPeriodRollover::updateAll(['execute_status' => 1], ['relation_no' => $order['relation_no'],'shop_id'=>$order['shop_id']]);
            }
            $order->settlement_status = HelperStamp::addStamp($order['settlement_status'], Order::SETTLEMENT_STATUS_SALE);;
            $order->save();
        }
        return $result;
    }

    /**
     * 回款状态
     * @param $financial_id
     * @param $payment_back
     * @param $collection_time
     * @return void
     */
    public function collectionStatus($financial_id,$payment_back,$collection_time = 0)
    {
        $fin = FinancialPlatformSalesPeriod::find()->where(['id'=>$financial_id])->one();
        $fin->payment_back = $payment_back == 1 ? 1:2;
        $fin->collection_time = $collection_time;
        $fin->save();

        $financial_rollover = FinancialPeriodRollover::find()->where(['financial_id'=>$financial_id])->all();
        foreach ($financial_rollover as $v){
            $v->collection_time = $collection_time;
            $v->save();

            if (empty($v['relation_no'])) {
                continue;
            }
            $order_settlement = OrderSettlement::find()->where(['relation_no'=>$v['relation_no']])->one();
            if(empty($order_settlement)) {
                continue;
            }
            $order_settlement->collection_time=$collection_time;
            $order_settlement->settlement_status = $payment_back==1?OrderSettlement::SETTLEMENT_STATUS_SETTLEMENT:OrderSettlement::SETTLEMENT_STATUS_CONFIRMED;
            $order_settlement->save();
        }
        return true;
    }

}