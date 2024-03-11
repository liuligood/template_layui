<?php

namespace common\models\order;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "ys_order_refund".
 *
 * @property int $id
 * @property string $order_id 订单号
 * @property int $refund_reason 退款原因
 * @property string $refund_remarks 备注
 * @property int $refund_type 退款类型
 * @property float $refund_num 退款金额
 * @property int $admin_id 操作者
 * @property int $add_time 退款时间
 * @property int $update_time 更新时间
 */
class OrderRefund extends BaseAR
{
    const REFUND_ONE = 1;
    const REFUND_TWO = 2;
    public static $refund_map=[
        self::REFUND_TWO => '部分退款',
        self::REFUND_ONE => '全部退款'

    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ys_order_refund';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['refund_reason', 'refund_type', 'add_time', 'update_time','admin_id'], 'integer'],
            [['order_id'], 'string', 'max' => 32],
            [['refund_remarks'], 'string', 'max' => 255],
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
            'refund_reason' => 'Refund Reason',
            'refund_remarks' => 'Refund Remarks',
            'refund_type' => 'Refund Type',
            'refund_num' => 'Refund Num',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}
