<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "ys_financial_period_rollover".
 *
 * @property int $id
 * @property int $platform_type 来源
 * @property int $shop_id 店铺id
 * @property int $financial_id 账期表id
 * @property int $date 结算时间
 * @property int $data_post 操作日期
 * @property string $identifier
 * @property string $operation 操作类型
 * @property string $buyer 操作人消息
 * @property string $offer 操作单消息
 * @property string $amount 金额
 * @property int $collection_time 回款时间
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 * @property string $currency 货币
 * @property string $relation_no 销售单号
 * @property string $order_id 订单id
 * @property string $params 参数
 * @property int $execute_status  执行状态
 * @property int $is_manual 是否手动添加
 */
class FinancialPeriodRollover extends BaseAR
{
    const IS_NO_MANUAL = 0;//自动
    const MANUAL = 1;//手动


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ys_financial_period_rollover';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['platform_type', 'shop_id', 'financial_id', 'date', 'data_post', 'add_time', 'update_time','execute_status','collection_time','is_manual'], 'integer'],
            [['amount'], 'number'],
            [['identifier'], 'string', 'max' => 60],
            [['operation'], 'string', 'max' => 256],
            [['buyer', 'offer'], 'string', 'max' => 256],
            [['relation_no','currency','order_id'],'string','max' => 64],
            [['params'], 'string'],
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
            'financial_id' => 'Financial ID',
            'date' => 'Date',
            'data_post' => 'Data  Post',
            'identifier' => 'Identifier',
            'operation' => 'Operation',
            'buyer' => 'Buyer',
            'offer' => 'Offer',
            'amount' => 'Amount',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
            'currency' => 'Currency',
        ];
    }
}
