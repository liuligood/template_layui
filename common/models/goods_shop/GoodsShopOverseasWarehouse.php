<?php
namespace common\models\goods_shop;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%goods_shop_overseas_warehouse}}".
 *
 * @property int $id
 * @property int $goods_shop_id 店铺商品id
 * @property int $shop_id 店铺id
 * @property string $cgoods_no 子商品编号
 * @property int $platform_type 所属平台
 * @property int $warehouse_id 仓库id
 * @property string $start_logistics_cost 头程费用
 * @property string $end_logistics_cost 尾程费用
 * @property string $estimated_start_logistics_cost 预估头程费用
 * @property string $estimated_end_logistics_cost 预估尾程费用
 * @property int $goods_stock 商品库存
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class GoodsShopOverseasWarehouse extends BaseAR
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%goods_shop_overseas_warehouse}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['goods_shop_id', 'shop_id', 'platform_type', 'warehouse_id', 'add_time', 'update_time', 'goods_stock'], 'integer'],
            [['start_logistics_cost', 'end_logistics_cost', 'estimated_start_logistics_cost', 'estimated_end_logistics_cost'], 'number'],
            [['cgoods_no'], 'string', 'max' => 24],
            [['goods_shop_id'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'goods_shop_id' => '店铺商品id',
            'shop_id' => '店铺id',
            'cgoods_no' => '子商品编号',
            'platform_type' => '所属平台',
            'warehouse_id' => '仓库id',
            'start_logistics_cost' => '头程费用',
            'end_logistics_cost' => '尾程费用',
            'estimated_start_logistics_cost' => '预估头程费用',
            'estimated_end_logistics_cost' => '预估尾程费用',
            'add_time' => '添加时间',
            'update_time' => '修改时间',
        ];
    }
}