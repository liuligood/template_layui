<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "ys_financial_platform_sales_period".
 *
 * @property int $id
 * @property int $platform_type 所属平台
 * @property int $shop_id 店铺id
 * @property int $data 日期
 * @property int $stop_data 结束日期
 * @property string $sales_amount 销售金额
 * @property string $refund_amount 退款金额
 * @property string $commission_amount 佣金
 * @property string $payment_amount 回款
 * @property string $payment_amount_original 原回款金额
 * @property string $promotions_amount 促销活动
 * @property string $order_amount 其他费用
 * @property string $currency 货币
 * @property string $remark 备注
 * @property int $add_time 创建日期
 * @property int $update_time 更新日期
 * @property int $payment_back 是否回款
 * @property string $freight 运费
 * @property string $refund_commission_amount 退款佣金
 * @property string $advertising_amount 广告费用
 * @property string $cancellation_amount 取消费用
 * @property string $goods_services_amount 商品服务费用
 * @property string premium 手续费
 * @property int $collection_time 回款时间
 * @property string $objection_amount 退款金额
 * @property int $objection 是否有异议
 */
class FinancialPlatformSalesPeriod extends BaseAR
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ys_financial_platform_sales_period';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['platform_type', 'shop_id', 'data', 'add_time', 'update_time','payment_back','collection_time','objection'], 'integer'],
            [['sales_amount','objection_amount', 'refund_amount', 'commission_amount','refund_commission_amount','payment_amount','freight', 'promotions_amount', 'order_amount','advertising_amount','cancellation_amount','goods_services_amount','premium','payment_amount_original'], 'number'],
            [['currency'], 'string', 'max' => 32],
            [['remark'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'platform_type' => 'Platform Type',
            'shop_id' => 'Shop ID',
            'data' => 'Data',
            'sales_amount' => 'Sales Amount',
            'refund_amount' => 'Refund Amount',
            'commission_amount' => 'Commission Amount',
            'payment_amount' => 'Payment Amount',
            'promotions_amount' => 'Promotions Amount',
            'order_amount' => 'Order Amount',
            'currency' => 'Currency',
            'remark' => 'Remark',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
            'freight' => 'Freight'
        ];
    }
}
