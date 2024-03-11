<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "ys_promote_campaign_details".
 *
 * @property int $id
 * @property int $platform_type 平台
 * @property int $shop_id 店铺id
 * @property int $promote_id 推广活动id
 * @property int $promote_name 推广活动编号
 * @property string $cgoods_no 子商品id
 * @property string $platform_goods_opc 平台商品编号
 * @property int $impressions 展示量
 * @property int $hits 点击量
 * @property float $promotes 推广费用
 * @property int $order_volume 订单量
 * @property float $order_sales 订单销售额
 * @property int $model_orders 型号订单量
 * @property float $model_sales 型号订单销售额
 * @property int $promote_time 添加时间
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class PromoteCampaignDetails extends BaseAR
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ys_promote_campaign_details';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['promote_name','platform_type', 'shop_id', 'promote_id', 'impressions', 'hits',  'order_volume', 'model_orders',  'promote_time', 'add_time', 'update_time'], 'integer'],
            [[ 'cgoods_no','platform_goods_opc'], 'string', 'max' => 256],
            [[ 'promotes','model_sales', 'order_sales'], 'number'],
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
            'cgoods_no' => 'Children Goods ID',
            'impressions' => 'Impressions',
            'hits' => 'Hits',
            'promotes' => 'Promotes',
            'order_volume' => 'Order Volume',
            'order_sales' => 'Order Sales',
            'model_orders' => 'Model Orders',
            'model_sales' => 'Model Sales',
            'promote_time' => 'Promote Time',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}
