<?php

namespace common\models\goods_shop;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%goods_shop_follow_sale_log}}".
 *
 * @property int $id
 * @property int $goods_shop_id 店铺商品id
 * @property int $shop_id 店铺id
 * @property string $cgoods_no 子商品编号
 * @property int $platform_type 所属平台
 * @property string $show_cur_price 当前价格（显示）
 * @property string $show_follow_price 店铺预计跟卖价格（显示）
 * @property string $show_own_price 店铺价格（显示）
 * @property string $show_currency 货币（显示）
 * @property string $cur_price 当前价格
 * @property string $follow_price 跟卖价格
 * @property string $currency 货币
 * @property string $weight 重量
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class GoodsShopFollowSaleLog extends BaseAR
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%goods_shop_follow_sale_log}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['goods_shop_id', 'shop_id', 'platform_type', 'add_time', 'update_time'], 'integer'],
            [['show_cur_price', 'show_own_price', 'show_follow_price', 'cur_price', 'follow_price', 'weight'], 'number'],
            [['cgoods_no'], 'string', 'max' => 24],
            [['show_currency', 'currency'], 'string', 'max' => 3],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'goods_shop_id' => '店铺商品id',
            'shop_id' => '店铺id',
            'cgoods_no' => '子商品编号',
            'platform_type' => '所属平台',
            'show_cur_price' => '当前价格（显示）',
            'show_follow_price' => '店铺预计跟卖价格（显示）',
            'show_currency' => '货币（显示）',
            'cur_price' => '当前价格',
            'follow_price' => '跟卖价格',
            'currency' => '货币',
            'weight' => '重量',
            'add_time' => '添加时间',
            'update_time' => '修改时间',
        ];
    }
}