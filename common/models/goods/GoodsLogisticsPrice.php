<?php

namespace common\models\goods;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%goods_logistics_price}}".
 *
 * @property int $id
 * @property int $platform_type 所属平台
 * @property string $cgoods_no 子商品编号
 * @property string $country 国家
 * @property int $logistics_channels_id 物流渠道id
 * @property string $logistics_name 仓库名称
 * @property string $currency 货币
 * @property string $price 价格
 * @property int $order_time 订单时间
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class GoodsLogisticsPrice extends BaseAR
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%goods_logistics_price}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['platform_type', 'logistics_channels_id', 'order_time', 'add_time', 'update_time'], 'integer'],
            [['price'], 'number'],
            [['cgoods_no'], 'string', 'max' => 24],
            [['country'], 'string', 'max' => 2],
            [['logistics_name'], 'string', 'max' => 64],
            [['currency'], 'string', 'max' => 3],
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
            'cgoods_no' => 'Cgoods No',
            'country' => 'Country',
            'logistics_channels_id' => 'Logistics Channels ID',
            'logistics_name' => 'Logistics Name',
            'currency' => 'Currency',
            'price' => 'Price',
            'order_time' => 'Order Time',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}