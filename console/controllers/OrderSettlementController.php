<?php
namespace console\controllers;

use common\components\statics\Base;
use common\models\FinancialPeriodRollover;
use common\models\FinancialPlatformSalesPeriod;
use common\models\Order;
use common\models\OrderSettlement;
use common\models\Shop;
use common\services\financial\PlatformSalesPeriodService;
use common\services\goods\GoodsPriceService;
use common\services\order\OrderSettlementService;
use yii\console\Controller;

class OrderSettlementController extends Controller
{

    public function actionTest($order_id)
    {
        (new OrderSettlementService())->orderSettlement($order_id);
    }

    public function actionCollectionStatus()
    {
        $financial = FinancialPlatformSalesPeriod::find()->where(['payment_back' => 1])->all();
        foreach ($financial as $v) {
            $collection_time = $v['collection_time'];
            if (empty($collection_time)) {
                //$v['collection_time'] = $v['stop_data'];
                //$v->save();
                $collection_time = $v['stop_data'];
            }
            (new OrderSettlementService())->collectionStatus($v['id'], $v['payment_back'], $collection_time);
        }
    }

    /**
     * 统计金额
     * @param $platform_type
     * @return void
     */
    public function actionAmountStatistics($platform_type)
    {
        $financial = FinancialPlatformSalesPeriod::find()->where(['platform_type' => $platform_type])->all();
        foreach ($financial as $v) {
            (new PlatformSalesPeriodService())->amountStatistics($v['id']);
        }
    }

    /**
     * 昨日订单结算
     * @param $add_time
     * @param $shop_id
     * @return void
     */
    public function actionYesterdayOrder($add_time = null,$shop_id = null)
    {
        $where = [];
        $where['source'] = [Base::PLATFORM_OZON,Base::PLATFORM_B2W,Base::PLATFORM_HEPSIGLOBAL,Base::PLATFORM_ALLEGRO,Base::PLATFORM_FRUUGO,Base::PLATFORM_NOCNOC];
        if (!empty($shop_id)) {
            $where['shop_id'] = $shop_id;
        }
        if(empty($add_time)){
            $add_time = date("Y-m-d",strtotime("-1 day"));
        }
        $add_time = strtotime($add_time);
        /*$where['and'][] = ['or',[
            'and',['=','order_status',Order::ORDER_STATUS_FINISH],
            ['>=','delivery_time',$add_time]
        ],['and',['=','order_status',Order::ORDER_STATUS_REFUND],
            ['>=','cancel_time',$add_time]
        ],['and',['=','order_status',Order::ORDER_STATUS_FINISH],
            ['>=','delivered_time',$add_time]
        ]];*/
        $where['and'][] = ['in','order_status',[Order::ORDER_STATUS_SHIPPED,Order::ORDER_STATUS_FINISH,Order::ORDER_STATUS_REFUND]];
        $where['and'][] = ['>=','update_time',$add_time];
        $count = Order::dealWhere($where)->count();
        $limit = 0;
        while (true) {
            $limit ++;
            $order = Order::dealWhere($where)->offset(1000*($limit-1))->limit(1000)->all();
            if(empty($order)) {
                return;
            }
            echo $limit."/".ceil($count/1000)."\n";
            foreach ($order as $v) {
                (new OrderSettlementService())->orderSettlement($v['order_id']);
                echo $v['order_id'] . "\n";
            }
        }
    }

