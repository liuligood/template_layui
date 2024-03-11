<?php

namespace common\models\goods_shop;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%goods_shop_follow_sale}}".
 *
 * @property int $id
 * @property int $goods_shop_id 店铺商品id
 * @property int $shop_id 店铺id
 * @property string $cgoods_no 子商品编号
 * @property int $platform_type 所属平台
 * @property string $goods_url 商品链接
 * @property int $type 类型：1被跟卖，2非中国卖家跟卖,3没被跟卖，4找不到链接
 * @property string $sale_min_price 销售最低价
 * @property string $min_price 跟卖最低价
 * @property string $own_price 店铺价格
 * @property string $price 跟卖价
 * @property int $is_min_price 是否最低价
 * @property int $status 状态：1正常，2禁用
 * @property int $number 跟卖人数
 * @property string $currency 货币
 * @property int $adjustment_times 调整次数
 * @property int $plan_time 计划执行时间
 * @property int $last_time 最后一次调整时间
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class GoodsShopFollowSale extends BaseAR
{

    const STATUS_VALID = 1;//正常
    const STATUS_INVALID = 2;//禁用

    const TYPE_DEFAULT= 0;//待检测
    const TYPE_FOLLOW = 1;//被跟卖
    const TYPE_NON_CHINA = 2;//非中国卖家跟卖
    const TYPE_UNFOLLOW = 3;//没跟卖
    const TYPE_NOT_FIND = 4;//找不到产品
    const TYPE_FAIL = 5;//请求失败
    const TYPE_FOLLOW_OFF = 6;//被跟卖未出售
    const TYPE_UNFOLLOW_FOLLOW = 7;//曾经跟卖过
    const TYPE_LOW_PRICE_FOLLOW = 10;//超低价跟卖

    public static $type_show_map = [
        GoodsShopFollowSale::TYPE_FOLLOW => '被跟卖',
        GoodsShopFollowSale::TYPE_NON_CHINA => '非中国卖家跟卖',
        GoodsShopFollowSale::TYPE_LOW_PRICE_FOLLOW => '超低价跟卖',
        GoodsShopFollowSale::TYPE_UNFOLLOW_FOLLOW => '跟卖过',
        GoodsShopFollowSale::TYPE_FOLLOW_OFF => '被跟卖未出售',
    ];


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%goods_shop_follow_sale}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['goods_shop_id', 'shop_id', 'platform_type', 'type', 'is_min_price', 'status', 'number', 'adjustment_times', 'plan_time', 'add_time', 'update_time','last_time'], 'integer'],
            [['min_price','own_price','sale_min_price','price'], 'number'],
            [['cgoods_no'], 'string', 'max' => 24],
            [['goods_url'], 'string', 'max' => 1000],
            [['currency'], 'string', 'max' => 3],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'goods_shop_id' => 'Goods Shop ID',
            'shop_id' => 'Shop ID',
            'cgoods_no' => 'Cgoods No',
            'platform_type' => 'Platform Type',
            'goods_url' => 'Goods Url',
            'type' => 'Type',
            'min_price' => 'Min Price',
            'is_min_price' => 'Is Min Price',
            'status' => 'Status',
            'number' => 'Number',
            'currency' => 'Currency',
            'adjustment_times' => 'Adjustment Times',
            'plan_time' => 'Plan Time',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}