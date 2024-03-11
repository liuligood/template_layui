<?php
namespace common\models\goods;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%goods_stock_details}}".
 *
 * @property int $id
 * @property string $cgoods_no 子商品编号
 * @property string $order_id 订单号
 * @property string $warehouse 仓库
 * @property string $purchase_order_id 采购订单号
 * @property int $type 类型:1.采购订单，2.手动入库
 * @property int $status 状态：1未入库，2已入库，3已出库，4作废
 * @property string $goods_price 价格
 * @property int $inbound_time 入库时间
 * @property int $outgoing_time 出库时间
 * @property int $cancel_time 取消时间
 * @property int $admin_id 管理员id
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class GoodsStockDetails extends BaseAR
{

    const STATUS_INBOUND = 2;//已入库
    const STATUS_OUTGOING = 3;//已出库
    //const STATUS_CANCEL = 4;//作废

    const TYPE_PURCHASE = 1;//采购入库
    const TYPE_ADMIN = 2;//手动入库

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%goods_stock_details}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type', 'status', 'inbound_time','warehouse', 'outgoing_time', 'cancel_time', 'admin_id', 'add_time', 'update_time'], 'integer'],
            [['goods_price'], 'number'],
            [['cgoods_no'], 'string', 'max' => 24],
            [['order_id', 'purchase_order_id'], 'string', 'max' => 32],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'cgoods_no' => 'Cgoods No',
            'order_id' => 'Order ID',
            'purchase_order_id' => 'Purchase Order ID',
            'type' => 'Type',
            'status' => 'Status',
            'inbound_time' => 'Inbound Time',
            'outgoing_time' => 'Outgoing Time',
            'cancel_time' => 'Cancel Time',
            'admin_id' => 'Admin ID',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }

}