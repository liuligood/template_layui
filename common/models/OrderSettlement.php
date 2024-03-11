<?php

namespace common\models;


use Yii;

/**
 * This is the model class for table "{{%order_settlement}}".
 *
 * @property int $id
 * @property string $order_id 订单号
 * @property string $relation_no 销售单号
 * @property int $shop_id 店铺id
 * @property int $platform_type 平台
 * @property string $sales_amount 销售金额
 * @property string $commission_amount 佣金
 * @property string $refund_amount 退款金额
 * @property string $other_amount 其他费用
 * @property string $cancellation_amount 取消费用
 * @property string $refund_commission_amount 退款佣金
 * @property string $platform_type_freight 平台运费
 * @property string $tax_amount 税务
 * @property string $freight 运费
 * @property string $currency 货币
 * @property string $exchange_rate 汇率
 * @property string $procurement_amount 采购金额
 * @property string $total_amount 总金额
 * @property string $total_profit 总利润
 * @property int $order_time 下单时间
 * @property int $delivery_time 发货时间
 * @property int $settlement_time 结算时间
 * @property int $collection_time 回款时间
 * @property int $settlement_status 结算状态
 * @property string $sales_period_ids 账期id
 */
class OrderSettlement extends BaseAR
{

    const SETTLEMENT_STATUS_UNCONFIRMED = 0;//未确认
    const SETTLEMENT_STATUS_CONFIRMED = 1;//待结算
    const SETTLEMENT_STATUS_SETTLEMENT = 2;//已结算
    const SETTLEMENT_STATUS_NULLIFIED = 10;//无效


    public static function tableName()
    {
        return '{{%order_settlement}}';
    }


    public function rules()
    {
        return [
            [['shop_id', 'platform_type', 'order_time', 'settlement_time','add_time','update_time','collection_time','settlement_status','delivery_time'], 'integer'],
            [['sales_amount', 'commission_amount', 'refund_amount', 'other_amount', 'cancellation_amount', 'refund_commission_amount', 'platform_type_freight', 'freight', 'exchange_rate', 'procurement_amount', 'total_amount', 'total_profit', 'tax_amount'], 'number'],
            [['order_id', 'currency','sales_period_ids'], 'string', 'max' => 32],
            [['relation_no'], 'string', 'max' => 64],
        ];
    }


    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => 'Order ID',
            'relation_no' => 'Relation No',
            'shop_id' => 'Shop ID',
            'platform_type' => 'Platform Type',
            'sales_amount' => 'Sales Amount',
            'commission_amount' => 'Commission Amount',
            'refund_amount' => 'Refund Amount',
            'other_amount' => 'Other Amount',
            'cancellation_amount' => 'Cancellation Amount',
            'refund_commission_amount' => 'Refund Commission Amount',
            'platform_type_freight' => 'Platform Type Freight',
            'freight' => 'Freight',
            'currency' => 'Currency',
            'exchange_rate' => 'Exchange Rate',
            'procurement_amount' => 'Procurement Amount',
            'total_amount' => 'Total Amount',
            'total_profit' => 'Total Profit',
            'order_time' => 'Order Time',
            'settlement_time' => 'Settlement Time',
        ];
    }
}
