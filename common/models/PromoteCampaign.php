<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "ys_promote_campaign".
 *
 * @property int $id
 * @property int $platform_type 平台
 * @property int $shop_id 店铺id
 * @property int $promote_id 推广活动编号
 * @property string $promote_name 推广活动名称
 * @property int $status 状态 1启用 2禁用
 * @property int $type 类型 0展示 1点击
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class PromoteCampaign extends BaseAR
{
    const TYPE_SHOW = 0;//展示
    const TYPE_CLICK = 1;//点击

    public static $type_maps = [
        self::TYPE_SHOW => '展示',
        self::TYPE_CLICK => '点击'
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ys_promote_campaign';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['platform_type', 'shop_id', 'promote_id', 'status', 'add_time', 'update_time', 'type'], 'integer'],
            [['promote_name'], 'string', 'max' => 256],
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
            'promote_id' => 'Promote ID',
            'promote_name' => 'Promote Name',
            'status' => 'Status',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}
