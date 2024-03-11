<?php

namespace common\models\sys;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%frequently_operations}}".
 *
 * @property int $id
 * @property int $type 类型
 * @property string $type_id 类型id
 * @property int $admin_id 管理员id
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class FrequentlyOperations extends BaseAR
{

    const TYPE_SHELVES = 1;//货架
    const TYPE_CATEGORY = 2;//分类
    const TYPE_CURRENCY = 3;//货币

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%frequently_operations}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type', 'admin_id', 'add_time', 'update_time'], 'integer'],
            [['type_id'], 'string', 'max' => 32],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type' => 'Type',
            'type_id' => 'Type ID',
            'admin_id' => 'Admin ID',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}