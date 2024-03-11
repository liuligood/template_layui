<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%buy_goods}}".
 *
 * @property int $id
 * @property string $order_id 订单号
 * @property int $order_goods_id 商品订单id
 * @property int $source_method 来源方式
 * @property int $platform_type 平台类型
 * @property string $asin asin码
 * @property int $buy_goods_num 商品数量
 * @property string $buy_goods_url 商品链接
 * @property string $buy_goods_pic 商品图片
 * @property string $buy_goods_price 商品价格
 * @property string $swipe_buyer_id 刷单买家号
 * @property string $buy_relation_no 亚马逊订单号
 * @property string $logistics_id 亚马逊物流单号
 * @property int $arrival_time 预计到货时间
 * @property int $buy_goods_status 购买状态
 * @property int $after_sale_status 售后状态
 * @property int $logistics_channels_id 物流渠道
 * @property string $track_no 物流订单号
 * @property int $check_stock_time 检测库存时间
 * @property string $remarks 备注
 * @property int $after_sale_time 申请售后时间
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class BuyGoods extends BaseAR
{

    //状态
    const BUY_GOODS_STATUS_NONE = 0;
    const BUY_GOODS_STATUS_OUT_STOCK = 1;
    const BUY_GOODS_STATUS_ERROR_CON = 2;
    const BUY_GOODS_STATUS_IN_STOCK = 3;
    const BUY_GOODS_STATUS_BUY = 10;
    const BUY_GOODS_STATUS_DELIVERY = 20;
    const BUY_GOODS_STATUS_FINISH = 30;
    const BUY_GOODS_STATUS_DELETE = 90;

    public static $buy_goods_unpay_status_map = [
        self::BUY_GOODS_STATUS_NONE => '未购买',
        self::BUY_GOODS_STATUS_BUY => '已购买',
        self::BUY_GOODS_STATUS_OUT_STOCK => '缺货',
        self::BUY_GOODS_STATUS_ERROR_CON => '信息错误',
        self::BUY_GOODS_STATUS_IN_STOCK => '已有货',
    ];

    public static $buy_goods_status_map = [
        self::BUY_GOODS_STATUS_NONE => '未购买',
        self::BUY_GOODS_STATUS_BUY => '已购买',
        self::BUY_GOODS_STATUS_DELIVERY => '已发货',
        self::BUY_GOODS_STATUS_FINISH => '已完成',
        self::BUY_GOODS_STATUS_OUT_STOCK => '缺货',
        self::BUY_GOODS_STATUS_ERROR_CON => '信息错误',
        self::BUY_GOODS_STATUS_DELETE => '已取消',
    ];

    //售后状态
    const AFTER_SALE_STATUS_NONE = 0;
    const AFTER_SALE_STATUS_RETURN = 10;
    const AFTER_SALE_STATUS_REFUND = 20;
    const AFTER_SALE_STATUS_EXCHANGE = 30;

    public static $after_sale_status_map = [
        self::AFTER_SALE_STATUS_NONE => '无',
        self::AFTER_SALE_STATUS_RETURN => '退货',
        self::AFTER_SALE_STATUS_REFUND => '退款',
        self::AFTER_SALE_STATUS_EXCHANGE => '换货',
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%buy_goods}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['order_goods_id', 'platform_type', 'buy_goods_num', 'arrival_time', 'buy_goods_status', 'after_sale_status', 'logistics_channels_id', 'add_time', 'update_time', 'after_sale_time','check_stock_time','source_method'], 'integer'],
            [['buy_goods_price'], 'number'],
            [['order_id', 'asin'], 'string', 'max' => 32],
            [['buy_goods_url'], 'string', 'max' => 1000],
            [['buy_goods_pic'], 'string', 'max' => 300],
            [['swipe_buyer_id', 'buy_relation_no', 'logistics_id','track_no'], 'string', 'max' => 60],
            [['remarks'], 'string', 'max' => 1000],
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
            'order_goods_id' => 'Order Goods ID',
            'source_method' => 'source method',
            'platform_type' => 'Platform Type',
            'asin' => 'Asin',
            'buy_goods_url' => 'Buy Goods Url',
            'buy_goods_pic' => 'Buy Goods Pic',
            'swipe_buyer_id' => 'Swipe Buyer ID',
            'buy_relation_no' => 'Buy Relation No',
            'logistics_id' => 'Logistics ID',
            'arrival_time' => 'Arrival Time',
            'buy_goods_status' => 'Buy Goods Status',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}