<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "ys_configs".
 *
 * @property int $id
 * @property string $code 配置代码
 * @property string $name 配置名称
 * @property string $type 类型：text单行文本，textarea多行文本，radio单选，checkbox多选，select下拉
 * @property int $width 输入框宽度
 * @property string $value 配置值
 * @property string $option $option
 * @property string $desc 说明
 * @property int $admin_id 操作者
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class Configs extends \yii\db\ActiveRecord
{
    const CONFIGS_TYPE_ONE = 1;
    const CONFIGS_TYPE_TWO = 2;
    const CONFIGS_TYPE_THREE = 3;
    const CONFIGS_TYPE_FOURTH = 4;
    const CONFIGS_TYPE_FIRTH = 5;
    public static $type_map = [
        self::CONFIGS_TYPE_ONE => "text",
        self::CONFIGS_TYPE_TWO => "textarea",
        self::CONFIGS_TYPE_THREE => "radio",
        self::CONFIGS_TYPE_FOURTH => "checkbox",
        self::CONFIGS_TYPE_FIRTH => "select",
        ];
    public static $type_change_map= [
        "text" => self::CONFIGS_TYPE_ONE,
        "textarea"  => self::CONFIGS_TYPE_TWO,
        "radio" => self::CONFIGS_TYPE_THREE,
        "checkbox" =>self::CONFIGS_TYPE_FOURTH ,
        "select" => self::CONFIGS_TYPE_FIRTH,
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ys_configs';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['width', 'admin_id', 'add_time', 'update_time'], 'integer'],
            [['code', 'name'], 'string', 'max' => 30],
            [['type'], 'string', 'max' => 20],
            [['value'], 'string', 'max' => 100],
            [['option', 'desc'], 'string', 'max' => 1000],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'code' => 'Code',
            'name' => 'Name',
            'type' => 'Type',
            'width' => 'Width',
            'value' => 'Value',
            'option' => 'Option',
            'desc' => 'Desc',
            'admin_id' => 'Admin ID',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}
