<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%order_logistics_status}}".
 *
 * @property int $id
 * @property int $source 来源
 * @property int $shop_id 店铺id
 * @property string $order_id 订单号
 * @property string $relation_no 销售单号
 * @property int $status 物流状态
 * @property int $error_status 错误状态
 * @property int $plan_time 计划执行时间
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class OrderLogisticsStatus extends BaseAR
{

    const STATUS_WAIT = 0;
    const STATUS_ON_WAY = 1;
    const STATUS_DELIVERED = 2;

    const ERROR_STATUS_NO = 0;
    const ERROR_STATUS_YES = 1;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%order_logistics_status}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['source', 'shop_id', 'status', 'error_status', 'plan_time', 'add_time', 'update_time'], 'integer'],
            [['order_id'], 'string', 'max' => 32],
            [['relation_no'], 'string', 'max' => 64],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'source' => 'Source',
            'shop_id' => 'Shop ID',
            'order_id' => 'Order ID',
            'relation_no' => 'Relation No',
            'logistics_channels_id' => 'Logistics Channels ID',
            'track_no' => 'Track No',
            'status' => 'Status',
            'plan_time' => 'Plan Time',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}