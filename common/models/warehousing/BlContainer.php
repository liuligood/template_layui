<?php

namespace common\models\warehousing;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "ys_bl_container".
 *
 * @property int $id
 * @property string $country 国家
 * @property int $warehouse_id 库存id
 * @property int $bl_transportation_id 提单箱运输id
 * @property string $bl_no 提单箱编号
 * @property string $weight 重量
 * @property string $size 尺寸
 * @property string $initial_number 序号
 * @property int $cjz 材积重
 * @property string $price 价格
 * @property int $delivery_time 发货时间
 * @property int $arrival_time 预计到达时间
 * @property int $transport_type 运输方式
 * @property int $status 状态
 * @property int $goods_count 商品总数
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class BlContainer extends BaseAR
{
    const STATUS_WAIT_SHIP = 5;//待发货
    const STATUS_NOT_DELIVERED = 10;//未到货
    const STATUS_PARTIAL_DELIVERED = 15;//部分到货
    const STATUS_DELIVERED = 20;//已到货

    const TRANSPORT_SEA = 1;//海运
    const TRANSPORT_HONGKONG_AIR = 2;//香港空运
    const TRANSPORT_AIR = 3;//大陆空运
    const TRANSPORT_RAIL = 4;//铁路

    public static $status_maps = [
        self::STATUS_WAIT_SHIP => '待发货',
        self::STATUS_NOT_DELIVERED => '未到货',
        self::STATUS_PARTIAL_DELIVERED => '部分到货',
        self::STATUS_DELIVERED => '已到货',
    ];

    public static $transport_maps = [
        self::TRANSPORT_SEA => '海运',
        self::TRANSPORT_HONGKONG_AIR => '香港空运',
        self::TRANSPORT_AIR => '大陆空运',
        self::TRANSPORT_RAIL => '铁路'
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ys_bl_container';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['weight', 'price'], 'number'],
            [['cjz', 'delivery_time', 'arrival_time', 'status', 'add_time', 'update_time','warehouse_id','goods_count','transport_type','bl_transportation_id'], 'integer'],
            [['country'], 'string', 'max' => 60],
            [['bl_no','initial_number'], 'string', 'max' => 32],
            [['size'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'country' => 'Country',
            'bl_no' => 'Bl No',
            'weight' => 'Weight',
            'size' => 'Size',
            'cjz' => 'Cjz',
            'price' => 'Price',
            'delivery_time' => 'Delivery Time',
            'arrival_time' => 'Arrival Time',
            'status' => 'Status',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}
