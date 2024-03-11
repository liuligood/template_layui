<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%goods_property}}".
 *
 * @property int $id
 * @property string $goods_no 商品编号
 * @property int $property_id 属性id
 * @property int $property_value_id 属性值id
 * @property string $property_value 属性值
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class GoodsProperty extends BaseAR
{

    public static function tableName()
    {
        return '{{%goods_property}}';
    }

    public function rules()
    {
        return [
            [['goods_no'], 'required'],
            [['property_id', 'property_value_id', 'add_time', 'update_time'], 'integer'],
            [['goods_no'], 'string', 'max' => 24],
            [['property_value'], 'string', 'max' => 256],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'goods_no' => 'Goods No',
            'property_id' => 'Property ID',
            'property_value_id' => 'Property Value ID',
            'property_value' => 'Property Value',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}
