<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%platform_information}}".
 *
 * @property int $id
 * @property string $goods_no 商品编号
 * @property string $platform_type 平台类型
 * @property string $o_category_name 外部类目名称
 * @property string $o_category_name_old 外部类目名称
 * @property string $attribute_value 属性值
 * @property string $editor_value 编辑器值
 * @property string $specs_value 规格值
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class PlatformInformation extends BaseAR
{

    public static function tableName()
    {
        return '{{%platform_information}}';
    }

    public function rules()
    {
        return [
            [['attribute_value', 'editor_value', 'specs_value'], 'string'],
            [['add_time', 'update_time', 'platform_type'], 'integer'],
            [['goods_no'], 'string', 'max' => 24],
            [['o_category_name','o_category_name_old'], 'string', 'max' => 256],
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
            'platform_type' => 'Platform Type',
            'o_category_name' => 'O Category Name',
            'attribute_value' => 'Attribute Value',
            'editor_value' => 'Editor Value',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}
