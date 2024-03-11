<?php

namespace common\models\sys;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%sys_ean}}".
 *
 * @property int $id
 * @property string $ean ean
 * @property string $status 状态
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class Ean extends BaseAR
{

    const STATUS_DEFAULT = 0;//正常
    const STATUS_USE = 1;//已使用
    const STATUS_CANCEL = -1;//作废

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%sys_ean}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status', 'add_time', 'update_time'], 'integer'],
            [['ean'], 'string', 'max' => 24],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'ean' => 'ean',
            'status' => 'status',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }

}