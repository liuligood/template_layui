<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "ys_category_property".
 *
 * @property int $id
 * @property int $category_id 类目id
 * @property string $property_type 属性类型
 * @property string $property_name 属性名称
 * @property int $is_required 是否必填
 * @property int $is_multiple 是否多选
 * @property int $custom_property_value_id 自定义属性值id
 * @property int $status 状态
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class CategoryProperty extends BaseAR
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ys_category_property';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['category_id', 'is_required', 'is_multiple', 'custom_property_value_id', 'status', 'add_time', 'update_time', 'width', 'sort'], 'integer'],
            [['property_type'], 'string', 'max' => 30],
            [['property_name'], 'string', 'max' => 100],
            [['unit'], 'string', 'max' => 32]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'category_id' => 'Category ID',
            'property_type' => 'Property Type',
            'property_name' => 'Property Name',
            'is_required' => 'Is Required',
            'is_multiple' => 'Is Multiple',
            'custom_property_value_id' => 'Custom Property Value ID',
            'status' => 'Status',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}
