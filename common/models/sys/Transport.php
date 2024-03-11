<?php

namespace common\models\sys;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "sys_transport".
 *
 * @property string $transport_code 物流商代码
 * @property string $transport_name 物流商名
 * @property string $track_url 物流跟踪链接
 * @property int $status 0:未启用 10:已启用
 * @property int $param 参数
 * @property int $add_time 创建时间
 * @property int $update_time 更新时间
 */
class Transport extends BaseAR
{

    const STATUS_VALID = 10;
    const STATUS_INVALID = 20;

    public static $status_map = [
        self::STATUS_VALID => '启用',
        self::STATUS_INVALID => '禁用',
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%sys_transport}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status', 'add_time', 'update_time'], 'integer'],
            [['transport_code'], 'string', 'max' => 50],
            [['transport_name'], 'string', 'max' => 100],
            [['track_url'], 'string', 'max' => 256],
            [['param'], 'string', 'max' => 1000],
            [['transport_code'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'transport_code' => 'Transport Code',
            'transport_name' => 'Transport Name',
            'track_url' => 'Track Url',
            'status' => 'Status',
            'add_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}