<?php

namespace common\models\platform;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%platform_category_field}}".
 *
 * @property int $id
 * @property int $platform_type 所属平台
 * @property string $category_id 类目id
 * @property string $attribute_type 属性类型
 * @property string $attribute_id 属性id
 * @property string $attribute_name 属性名称
 * @property string $attribute_name_cn 属性名称中文
 * @property string $attribute_desc 属性描述
 * @property int $is_required 是否必填
 * @property int $is_multiple 是否多选
 * @property int $dictionary_id 字典id
 * @property int $status 状态
 * @property int $param 参数
 * @property int $unit 单位
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class PlatformCategoryField extends BaseAR
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%platform_category_field}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['platform_type', 'is_required', 'is_multiple', 'status', 'add_time', 'update_time'], 'integer'],
            [['category_id', 'attribute_id'], 'string', 'max' => 64],
            [['attribute_type'], 'string', 'max' => 30],
            [['dictionary_id','unit'], 'string', 'max' => 24],
            [['attribute_name', 'attribute_name_cn'], 'string', 'max' => 100],
            [['param','attribute_desc'], 'string'],
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
            'attribute_type' => 'Attribute Type',
            'attribute_id' => 'Attribute ID',
            'attribute_name' => 'Attribute Name',
            'attribute_name_cn' => 'Attribute Name Cn',
            'is_required' => 'Is Required',
            'is_multiple' => 'Is Multiple',
            'dictionary_id' => 'Dictionary ID',
            'status' => 'Status',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}