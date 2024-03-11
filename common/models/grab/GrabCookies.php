<?php

namespace common\models\grab;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%grab_cookies}}".
 *
 * @property int $id
 * @property int $platform_type 所属平台
 * @property string $cookie cookie
 * @property int $status 状态
 * @property int $exec_num 执行次数
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class GrabCookies extends BaseAR
{

    const STATUS_VALID = 1;//正常
    const STATUS_INVALID = 2;//禁用

    public static $status_maps = [
        self::STATUS_VALID => '正常',
        self::STATUS_INVALID => '禁用'
    ];

    public static function tableName()
    {
        return '{{%grab_cookies}}';
    }

    public function rules()
    {
        return [
            [['platform_type', 'status', 'exec_num', 'add_time', 'update_time'], 'integer'],
            [['cookie'], 'string'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'platform_type' => '所属平台',
            'cookie' => 'cookie',
            'status' => '状态',
            'exec_num' => '执行次数',
            'add_time' => '添加时间',
            'update_time' => '修改时间',
        ];
    }
}
