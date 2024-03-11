<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%order_recommended}}".
 *
 * @property int $id
 * @property string $order_id 订单号
 * @property int $logistics_channels_id 物流渠道
 * @property string $freight_price 运费
 * @property int $update_time 修改时间
 * @property int $add_time 添加时间
 */
class OrderRecommended extends BaseAR
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%order_recommended}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['logistics_channels_id', 'update_time', 'add_time'], 'integer'],
            [['freight_price'], 'number'],
            [['order_id'], 'string', 'max' => 32],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => 'Order ID',
            'logistics_channels_id' => 'Logistics Channels ID',
            'freight_price' => 'Freight Price',
            'update_time' => 'Update Time',
            'add_time' => 'Add Time',
        ];
    }
}