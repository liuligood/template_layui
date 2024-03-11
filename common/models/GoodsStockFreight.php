<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%goods_stock_freight}}".
 *
 * @property int $id
 * @property int $warehouse_id 仓库id
 * @property string $cgoods_no 子商品编号
 * @property string $freight_price 运费
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class GoodsStockFreight extends BaseAR
{

    public static function tableName()
    {
        return '{{%goods_stock_freight}}';
    }

    public function rules()
    {
        return [
            [['warehouse_id', 'add_time', 'update_time'], 'integer'],
            [['freight_price'], 'number'],
            [['cgoods_no'], 'string', 'max' => 255],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'warehouse_id' => 'Warehouse ID',
            'cgoods_no' => 'Cgoods No',
            'freight_price' => 'Freight Price',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}
