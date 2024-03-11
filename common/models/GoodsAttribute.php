<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%goods_attribute}}".
 *
 * @property int $id
 * @property string $goods_no 商品编号
 * @property string $attribute_name 属性名称
 * @property string $attribute_value 属性值
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class GoodsAttribute extends BaseAR
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%goods_attribute}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['goods_no'], 'required'],
            [['add_time', 'update_time'], 'integer'],
            [['goods_no'], 'string', 'max' => 24],
            [['attribute_name'], 'string', 'max' => 100],
            [['attribute_value'], 'string', 'max' => 256],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'goods_no' => 'Goods No',
            'attribute_name' => 'Attribute Name',
            'attribute_value' => 'Attribute Value',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}