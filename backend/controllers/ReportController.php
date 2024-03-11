<?php

namespace backend\controllers;

use backend\models\AdminUser;
use backend\models\search\ReportUserCountSearch;
use common\components\HelperStamp;
use common\components\statics\Base;
use common\models\FinancialPeriodRollover;
use common\models\FinancialPlatformSalesPeriod;
use common\models\Goods;
use common\models\Order;
use common\models\PromoteCampaign;
use common\models\PromoteCampaignDetails;
use common\models\ReportUserCount;
use common\models\Shop;
use common\models\User;
use common\services\ShopService;
use Yii;
use common\base\BaseController;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class ReportController extends BaseController
{
    /**
     * @routeName 订单统计
     * @routeDescription 订单统计
     */
    public function actionOrderCount()
    {
        $req = Yii::$app->request;
        $start_date = $req->get('start_date', date('Y-m-d'));
        $end_date = $req->get('end_date', date('Y-m-d'));

        $where = [];
        $order_count = Order::find()->select('shop_id,source,count(*) as cut,sum(order_income_price * exchange_rate) as income_price,
        sum(case when order_status = 40 or order_cost_price<=0 or freight_price <=0 then 0 else order_profit end) as order_profit,
        sum(case when order_status = 40 or order_cost_price<=0 or freight_price <=0 then 0 else (order_cost_price + freight_price) end) as cost_price')
            ->where($where)
            ->andWhere(['>=', 'date', strtotime($start_date)])
            ->andWhere(['<', 'date', strtotime($end_date) + 86400])
            ->groupBy('shop_id')->asArray()->all();
        $all_count = 0;
        $all_income_price = 0;
        $all_order_profit = 0;
        $all_cost = 0;
        $lists = [];
        foreach ($order_count as $v) {
            if(empty($lists[$v['source']])){
                $lists[$v['source']]['source'] = $v['source'];
                $lists[$v['source']]['cut'] = 0;
                $lists[$v['source']]['shop'] = [];
                $lists[$v['source']]['income_price'] = 0;
                $lists[$v['source']]['order_profit'] = 0;
                $lists[$v['source']]['cost_price'] = 0;
            }
            $income_price = $v['income_price'];
            $order_profit = $v['order_profit'];
            $cost_price = $v['cost_price'];
            $lists[$v['source']]['cut'] += $v['cut'];
            $lists[$v['source']]['shop'][] = $v;
            $lists[$v['source']]['income_price'] += $income_price;
            $lists[$v['source']]['order_profit'] += $order_profit;
            $lists[$v['source']]['cost_price'] += $cost_price;
            $all_count += $v['cut'];
            $all_income_price += $income_price;
            $all_order_profit += $order_profit;
            $all_cost += $cost_price;
        }
        $key_arrays = [];
        foreach ($lists as $v) {
            $key_arrays[] = $v['cut'];
        }
        array_multisort($key_arrays,SORT_DESC,$lists);
        $shop = Shop::find()->select('id,name')->asArray()->all();
        $shop = ArrayHelper::map($shop,'id','name');
        return $this->render('order_count', [
            'searchModel' => [
                'start_date' => $start_date,
                'end_date' => $end_date,
            ],
            'all_count' => $all_count,
            'all_income_price' => $all_income_price,
            'all_order_profit' => $all_order_profit,
            'all_cost' => $all_cost,
            'order_count' => $lists,
            'shop_map' => $shop,
        ]);
    }
    /**
     * @routeName 账期统计
     * @routeDescription 账期统计
     */
    public function actionFinancialCount()
    {
        $req = Yii::$app->request;
        $start_date = $req->get('start_date', date('Y-m-d'));
        $end_date = $req->get('end_date', date('Y-m-d'));
        $platform_type = $req->get('platform_type');
        $where = [];
        $name = '全部';
        $py = 1;
        $all_sales_amount = 0;
        $all_refund_amount = 0;
        $all_commission_amount = 0;
        $all_promotions_amount = 0;
        $all_freight =0;
        $all_refund_commission_amount = 0;
        $all_advertising_amount = 0;
        $all_cancellation_amount = 0;
        $all_goods_services_amount = 0;
        $all_order_amount = 0;
        $all_payment_amount = 0;
        $all_premium =0;
        $all_payment_amount_no = 0;
        $lists = [];
        $financial_count = FinancialPlatformSalesPeriod::find()->select('
        shop_id,
        platform_type,
        sum(sales_amount) as sales_amount,
        sum(refund_amount) as refund_amount,
        sum(commission_amount) as commission_amount,
        sum(payment_amount) as payment_amount,
        sum(promotions_amount) as promotions_amount,
        sum(freight) as freight,
        sum(refund_commission_amount) as refund_commission_amount,
        sum(advertising_amount) as advertising_amount,
        sum(cancellation_amount) as cancellation_amount,
        sum(goods_services_amount) as goods_services_amount,
        sum(order_amount) as order_amount,
        sum(premium) as premium,
        sum(case when payment_back = 1 then 0 else payment_amount end) as payment_amount_no,')
            ->where($where)
            ->andWhere(['>=', 'data', strtotime($start_date)])
            ->andWhere(['<', 'data', strtotime($end_date) + 86400])
            ->groupBy('shop_id')->asArray()->all();
        if($platform_type){
            $py = 2;
            $where['platform_type'] = $platform_type;
            $name = Base::$platform_maps[$platform_type];
            $ids = Shop::find()->where(['platform_type'=>$platform_type])->select('id')->asArray()->all();
            foreach ($ids as $id){
                $a['shop_id'] = $id['id'];
                $a['sales_amount'] = 0;
                $a['refund_amount'] = 0;
                $a['commission_amount'] = 0;
                $a['payment_amount'] = 0;
                $a['promotions_amount'] = 0;
                $a['freight'] = 0;
                $a['refund_commission_amount'] = 0;
                $a['advertising_amount'] = 0;
                $a['cancellation_amount'] = 0;
                $a['goods_services_amount'] = 0;
                $a['order_amount'] = 0;
                $a['premium'] = 0;
                $a['payment_amount_no'] = 0;
                array_push($lists,$a);
            }
                foreach ($lists as &$list){
                    $where['shop_id'] = $list['shop_id'];
                    $financial_count = FinancialPlatformSalesPeriod::find()->select('
        sum(sales_amount) as sales_amount,
        sum(refund_amount) as refund_amount,
        sum(commission_amount) as commission_amount,
        sum(payment_amount) as payment_amount,
        sum(promotions_amount) as promotions_amount,
        sum(freight) as freight,
        sum(refund_commission_amount) as refund_commission_amount,
        sum(advertising_amount) as advertising_amount,
        sum(cancellation_amount) as cancellation_amount,
        sum(goods_services_amount) as goods_services_amount,
        sum(order_amount) as order_amount,
        sum(premium) as premium,
        sum(case when payment_back = 1 then 0 else payment_amount end) as payment_amount_no,')
                        ->where($where)
                        ->andWhere(['>=', 'data', strtotime($start_date)])
                        ->andWhere(['<', 'data', strtotime($end_date) + 86400])
                        ->asArray()->all();
                    if(!($financial_count[0]['sales_amount'])){
                    }else{
                $sales_amount = $financial_count[0]['sales_amount'];
                $refund_amount = $financial_count[0]['refund_amount'];
                $commission_amount = $financial_count[0]['commission_amount'];
                $payment_amount =$financial_count[0]['payment_amount'];
                $promotions_amount = $financial_count[0]['promotions_amount'];
                $freight =$financial_count[0]['freight'];
                $refund_commission_amount = $financial_count[0]['refund_commission_amount'];
                $advertising_amount = $financial_count[0]['advertising_amount'];
                $cancellation_amount = $financial_count[0]['cancellation_amount'];
                $goods_services_amount = $financial_count[0]['goods_services_amount'];
                $order_amount = $financial_count[0]['order_amount'];
                $premium =$financial_count[0]['premium'];
                $payment_amount_no = $financial_count[0]['payment_amount_no'];
                    $list['sales_amount'] = $sales_amount;
                    $list['refund_amount'] = $refund_amount;
                    $list['commission_amount'] = $commission_amount;
                    $list['payment_amount'] = $payment_amount;
                    $list['promotions_amount'] = $promotions_amount;
                    $list['freight'] = $freight;
                    $list['refund_commission_amount'] = $refund_commission_amount;
                    $list['advertising_amount'] = $advertising_amount;
                    $list['cancellation_amount'] = $cancellation_amount;
                    $list['goods_services_amount'] = $goods_services_amount;
                    $list['order_amount'] = $order_amount;
                    $list['premium'] = $premium;
                    $list['payment_amount_no'] = $payment_amount_no;
                $all_sales_amount += $sales_amount;
                $all_refund_amount += $refund_amount;
                $all_commission_amount += $commission_amount;
                $all_payment_amount +=$payment_amount;
                $all_promotions_amount += $promotions_amount;
                $all_freight +=$freight;
                $all_refund_commission_amount += $refund_commission_amount;
                $all_advertising_amount += $advertising_amount;
                $all_cancellation_amount += $cancellation_amount;
                $all_goods_services_amount += $goods_services_amount;
                $all_order_amount += $order_amount;
                $all_premium +=$premium;
                $all_payment_amount_no += $payment_amount_no;}}
                $shop = Shop::find()->select('id,name')->asArray()->all();
                $shop = ArrayHelper::map($shop,'id','name');
            return $this->render('financial_count', [
                'searchModel' => [
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'platform_type' => $platform_type
                ],
                'name' => $name,
                'all_sales_amount' => $all_sales_amount,
                'all_refund_amount' => $all_refund_amount,
                'all_commission_amount' => $all_commission_amount,
                'all_payment_amount' => $all_payment_amount,
                'all_promotions_amount' => $all_promotions_amount,
                'all_freight' => $all_freight,
                'all_refund_commission_amount' => $all_refund_commission_amount,
                'all_advertising_amount' => $all_advertising_amount,
                'all_cancellation_amount' => $all_cancellation_amount,
                'all_goods_services_amount' => $all_goods_services_amount,
                'all_order_amount' => $all_order_amount,
                'all_premium' => $all_premium,
                'all_payment_amount_no' => $all_payment_amount_no,
                'financial_count' => $lists,
                'shop_map' => $shop,
                'py' => $py
            ]);
        }
        foreach ($financial_count as $v) {
            if(empty($lists[$v['platform_type']])){
                $lists[$v['platform_type']]['platform_type'] = $v['platform_type'];
                $lists[$v['platform_type']]['sales_amount'] = 0;
                $lists[$v['platform_type']]['refund_amount'] = 0;
                $lists[$v['platform_type']]['commission_amount'] = 0;
                $lists[$v['platform_type']]['payment_amount'] = 0;
                $lists[$v['platform_type']]['promotions_amount'] = 0;
                $lists[$v['platform_type']]['freight'] = 0;
                $lists[$v['platform_type']]['refund_commission_amount'] = 0;
                $lists[$v['platform_type']]['advertising_amount'] = 0;
                $lists[$v['platform_type']]['cancellation_amount'] = 0;
                $lists[$v['platform_type']]['goods_services_amount'] = 0;
                $lists[$v['platform_type']]['order_amount'] = 0;
                $lists[$v['platform_type']]['premium'] = 0;
                $lists[$v['platform_type']]['payment_amount_no'] = 0;
            }
            $sales_amount = $v['sales_amount'];
            $refund_amount = $v['refund_amount'];
            $commission_amount = $v['commission_amount'];
            $payment_amount =$v['payment_amount'];
            $promotions_amount = $v['promotions_amount'];
            $freight =$v['freight'];
            $refund_commission_amount = $v['refund_commission_amount'];
            $advertising_amount = $v['advertising_amount'];
            $cancellation_amount = $v['cancellation_amount'];
            $goods_services_amount = $v['goods_services_amount'];
            $order_amount = $v['order_amount'];
            $premium =$v['premium'];
            $payment_amount_no = $v['payment_amount_no'];
            $lists[$v['platform_type']]['sales_amount'] += $sales_amount;
            $lists[$v['platform_type']]['refund_amount'] += $refund_amount;
            $lists[$v['platform_type']]['commission_amount'] += $commission_amount;
            $lists[$v['platform_type']]['payment_amount'] += $payment_amount;
            $lists[$v['platform_type']]['promotions_amount'] += $promotions_amount;
            $lists[$v['platform_type']]['freight'] += $freight;
            $lists[$v['platform_type']]['refund_commission_amount'] += $refund_commission_amount;
            $lists[$v['platform_type']]['advertising_amount'] += $advertising_amount;
            $lists[$v['platform_type']]['cancellation_amount'] += $cancellation_amount;
            $lists[$v['platform_type']]['goods_services_amount'] += $goods_services_amount;
            $lists[$v['platform_type']]['order_amount'] += $order_amount;
            $lists[$v['platform_type']]['premium'] += $premium;
            $lists[$v['platform_type']]['payment_amount_no'] += $payment_amount_no;
            $all_sales_amount += $sales_amount;
            $all_refund_amount += $refund_amount;
            $all_commission_amount += $commission_amount;
            $all_payment_amount +=$payment_amount;
            $all_promotions_amount += $promotions_amount;
            $all_freight +=$freight;
            $all_refund_commission_amount += $refund_commission_amount;
            $all_advertising_amount += $advertising_amount;
            $all_cancellation_amount += $cancellation_amount;
            $all_goods_services_amount += $goods_services_amount;
            $all_order_amount += $order_amount;
            $all_premium +=$premium;
            $all_payment_amount_no += $payment_amount_no;
        }
        $shop = Shop::find()->select('id,name')->asArray()->all();
        $shop = ArrayHelper::map($shop,'id','name');
        return $this->render('financial_count', [
            'searchModel' => [
                'start_date' => $start_date,
                'end_date' => $end_date,
                'platform_type' => $platform_type
            ],
            'name' => $name,
            'all_sales_amount' => $all_sales_amount,
            'all_refund_amount' => $all_refund_amount,
            'all_commission_amount' => $all_commission_amount,
            'all_payment_amount' => $all_payment_amount,
            'all_promotions_amount' => $all_promotions_amount,
            'all_freight' => $all_freight,
            'all_refund_commission_amount' => $all_refund_commission_amount,
            'all_advertising_amount' => $all_advertising_amount,
            'all_cancellation_amount' => $all_cancellation_amount,
            'all_goods_services_amount' => $all_goods_services_amount,
            'all_order_amount' => $all_order_amount,
            'all_premium' => $all_premium,
            'all_payment_amount_no' => $all_payment_amount_no,
            'financial_count' => $lists,
            'shop_map' => $shop,
            'py' => $py
        ]);
    }

    public function model(){
        return new ReportUserCount();
    }

    /**
     * @routeName 用户统计表主页
     * @routeDescription 用户统计表主页
     */
    public function actionIndex()
    {
        $req = Yii::$app->request;
        $searchModel=new ReportUserCountSearch();
        $start_date = $req->get('start_date', date('Y-m-d'));
        $end_date = $req->get('end_date', date('Y-m-d'));
        $query_params = Yii::$app->request->queryParams;
        if(empty($query_params['ReportUserCountSearch'])){
            $query_params['ReportUserCountSearch']['start_date'] = $start_date;
            $query_params['ReportUserCountSearch']['end_date'] = $end_date;
        }
        $where=$searchModel->search($query_params);
        $data = $this->lists($where);
        $item = ReportUserCount::dealWhere($where)->select('sum(o_goods_success) as o_goods_success,
        sum(o_goods_fail) as o_goods_fail,
        sum(o_goods_audit) as o_goods_audit,
        sum(o_goods_upload) as o_goods_upload,
        sum(order_count) as order_count')
            ->asArray()->all();
        foreach ($data['list'] as &$model){
            $map = ShopService::getShopMap();
            $model['shop_id'] =$map[$model['shop_id']];
            $model['admin_id'] = User::getInfoNickname($model['admin_id']);
            $model['date_time'] = Yii::$app->formatter->asDate($model['date_time']);
        }

        return $this->render('index', [
            'searchModel' => $searchModel,
            'list' => $data['list'],
            'pages' => $data['pages'],
            'item' => $item,
            'start_date' => $start_date,
            'end_date' => $end_date,
        ]);
    }


    /**
     * @routeName 店铺统计表
     * @routeDescription 店铺统计表
     */
    public function actionShopIndex()
    {
        $req = Yii::$app->request;
        $start_month = $req->get('start_month', date('Y-m'));
        $shop_id = $req->get('shop_id',487);
        $start_month_date = strtotime($start_month);
        if($start_month_date == strtotime(date('Y-m'))) {//本月
            $end_month_date = strtotime(date('Y-m-d'));
        } else {
            $end_month_date = strtotime("+1 month", $start_month_date);
        }
        $last_month_date = strtotime("-1 month",$start_month_date);
        $end_last_month_date = strtotime("-1 month",$end_month_date);

        $where = [
            'shop_id' => $shop_id
        ];
        //订单
        $order = Order::find()->where($where)
            ->select('count(*) as all_cut,
            sum(case when order_status = 40 then 0 else order_income_price end) as income_price,
            sum(case when order_status = 40 then 1 else 0 end) as cancel_cut')
            ->andWhere(['>=', 'date', $start_month_date])
            ->andWhere(['<', 'date', $end_month_date])
            ->asArray()->one();
        //上个月订单
        $last_order = Order::find()->where($where)
            ->select('count(*) as all_cut,
            sum(case when order_status = 40 then 0 else order_income_price end) as income_price,
            sum(case when order_status = 40 then 1 else 0 end) as cancel_cut')
            ->andWhere(['>=', 'date', $last_month_date])
            ->andWhere(['<', 'date', $end_last_month_date])
            ->asArray()->one();

        //广告
        $promote = PromoteCampaignDetails::find()->where($where)
            ->select('sum(promotes) as promotes')
            ->andWhere(['>=', 'promote_time', $start_month_date])
            ->andWhere(['<', 'promote_time', $end_month_date])
            ->asArray()->one();
        //上个月广告
        $last_promote = PromoteCampaignDetails::find()->where($where)
            ->select('sum(promotes) as promotes')
            ->andWhere(['>=', 'promote_time', $last_month_date])
            ->andWhere(['<', 'promote_time', $end_last_month_date])
            ->asArray()->one();

        $promote_cur = empty($order['income_price'])?0:round(($promote['promotes']/$order['income_price'])*100,2);
        $promote_last = empty($order['income_price'])?0:round(($last_promote['promotes']/$order['income_price'])*100,2);

        $where['order_status'] = Order::ORDER_STATUS_REFUND;
        //退款
        $order_refund = Order::find()->where($where)
            ->select('count(*) as refund_cut')
            ->andWhere(['>=', 'cancel_time', $start_month_date])
            ->andWhere(['<', 'cancel_time', $end_month_date])
            ->asArray()->one();
        //上个月退款
        $last_order_refund = Order::find()->where($where)
            ->select('count(*) as refund_cut')
            ->andWhere(['>=', 'cancel_time', $last_month_date])
            ->andWhere(['<', 'cancel_time', $end_last_month_date])
            ->asArray()->one();

        $refund_cur = $order['all_cut']-$order['cancel_cut']==0?0:round(($order_refund['refund_cut']/($order['all_cut']-$order['cancel_cut']))*100,2);
        $refund_last = $last_order['all_cut']-$last_order['cancel_cut']==0?0:round(($last_order_refund['refund_cut']/($last_order['all_cut']-$last_order['cancel_cut']))*100,2);

        $mon_rate_fun = function ($current,$last){
            if(empty($last)) {
                return 0;
            }
            return round(($current - $last)/$last * 100,2);
        };

        return $this->render('shop_index', [
            'searchModel' => [
                'start_month' => $start_month,
                'shop_id' => $shop_id
            ],
            'item' => [
                'income_price' => [
                    'current' => empty($order['income_price'])?0:$order['income_price'],
                    'last' => empty($last_order['income_price'])?0:$last_order['income_price'],
                    'm_rate' => $mon_rate_fun($order['income_price'],$last_order['income_price']),
                ],
                'all_cut' => [
                    'current' => empty($order['all_cut'])?0:$order['all_cut'],
                    'last' => empty($last_order['all_cut'])?0:$last_order['all_cut'],
                    'm_rate' => $mon_rate_fun($order['all_cut'],$last_order['all_cut']),
                ],
                'cancel_cut' => [
                    'current' => empty($order['cancel_cut'])?0:$order['cancel_cut'],
                    'last' => empty($last_order['cancel_cut'])?0:$last_order['cancel_cut'],
                    'm_rate' => $mon_rate_fun($order['cancel_cut'],$last_order['cancel_cut']),
                ],
                'refund' => [
                    'current' => $refund_cur,
                    'last' => $refund_last,
                    'm_rate' => $mon_rate_fun($refund_cur,$refund_last),
                    'refund_cut' => empty($order_refund['refund_cut'])?0:$order_refund['refund_cut'],
                ],
                'promote' => [
                    'current' => $promote_cur,
                    'last' => $promote_last,
                    'm_rate' => $mon_rate_fun($promote_cur,$promote_last),
                    'fee' => empty($promote['promotes'])?0:$promote['promotes'],
                ],
            ],
        ]);
    }

}