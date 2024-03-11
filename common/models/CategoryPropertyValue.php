<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "ys_category_property_value".
 *
 * @property int $id
 * @property int $property_id 属性id
 * @property string $property_value 属性值
 * @property int $status 状态
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class CategoryPropertyValue extends BaseAR
{

    public static function tableName()
    {
        return 'ys_category_property_value';
    }

    public function rules()
    {
        return [
            [['property_id', 'status', 'add_time', 'update_time'], 'integer'],
            [['property_value'], 'string', 'max' => 100],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'property_id' => 'Property ID',
            'property_value' => 'Property Value',
            'status' => 'Status',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}
