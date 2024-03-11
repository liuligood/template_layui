<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%freight_price_log}}".
 *
 * @property int $id
 * @property string $order_id 订单号
 * @property int $logistics_channels_id 物流渠道id
 * @property string $transport_code 物流商代码
 * @property string $country 国家
 * @property string $track_no 物流单号
 * @property string $track_logistics_no 物流转单号
 * @property string $freight_price 运费
 * @property string $weight 重量
 * @property string $length 长
 * @property string $width 宽
 * @property string $height 高
 * @property int $billed_time 计费时间
 * @property int $update_time 更新时间
 * @property int $add_time 添加时间
 */
class FreightPriceLog extends BaseAR
{

    public static function tableName()
    {
        return '{{%freight_price_log}}';
    }

    public function rules()
    {
        return [
            [['logistics_channels_id', 'billed_time', 'update_time', 'add_time'], 'integer'],
            [['freight_price', 'weight', 'length', 'width', 'height'], 'number'],
            [['order_id'], 'string', 'max' => 32],
            [['transport_code'], 'string', 'max' => 50],
            [['country', 'track_no', 'track_logistics_no'], 'string', 'max' => 60],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => 'Order ID',
            'logistics_channels_id' => 'Logistics Channels ID',
            'transport_code' => 'Transport Code',
            'country' => 'Country',
            'track_no' => 'Track No',
            'track_logistics_no' => 'Track Logistics No',
            'freight_price' => 'Freight Price',
            'weight' => 'Weight',
            'length' => 'Length',
            'width' => 'Width',
            'height' => 'Height',
            'billed_time' => 'Billed Time',
            'update_time' => 'Update Time',
            'add_time' => 'Add Time',
        ];
    }
}
