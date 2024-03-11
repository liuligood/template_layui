<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%goods_stock_check_cycle}}".
 *
 * @property int $id
 * @property string $name 名称
 * @property int $status 状态
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class GoodsStockCheckCycle extends BaseAR
{

    const STATUS_NONE = 0;
    const STATUS_FINISH = 10;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%goods_stock_check_cycle}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['status', 'add_time', 'update_time'], 'integer'],
            [['name'], 'string', 'max' => 256],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'status' => 'Status',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}
