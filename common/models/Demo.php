<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%demo}}".
 *
 * @property int $id
 * @property string $title 标题
 * @property string $desc 备注
 * @property int $status 状态
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class Demo extends BaseAR
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%demo}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['desc'], 'string'],
            [['status', 'add_time', 'update_time'], 'integer'],
            [['title'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'desc' => 'Desc',
            'status' => 'Status',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}