<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%category_mapping}}".
 *
 * @property int $id
 * @property int $category_id 类目id
 * @property int $platform_type 所属平台
 * @property string $o_category_name 外部类目名称
 * @property string $o_category_name_old 外部类目名称
 * @property string $file 文件
 * @property string $attribute_value 属性值
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class CategoryMapping extends BaseAR
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%category_mapping}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['category_id', 'platform_type', 'add_time', 'update_time'], 'integer'],
            [['o_category_name','file','o_category_name_old'], 'string', 'max' => 256],
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
            'category_id' => '类目id',
            'platform_type' => '所属平台',
            'o_category_name' => '外部类目名称',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}