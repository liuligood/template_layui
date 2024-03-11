<?php

namespace common\models\warehousing;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%warehouse_provider}}".
 *
 * @property int $id
 * @property string $warehouse_provider_name 海外仓名称
 * @property int $warehouse_provider_type 海外仓类型
 * @property int $warehouse_provider_code 海外仓编码
 * @property int $status 1:启用,2:禁用
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class WarehouseProvider extends BaseAR
{
    //状态
    const STATUS_ENABLE = 1;//启用
    const STATUS_DISABLE = 2;//禁用

    //类型
    const TYPE_PLATFORM = 10;//平台海外仓
    const TYPE_THIRD_PARTY = 20;//第三方海外仓
    const TYPE_B2B = 30;//B2B平台海外仓
    const TYPE_LOCAL = 40;//自建本地仓
    const TYPE_LOCAL_THIRD_PARTY = 50;//第三方本地仓

    public static $status_maps = [
        self::STATUS_ENABLE => '启用',
        self::STATUS_DISABLE => '禁用'
    ];

    public static $type_maps = [
        self::TYPE_LOCAL => '自建本地仓',
        self::TYPE_LOCAL_THIRD_PARTY => '第三方本地仓',
        self::TYPE_PLATFORM => '平台海外仓',
        self::TYPE_THIRD_PARTY => '第三方海外仓',
        self::TYPE_B2B => 'B2B平台海外仓'
    ];

    public static function tableName()
    {
        return '{{%warehouse_provider}}';
    }

    public function rules()
    {
        return [
            [['warehouse_provider_type', 'status', 'add_time', 'update_time'], 'integer'],
            [['warehouse_provider_name','warehouse_provider_code'], 'string', 'max' => 100],
        ];
    }
    
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'warehouse_provider_name' => 'Warehouse Provider Name',
            'warehouse_provider_type' => 'Warehouse Provider Type',
            'status' => 'Status',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}
