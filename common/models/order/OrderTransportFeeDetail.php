<?php
namespace common\models\order;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%order_transport_fee_detail}}".
 *
 * @property int $id
 * @property int $order_transport_id 订单运输id
 * @property string $order_id 订单号
 * @property string $order_code 第三方订单号
 * @property int $warehouse_id 仓库id
 * @property string $fee_name 费用名称
 * @property string $fee_code 费用编号
 * @property string $fee 费用
 * @property string $currency 货币
 * @property int $status 状态
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class OrderTransportFeeDetail extends BaseAR
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%order_transport_fee_detail}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['order_transport_id', 'warehouse_id', 'status', 'add_time', 'update_time'], 'integer'],
            [['fee'], 'number'],
            [['order_id', 'order_code', 'fee_name', 'fee_code'], 'string', 'max' => 32],
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
            'order_transport_id' => '订单运输id',
            'order_id' => '订单号',
            'order_code' => '第三方订单号',
            'warehouse_id' => '仓库id',
            'fee_name' => '费用名称',
            'fee_code' => '费用编号',
            'fee' => '费用',
            'currency' => '货币',
            'status' => '状态',
            'add_time' => '添加时间',
            'update_time' => '修改时间',
        ];
    }

}