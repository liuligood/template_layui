<?php

namespace common\models\warehousing;

use common\models\BaseAR;

/**
 * This is the model class for table "{{%bl_container_transportation}}".
 *
 * @property int $id
 * @property int $warehouse_id 仓库id
 * @property string $country 国家
 * @property string $track_no 物流编号
 * @property string $estimate_weight 估算重量
 * @property int $cjz 材积
 * @property string $weight 重量
 * @property string $unit_price 单价
 * @property string $price 价格
 * @property int $delivery_time 发货时间
 * @property int $arrival_time 预计到达时间
 * @property int $bl_container_count 箱子数量
 * @property int $goods_count 商品总数
 * @property int $transport_type 运输方式
 * @property int $status 状态
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class BlContainerTransportation extends BaseAR
{
    const STATUS_NOT_DELIVERED = 10;//未到货
    const STATUS_DELIVERED = 20;//已到货

    public static $status_maps = [
        self::STATUS_NOT_DELIVERED => '未到货',
        self::STATUS_DELIVERED => '已到货'
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%bl_container_transportation}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['warehouse_id', 'cjz', 'delivery_time', 'arrival_time', 'bl_container_count', 'goods_count', 'transport_type', 'status', 'add_time', 'update_time'], 'integer'],
            [['track_no'], 'required'],
            [['estimate_weight', 'weight', 'unit_price', 'price'], 'number'],
            [['country'], 'string', 'max' => 60],
            [['track_no'], 'string', 'max' => 32],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'warehouse_id' => '仓库id',
            'country' => '国家',
            'track_no' => '物流编号',
            'estimate_weight' => '估算重量',
            'cjz' => '材积',
            'weight' => '重量',
            'unit_price' => '单价',
            'price' => '价格',
            'delivery_time' => '发货时间',
            'arrival_time' => '预计到达时间',
            'bl_container_count' => '箱子数量',
            'goods_count' => '商品总数',
            'transport_type' => '运输方式',
            'status' => '状态',
            'add_time' => '添加时间',
            'update_time' => '修改时间',
        ];
    }


    /**
     * 获取所有物流编号
     * @param $warehouse_id
     */
    public static function getAllTrackNo($warehouse_id = '')
    {
        $model = BlContainerTransportation::find()->filterWhere(['warehouse_id' => $warehouse_id])->andWhere(['status' => self::STATUS_NOT_DELIVERED])->select(['track_no','id'])->asArray()->all();
        $list = [];
        foreach ($model as $v) {
            $list[$v['id']] = $v['track_no'];
        }
        return $list;
    }
}