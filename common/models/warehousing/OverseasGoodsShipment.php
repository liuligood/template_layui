<?php
namespace common\models\warehousing;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%overseas_goods_shipment}}".
 *
 * @property int $id
 * @property string $cgoods_no 子商品编号
 * @property int $num 数量
 * @property int $supplier_id 供应商id
 * @property int $warehouse_id 仓库id
 * @property int $status 状态
 * @property int $bl_container_goods_id 提单箱商品id
 * @property int $air_logistics 是否空运
 * @property string $porder_id 采购订单号
 * @property int $purchase_time 采购时间
 * @property int $arrival_time 到货时间
 * @property int $packing_time 装箱时间
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class OverseasGoodsShipment extends BaseAR
{

    const STATUS_UNCONFIRMED = 0;//未确认
    const STATUS_WAIT_PURCHASE = 10;//待采购
    const STATUS_WAIT_SHIP = 20;//待发货
    const STATUS_WAIT_PACKED = 30;//待装箱
    const STATUS_FINISH = 40;//已完成
    const STATUS_CANCELLED = 50;//作废


    public static $status_maps = [
        self::STATUS_UNCONFIRMED => '未确认',
        self::STATUS_WAIT_PURCHASE => '待采购',
        self::STATUS_WAIT_SHIP => '待发货',
        self::STATUS_WAIT_PACKED => '待装箱',
        self::STATUS_FINISH => '已完成',
        self::STATUS_CANCELLED => '作废',
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%overseas_goods_shipment}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['num', 'warehouse_id', 'supplier_id', 'status', 'add_time', 'update_time','purchase_time','arrival_time','packing_time','bl_container_goods_id','air_logistics'], 'integer'],
            [['cgoods_no'], 'string', 'max' => 24],
            [['porder_id'], 'string', 'max' => 32],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'cgoods_no' => '子商品编号',
            'num' => '数量',
            'supplier_id' => '供应商id',
            'finish_num' => '到货数量',
            'status' => '状态',
            'add_time' => '添加时间',
            'update_time' => '修改时间',
        ];
    }
}