    /**
     * 结算
     */
    public function actionSettlement($platform_type = null,$shop_id = null)
    {
        $where = [];
        $where['execute_status'] = 0;
        if(is_null($platform_type)) {
            $platform_type = [Base::PLATFORM_OZON, Base::PLATFORM_B2W,Base::PLATFORM_HEPSIGLOBAL,Base::PLATFORM_ALLEGRO,Base::PLATFORM_FRUUGO,Base::PLATFORM_NOCNOC];
        }
        $where['platform_type'] = $platform_type;
        if (!empty($shop_id)) {
            $where['shop_id'] = $shop_id;
        }
        while (true) {
            $financial_period = FinancialPeriodRollover::find()->where($where)->limit(1000)->all();
            if(empty($financial_period)){
                break;
            }
            foreach ($financial_period as $v) {
                if (empty($v['relation_no'])) {
                    $v['execute_status'] = 1;
                    $v->save();
                    continue;
                }
                $order_id = Order::find()->where(['relation_no' => $v['relation_no'],'shop_id'=>$v['shop_id']])->select('order_id')->scalar();
                if (empty($order_id)) {
                    $v['execute_status'] = -1;
                    $v->save();
                    continue;
                }
                (new OrderSettlementService())->orderSettlement($order_id);
                echo $order_id . "\n";
            }
        }
    }

    public function actionCleanUnconfirmedOrder($platform_type,$shop_id = null)
    {
        $where = [];
        $where['platform_type'] = $platform_type;
        if (!empty($shop_id)) {
            $where['shop_id'] = $shop_id;
        }
        $where['settlement_status'] = OrderSettlement::SETTLEMENT_STATUS_UNCONFIRMED;
        $count = OrderSettlement::find()->where($where)->count();
        $limit = 0;
        while (true) {
            $limit ++;
            $order_set = OrderSettlement::find()->where($where)->offset(1000*($limit-1))->limit(1000)->all();
            if(empty($order_set)) {
                return;
            }
            echo $limit."/".ceil($count/1000)."\n";
            foreach ($order_set as $order_set_v) {

                $order = Order::find()->where(['order_id' => $order_set_v['order_id']])->one();
                $is_del = false;
                if ($order['order_status'] != Order::ORDER_STATUS_FINISH && $order['order_status'] != Order::ORDER_STATUS_REFUND) {
                    $is_del = true;
                }else {
                    //开始结算时间之前不进行结算
                    $shop = Shop::find()->where(['id' => $order['shop_id']])->one();
                    $delivery_time = $order['date'];
                    if ($order['delivery_time'] > 0) {
                        $delivery_time = $order['delivery_time'];
                    }
                    if ($order['order_status'] == Order::ORDER_STATUS_FINISH) {//订单完成的b2w使用到货时间
                        if ($order['source'] == Base::PLATFORM_B2W) {
                            $delivery_time = $order['delivered_time'];
                            if (empty($delivery_time)) {
                                $is_del = true;
                            }
                        }
                    } else {
                        $delivery_time = $order['cancel_time'];
                    }
                    if ($shop['start_settlement_time'] > 0 && $delivery_time < $shop['start_settlement_time']) {
                        $is_del = true;
                    }
                }
                if($is_del) {
                    $order_set_v->delete();
                }
                echo $order_set_v['order_id'] . "\n";
            }
        }
    }

    /**
     * 未确认订单
     * @param $platform_type
     * @param $shop_id
     * @return void
     */
    public function actionUnconfirmedOrder($platform_type,$shop_id = null)
    {
        $where = [];
        $where['source'] = $platform_type;
        if (!empty($shop_id)) {
            $where['shop_id'] = $shop_id;
        }
        $where['order_status'] = [Order::ORDER_STATUS_FINISH,Order::ORDER_STATUS_REFUND];
        $count = Order::find()->where($where)->count();
        $limit = 0;
        while (true) {
            $limit ++;
            $order = Order::find()->where($where)->offset(1000*($limit-1))->limit(1000)->all();
            if(empty($order)) {
                return;
            }
            echo $limit."/".ceil($count/1000)."\n";
            foreach ($order as $v) {
                (new OrderSettlementService())->orderSettlement($v['order_id']);
                echo $v['order_id'] . "\n";
            }
        }
    }

