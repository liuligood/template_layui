<?php
namespace common\services\financial;

use common\models\FinancialPeriodRollover;
use common\models\FinancialPlatformSalesPeriod;
use common\models\Order;
use common\models\order\OrderRefund;
use yii\helpers\ArrayHelper;

class PlatformSalesPeriodService
{

    const OPERATION_ONE = 1;
    const OPERATION_TWO = 2;
    const OPERATION_TRE = 3;
    const OPERATION_FOR = 4;
    const OPERATION_FIR = 5;
    const OPERATION_SIX = 6;
    const OPERATION_SER = 7;
    const OPERATION_EIG = 8;
    const OPERATION_NIN = 9;
    const OPERATION_TEN = 10;
    const OPERATION_ELE = 11;
    const OPERATION_TWE = 12;

    public static $OPREATION_MAP=[
        self::OPERATION_ONE => '销售金额',
        self::OPERATION_TWO => '其他费用',
        self::OPERATION_TRE => '商品服务费',
        self::OPERATION_FOR => '退款金额',
        self::OPERATION_FIR => '取消费用',
        self::OPERATION_SIX => '佣金',
        self::OPERATION_SER => '退款佣金',
        self::OPERATION_EIG => '运费',
        self::OPERATION_NIN => '广告费用',
        self::OPERATION_TEN => '促销活动',
        self::OPERATION_ELE => '手续费',
        self::OPERATION_TWE => '异议费用'
    ];

    public static $OPREATION_ALL_MAP=[
        self::OPERATION_ONE => ['pobranieopłatześrodków','wpłata','Доставкапокупателю','Sale','wypłataśrodków','SHIPMENT','PART_ORDER','ORDER','income','CONTRIBUTION','merchant_amount','SALE','OperationAgentDeliveredToCustomer'],
        self::OPERATION_TWO => ['Удержаниезанедовложениетовара','CORRECTION','utrzymać','Initial_ Expense','Инвентаризациявзаиморасчетов','Приобретениеотзывовнаплатформе','Прочиекомпенсации','orders','Other','hepsilogistic_fee','invoice_diff_return','Additional_Charge','Chargeback_Additional_Charge','Correction of VAT charges','Account correction','Payment','Bonus awarded','SURCHARGE','Monthly Fee','Complaint issue fee','Issue fee complaints','Chemical tax','Issue fee missing parcels','Prohibited article','Monthly Service','correction','Incl. VAT','Basic subscription','MarketplaceSaleReviewsOperation'],
        self::OPERATION_TRE => ['Item listing','Maintenance fee','Fulfillment — basic fee','Fulfillment — storage over 60 days','Fulfillment — storage over 30 days','Fulfillment — storage over 90 days','Fulfillment — storage over 180 days'],
        self::OPERATION_FOR => ['Refund','zwrot','Перечислениечастичнойкомпенсациипокупателю','Возвратперечислениязадоставкупокупателю','Chargeback_Sales','Частичнаякомпенсацияпокупателю','CANCELLATION','RETURN','refund','REFUND_CHARGE','Allegro Protect','merchant_refund_amount','REFUNDED','Получениевозврата,отмены,невыкупаотпокупателя','BLOCKADE','BLOCKADE_RELEASE','ClientReturnAgentOperation'],
        self::OPERATION_FIR => ['Take_Rate_Without_Unblocking','penalty','article_row_not_fulfilled_category_fee_amount','article_row_not_fulfilled_selling_fee_amount'],
        self::OPERATION_SIX => ['SALE COMMISSION','commission','Take_Rate','Sales commission','Commission on sales of a Featured offer','Commission on sales in the Deal Zone section','selling_fee_amount','category_fee_amount'],
        self::OPERATION_SER => ['REFUNDED COMMISSION','Refundscommissions','Chargeback_Take_Rate','invoice_diff','Refund of costs','Costs refund'],
        self::OPERATION_EIG => ['Агентскоевознаграждениезазаключениеисопровождениедоговоратранспортно-экспедиционныхуслугпоорганизациимеждународнойперевозки','Перечислениезадоставкуотпокупателя','Перевыставлениеуслугдоставки','refund_shipping','refund_customs','Xpress parcel','Allegro Paczkomaty InPost','DPD parcel','MarketplaceServiceItemDelivToCustomer','MarketplaceServiceItemDirectFlowLogistic','OperationMarketplaceAgencyFeeAggregator3PLGlobal','MarketplaceServiceItemReturnNotDelivToCustomer','MarketplaceServiceItemReturnFlowLogistic','MarketplaceRedistributionOfDeliveryServicesOperation'],
        self::OPERATION_NIN => ['Услугипродвижениятоваров','Fee for Ads campaign','Campaign fee','Great price premium badge fee','Ads campaign fee','MarketplaceMarketingActionCostOperation'],
        self::OPERATION_TEN => ['Chargeback_Promotion','Estorno_de_Comissao_de_Ressarcimento_de_Promocao','Refund_Promotion','Take_Rate_Refund_Sale','Cashback_Ame','Chargeback_Cashback_Ame','Fee for promotion on the category page','Fee for Promo Package','Fee for Feature','Fee for Coins','Refund of fee for Coins','Fee for Flexible Feature','Unit transaction fee','Deal Zone sales commission','Sales commission on featured offers','Flexible feature fee','New badge fee','Deal badge fee',],
        self::OPERATION_ELE => ['premium','mp_service','Unit sales fee','Coins fee','Coins fee refund','MarketplaceRedistributionOfAcquiringOperation'],
        self::OPERATION_TWE => ['Initial_ Expense']
    ];

