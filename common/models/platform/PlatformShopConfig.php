<?php

namespace common\models\platform;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%platform_shop_config}}".
 *
 * @property int $id
 * @property int $platform_type 所属平台
 * @property int $shop_id 店铺id
 * @property int $type 类型
 * @property string $type_id 类型id
 * @property string $type_val 类型值
 * @property int $status 状态
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class PlatformShopConfig extends BaseAR
{

    const TYPE_WAREHOUSE = 1;//仓库
    public static $warehousemap = [
        self::TYPE_WAREHOUSE => '仓库'
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%platform_shop_config}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['platform_type', 'shop_id', 'type', 'status', 'add_time', 'update_time'], 'integer'],
            [['type_id', 'type_val'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'platform_type' => 'Platform Type',
            'shop_id' => 'Shop ID',
            'type' => 'Type',
            'type_val' => 'Type Val',
            'status' => 'Status',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}