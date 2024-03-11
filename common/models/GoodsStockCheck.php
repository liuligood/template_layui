<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%goods_stock_check}}".
 *
 * @property int $id
 * @property int $cycle_id 周期id
 * @property int $source 来源
 * @property string $goods_no 商品编号
 * @property string $sku_no sku编号
 * @property int $old_stock 旧库存 0:为没库存
 * @property int $stock 库存 0:为没库存
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class GoodsStockCheck extends BaseAR
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%goods_stock_check}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['cycle_id', 'source', 'old_stock', 'stock', 'add_time', 'update_time'], 'integer'],
            [['goods_no', 'sku_no'], 'required'],
            [['goods_no'], 'string', 'max' => 24],
            [['sku_no'], 'string', 'max' => 32],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'cycle_id' => 'Cycle ID',
            'source' => 'Source',
            'goods_no' => 'Goods No',
            'sku_no' => 'Sku No',
            'old_stock' => 'Old Stock',
            'stock' => 'Stock',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}
