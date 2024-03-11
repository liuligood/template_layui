<?php

namespace common\models\platform;

use common\models\BaseAR;

use Yii;

/**
 * This is the model class for table "{{%platform_category_attribute}}".
 *
 * @property int $id
 * @property int $platform_type 所属平台
 * @property string $category_id 类目id
 * @property string $attribute_id 属性id
 * @property string $attribute_name 属性名称
 * @property string $attribute_value 属性值
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class PlatformCategoryAttribute extends BaseAR
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%platform_category_attribute}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['platform_type', 'add_time', 'update_time'], 'integer'],
            [['category_id', 'attribute_id'], 'string', 'max' => 64],
            [['attribute_name'], 'string', 'max' => 100],
            [['attribute_value'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'platform_type' => 'Platform Type',
            'category_id' => 'Category ID',
            'attribute_id' => 'Attribute ID',
            'attribute_name' => 'Attribute Name',
            'attribute_value' => 'Attribute Value',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}