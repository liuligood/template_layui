<?php

namespace common\models\platform;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%platform_category_field_value}}".
 *
 * @property int $id
 * @property int $platform_type 所属平台
 * @property string $category_id 类目id
 * @property string $dictionary_id 字典id
 * @property string $attribute_id 属性id
 * @property string $attribute_value_id 属性值id
 * @property string $attribute_value 属性值
 * @property string $attribute_value_cn 属性值中文
 * @property string $attribute_value_desc 属性描述
 * @property int $status 状态
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class PlatformCategoryFieldValue extends BaseAR
{

    /**
     * 所属平台
     * @var
     */
    public static $platform;

    /**
     * 设置平台
     * @param $platform
     * @return void
     */
    public static function setPlatform($platform)
    {
        self::$platform = $platform;
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        $platform_type = self::$platform;
        return '{{%platform_category_field_value_'.$platform_type.'}}';
        //return '{{%platform_category_field_value}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['platform_type', 'status', 'add_time', 'update_time'], 'integer'],
            [['category_id', 'attribute_id', 'attribute_value_id'], 'string', 'max' => 64],
            [['dictionary_id'], 'string', 'max' => 30],
            [['attribute_value', 'attribute_value_cn'], 'string', 'max' => 300],
            [['attribute_value_desc'], 'string'],
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
            'dictionary_id' => 'Dictionary ID',
            'attribute_id' => 'Attribute ID',
            'attribute_value_id' => 'Attribute Value ID',
            'attribute_value' => 'Attribute Value',
            'attribute_value_cn' => 'Attribute Value Cn',
            'status' => 'Status',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}