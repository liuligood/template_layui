<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%order_goods}}".
 *
 * @property int $id
 * @property string $order_id 订单号
 * @property string $goods_no 商品编号
 * @property string $cgoods_no 子商品编号
 * @property string $goods_name 商品名称
 * @property int $goods_num 数量
 * @property string $goods_pic 商品图片
 * @property string $goods_specification 规格型号
 * @property string $goods_income_price 售价
 * @property string $goods_cost_price 成本
 * @property string $goods_profit 利润
 * @property int $platform_type 平台类型
 * @property string $platform_asin 平台asin码
 * @property int $goods_status 状态
 * @property int $source_method 来源方式
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class OrderGoods extends BaseAR
{

    const GOODS_STATUS_UNCONFIRMED = 0;//未确认
    const GOODS_STATUS_NORMAL = 10;//正常
    const GOODS_STATUS_CANCEL = 20;//取消

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%order_goods}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['goods_num', 'goods_status', 'add_time', 'update_time','platform_type','source_method'], 'integer'],
            [['goods_income_price', 'goods_cost_price', 'goods_profit'], 'number'],
            [['goods_name'], 'string', 'max' => 255],
            [['goods_pic'], 'string', 'max' => 400],
            [['goods_specification'], 'string', 'max' => 100],
            [['platform_asin','order_id'], 'string'],
            [['goods_no', 'cgoods_no'], 'string', 'max' => 24],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => 'Order ID',
            'goods_name' => 'Goods Name',
            'goods_num' => 'Goods Num',
            'goods_pic' => 'Goods Pic',
            'goods_specification' => 'Goods Specification',
            'goods_income_price' => 'Goods Income Price',
            'goods_cost_price' => 'Goods Cost Price',
            'goods_profit' => 'Goods Profit',
            'platform_type' => 'Platform Type',
            'platform_asin' => 'Platform Asin',
            'goods_status' => 'Goods Status',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}