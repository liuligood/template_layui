<?php
namespace common\models\order;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%order_transport}}".
 *
 * @property int $id
 * @property string $order_id 订单号
 * @property string $order_code 第三方订单号
 * @property int $warehouse_id 仓库id
 * @property string $transport_code 物流商代码
 * @property int $shipping_method_id 物流方式id
 * @property string $track_no 物流跟踪号
 * @property string $weight 重量
 * @property string $size 尺寸
 * @property string $total_fee 费用
 * @property string $currency 货币
 * @property int $status 状态
 * @property int $admin_id 操作者
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class OrderTransport extends BaseAR
{

    const STATUS_UNCONFIRMED = 0;//未确认
    const STATUS_CONFIRMED = 10;//已确认
    const STATUS_EXISTING_TRACKING = 15;//已有物流单号
    const STATUS_CANCELLED = 30;//已取消

    public static $status_maps = [
        self::STATUS_UNCONFIRMED => '未确认',
        self::STATUS_CONFIRMED => '已确认',
        self::STATUS_EXISTING_TRACKING => '已有物流轨迹',
        self::STATUS_CANCELLED => '已取消'
    ];



    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%order_transport}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['warehouse_id', 'status', 'admin_id', 'add_time', 'update_time','shipping_method_id'], 'integer'],
            [['total_fee','weight'], 'number'],
            [['order_id', 'order_code','transport_code'], 'string', 'max' => 32],
            [['track_no','size'], 'string', 'max' => 60],
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
            'order_id' => '订单号',
            'order_code' => '第三方订单号',
            'warehouse_id' => '仓库id',
            'shipping_method' => '物流方式',
            'track_no' => '物流跟踪号',
            'fee' => '费用',
            'currency' => '货币',
            'status' => '状态',
            'admin_id' => '操作者',
            'add_time' => '添加时间',
            'update_time' => '修改时间',
        ];
    }
}