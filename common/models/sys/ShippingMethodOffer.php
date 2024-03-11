<?php

namespace common\models\sys;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%sys_shipping_method_offer}}".
 *
 * @property int $id
 * @property int $shipping_method_id 物流商运输服务id
 * @property string $transport_code 物流商代码
 * @property string $shipping_method_code 物流商运输服务代码
 * @property string $country_code 国家代码
 * @property string $start_weight 开始重量
 * @property string $end_weight 结束重量
 * @property string $weight_price 价格/kg
 * @property string $deal_price 处理费用
 * @property int $status 0:禁用 10:已启用
 * @property string $formula 公式
 * @property int $add_time 创建时间
 * @property int $update_time 更新时间
 */
class ShippingMethodOffer extends BaseAR
{
    const STATUS_VALID = 10;
    const STATUS_INVALID = 0;

    public static $status_map = [
        self::STATUS_VALID => '正常',
        self::STATUS_INVALID => '禁用',
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%sys_shipping_method_offer}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['start_weight', 'end_weight', 'weight_price', 'deal_price'], 'number'],
            [['weight_price', 'deal_price'], 'required'],
            [['status', 'add_time', 'update_time','shipping_method_id'], 'integer'],
            [['transport_code'], 'string', 'max' => 50],
            [['shipping_method_code'], 'string', 'max' => 100],
            [['country_code'], 'string', 'max' => 2],
            [['formula'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'transport_code' => 'Transport Code',
            'shipping_method_code' => 'Shipping Method Code',
            'country_code' => 'Country Code',
            'start_weight' => 'Start Weight',
            'end_weight' => 'End Weight',
            'weight_price' => 'Weight Price',
            'deal_price' => 'Deal Price',
            'status' => 'Status',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}