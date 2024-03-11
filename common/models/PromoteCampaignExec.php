<?php
namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%promote_campaign_exec}}".
 *
 * @property int $id
 * @property int $platform_type 平台
 * @property int $shop_id 店铺id
 * @property string $promote_ids 推广活动编号
 * @property string $next_promote_ids 下次推广活动编号
 * @property string $start_day 开始日期
 * @property string $end_day 结束日期
 * @property string $uuid UUID
 * @property int $status 状态：0待执行,1执行成功,2执行失败
 * @property int $plan_time 计划执行时间
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class PromoteCampaignExec extends BaseAR
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%promote_campaign_exec}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['platform_type', 'shop_id', 'status', 'plan_time', 'add_time', 'update_time'], 'integer'],
            [['start_day', 'end_day'], 'string', 'max' => 24],
            [['uuid'], 'string', 'max' => 64],
            [['promote_ids','next_promote_ids'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'platform_type' => '平台',
            'shop_id' => '店铺id',
            'promote_ids' => '推广活动编号',
            'start_day' => '开始日期',
            'end_day' => '结束日期',
            'uuid' => 'UUID',
            'status' => '状态：0待执行,1执行成功,2执行失败',
            'plan_time' => '计划执行时间',
            'add_time' => '添加时间',
            'update_time' => '修改时间',
        ];
    }
}