    /**
     * @param $platform_type
     * @param $shop_id
     * @return void
     */
    public function actionShopStartSettlementTime($platform_type,$shop_id = null)
    {
        $where = [];
        $where['platform_type'] = $platform_type;
        if (!empty($shop_id)) {
            $where['shop_id'] = $shop_id;
        }
        if($platform_type == Base::PLATFORM_ALLEGRO) {
            $shop_data = FinancialPeriodRollover::find()->select('min(date) as data,shop_id')->where($where)
                ->groupBy('shop_id')->indexBy('shop_id')->asArray()->all();
        } else {
            $shop_data = FinancialPlatformSalesPeriod::find()->select('min(data) as data,shop_id')->where($where)
                ->groupBy('shop_id')->indexBy('shop_id')->asArray()->all();
        }
        $where = [];
        $where['platform_type'] = $platform_type;
        if (!empty($shop_id)) {
            $where['id'] = $shop_id;
        }
        $shop_lists = Shop::find()->where($where)->all();
        foreach ($shop_lists as $v) {
            $start_settlement_time = 0;
            if(!empty($shop_data[$v['id']]) && $shop_data[$v['id']]['data'] > 0) {
                if ($v['platform_type'] == Base::PLATFORM_HEPSIGLOBAL) {
                    $start_settlement_time = $shop_data[$v['id']]['data'];
                } else if ($v['platform_type'] == Base::PLATFORM_ALLEGRO) {
                    $start_settlement_time = strtotime("+1 month", $shop_data[$v['id']]['data']);
                } else {
                    $start_settlement_time = strtotime("-1 month", $shop_data[$v['id']]['data']);
                }
            }else {
                if ($v['platform_type'] == Base::PLATFORM_FRUUGO) {
                    $start_settlement_time = strtotime('2023-01-01');
                }
            }
            if ($v['platform_type'] == Base::PLATFORM_NOCNOC) {
                $start_settlement_time = strtotime('2022-07-01');
            }
            $v->start_settlement_time = $start_settlement_time;
            $v->save();
        }
    }

    /**
     * 修复b2w数据
     * @return void
     */
    public function actionB2wRepair()
    {
        $where = [
            'platform_type' => Base::PLATFORM_B2W,
            'order_id' => '',
        ];
        $relation_nos = FinancialPeriodRollover::find()->where($where)
            ->groupBy('relation_no')->select('relation_no,shop_id')->limit(1000)->asArray()->all();
        foreach ($relation_nos as $v) {
            $arr = explode('-', $v['relation_no']);
            $relation_no = $v['relation_no'];
            if(count($arr) > 1){
                $relation_no = $arr[1];
            }
            $order = Order::find()->where(['shop_id'=>$v['shop_id']])
                ->andFilterWhere(['like','relation_no',$relation_no])->select(['relation_no','order_id'])->asArray()->all();
            $financial = FinancialPeriodRollover::find()->where(['relation_no'=>$v['relation_no']])->all();
            $oper = [];
            foreach ($financial as $fin_v) {
                if(empty($oper[$fin_v['operation']])) {
                    $oper[$fin_v['operation']] = 0;
                }
                $oper[$fin_v['operation']] += 1;
                $order_info = current($order);
                if(!empty($order[$oper[$fin_v['operation']] - 1])){
                    $order_info = $order[$oper[$fin_v['operation']] - 1];
                }
                if(!empty($order_info['relation_no'])){
                    $fin_v['relation_no'] = $order_info['relation_no'];
                    $fin_v['order_id'] = $order_info['order_id'];
                    $fin_v->save();
                    echo $fin_v['id'].",".$v['relation_no'].",".$fin_v['relation_no']."\n";
                }else{
                    echo $fin_v['id'].",".$v['relation_no'].",".$fin_v['relation_no']."###\n";
                }
            }
        }
    }

    /**
     * 商品物流价格
     * @return void
     */
    public function actionGoodsLogisticsPrice()
    {
        (new GoodsPriceService())->setLogisticsPriceToSalesPeriod();
        echo '执行完成'."\n";
    }

}