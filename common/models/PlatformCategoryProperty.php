<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%platform_category_property}}".
 *
 * @property int $id
 * @property int $platform_type 平台类型
 * @property int $property_type 属性类型,1:属性,2:属性值
 * @property int $property_id 属性id or 属性值id
 * @property int $platform_property_id 平台属性id
 * @property string $name 平台属性值名称
 * @property string $param 额外参数
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class PlatformCategoryProperty extends BaseAR
{
    const TYPE_PROPERTY = 1;//属性
    const TYPE_PROPERTY_VALUE = 2;//属性值

    public static function tableName()
    {
        return '{{%platform_category_property}}';
    }

    public function rules()
    {
        return [
            [['platform_type', 'property_type', 'property_id', 'platform_property_id', 'add_time', 'update_time'], 'integer'],
            [['param'], 'string'],
            [['name'], 'string', 'max' => 32],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'platform_type' => 'Platform Type',
            'property_type' => 'Property Type',
            'property_id' => 'Property ID',
            'platform_property_id' => 'Platform Property ID',
            'name' => 'Name',
            'param' => 'Param',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}