    public static function  findMap($operation)
    {
        for ($i = 0; $i < count(self::$OPREATION_MAP); $i++) {
            $o = $i + 1;
            foreach (self::$OPREATION_ALL_MAP[$o] as $item) {
                if ($item == $operation) {
                    return $o;
                }
            }
        }
    }

    /**
     * 统计金额
     * @param $fin_id
     * @return bool
     */
    public function amountStatistics($fin_id)
    {
        $fin = FinancialPlatformSalesPeriod::findOne($fin_id);
        $where = [];
        $where['financial_id'] = $fin_id;
        $operation_amount = FinancialPeriodRollover::find()->where($where)->select('operation,sum(amount) as amount')->groupBy('operation')->asArray()->all();
        $map = [];
        foreach (self::$OPREATION_ALL_MAP as $map_k => $map_v) {
            if (empty($map[$map_k])) {
                $map[$map_k] = 0;
            }
            foreach ($operation_amount as $item) {
                if (in_array($item['operation'], $map_v)) {
                    $map[$map_k] += $item['amount'];
                }
            }
        }
        $is_adjust = false;
        if ($fin['payment_amount'] > 0 && $fin['payment_amount'] != $fin['payment_amount_original']) {
            $is_adjust = true;
        }
        $fin['sales_amount'] = $map[self::OPERATION_ONE];
        $fin['commission_amount'] = $map[self::OPERATION_SIX];
        $fin['refund_commission_amount'] = $map[self::OPERATION_SER];
        $fin['refund_amount'] = $map[self::OPERATION_FOR];
        $fin['order_amount'] = $map[self::OPERATION_TWO];
        $fin['promotions_amount'] = $map[self::OPERATION_TEN];
        $fin['freight'] = $map[self::OPERATION_EIG];
        $fin['advertising_amount'] = $map[self::OPERATION_NIN];
        $fin['cancellation_amount'] = $map[self::OPERATION_FIR];
        $fin['premium'] = $map[self::OPERATION_ELE];
        $fin['goods_services_amount'] = $map[self::OPERATION_TRE];
        $fin['objection_amount'] = $map[self::OPERATION_TWE];
        $fin['payment_amount_original'] = $fin['sales_amount']
            + $fin['commission_amount'] + $fin['refund_commission_amount'] +
            $fin['refund_amount'] + $fin['order_amount'] + $fin['promotions_amount']
            + $fin['freight'] + $fin['advertising_amount'] + $fin['cancellation_amount'] +$fin['premium'] + $fin['goods_services_amount']+$fin['objection_amount'];
        if (!$is_adjust) {
            $fin['payment_amount'] = $fin['payment_amount_original'];
        }
        return $fin->save();
    }


    /**
     * 获取类型
     * @param $operation
     * @return bool
     */
    public static function getOperation($operation)
    {
        $type = ['Monthly Fee','Complaint issue fee','Issue fee complaint','Chemical tax','Issue fee missing parcels','Prohibited article','Monthly Service','Issue fee'];
        foreach ($type as $v){
            if (stristr($operation,$v)){
                if ($v == 'Issue fee complaint' || $v == 'Issue fee'){
                    return 'Issue fee complaints';
                }
                return $v;
            }
        }
        return $operation;
    }

    /**
     * 添加账期
     * @param $data
     * @param $operation
     * @param $amount
     * @return bool
     */
    public static function addFinancialPeriodRollover($data, $operation, $amount)
    {
        $data['amount'] = PlatformSalesPeriodService::repairAmount($amount);
        $data['operation'] = $operation;
        FinancialPeriodRollover::add($data);
        return true;
    }


    /**
     * 处理订单退款状态
     * @param $fin_period_rollover
     * @return bool
     */
    public function dealOrderRefundStatus($fin_period_rollover)
    {
        $relation_nos = array_keys($fin_period_rollover);
        $order = Order::find()->where(['relation_no' => $relation_nos])->all();
        foreach ($order as $order_v) {
            if ($order_v['order_status'] == Order::ORDER_STATUS_REFUND || $order_v['order_status'] == Order::ORDER_STATUS_CANCELLED) {
                continue;
            }
            $order_v['order_status'] = Order::ORDER_STATUS_REFUND;
            $order_v['cancel_time'] = $fin_period_rollover[$order_v['relation_no']]['date'];
            if ($order_v->save()) {
                $order_refund = OrderRefund::find()->where(['order_id' => $order_v['order_id']])->one();
                if (!empty($order_refund)) {
                    continue;
                }
                PlatformSalesPeriodService::addOrderRefund($order_v['order_id'], $fin_period_rollover[$order_v['relation_no']]['amount'], $fin_period_rollover[$order_v['relation_no']]['date']);
            }
        }
        return true;
    }


    /**
     * 添加订单退款记录
     * @param $order_id
     * @param $refund_num
     * @param $add_time
     * @return bool
     */
    public static function addOrderRefund($order_id, $refund_num, $add_time)
    {
        $order_refund = new OrderRefund();
        $order_refund['order_id'] = $order_id;
        $order_refund['refund_reason'] = 109;
        $order_refund['refund_remarks'] = '系统自动退款';
        $order_refund['refund_type'] = OrderRefund::REFUND_ONE;
        $order_refund['admin_id'] = 0;
        $order_refund['refund_num'] = abs($refund_num);
        $order_refund->save();
        OrderRefund::updateOneByCond(['order_id' => $order_id],['add_time' => $add_time, 'update_time' => $add_time]);
        return true;
    }

    /*
     * 修复金额
     */
    public static function repairAmount($amount) {
        return str_ireplace(',','',$amount);
    }

}