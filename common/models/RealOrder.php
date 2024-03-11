<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%real_order}}".
 *
 * @property integer $id
 * @property integer $shop_id
 * @property integer $date
 * @property string $country
 * @property string $city
 * @property string $area
 * @property string $order_id
 * @property string $user_no
 * @property string $asin
 * @property string $goods_name
 * @property integer $count
 * @property string $amazon_buy_url
 * @property string $amazon_price
 * @property string $real_price
 * @property string $real_order_amount
 * @property string $profit
 * @property string $image
 * @property string $specification
 * @property string $buyer_name
 * @property string $buyer_phone
 * @property string $postcode
 * @property string $address
 * @property string $swipe_buyer_id
 * @property string $amazon_order_id
 * @property string $logistics_id
 * @property string $real_track_no
 * @property integer $real_delivery_status
 * @property integer $real_order_status
 * @property integer $amazon_status
 * @property integer $amazon_arrival_time
 * @property integer $status
 * @property integer $admin_id
 * @property string $desc
 * @property integer $delete_time
 * @property string $new_order_id
 * @property integer $add_time
 * @property integer $update_time
 */
class RealOrder extends BaseAR
{

    const REAL_ORDER_NORMAL = 0;
    const REAL_ORDER_RETURN = 10;
    const REAL_ORDER_REFUND = 20;
    const REAL_ORDER_EXCHANGE = 30;

    public static $real_order_status_map = [
        self::REAL_ORDER_NORMAL => '已下单',
        self::REAL_ORDER_RETURN => '退货',
        self::REAL_ORDER_REFUND => '退款',
        self::REAL_ORDER_EXCHANGE => '换货',
    ];

    const REAL_DELIVERY_NORMAL = 0;
    const REAL_DELIVERY_RETURN = 10;
    const REAL_DELIVERY_NOT_TRACK = 20;


    public static $real_delivery_status_map = [
        self::REAL_DELIVERY_NORMAL => '未发货',
        self::REAL_DELIVERY_RETURN => '已发货',
        self::REAL_DELIVERY_NOT_TRACK => '无跟踪信息发',
    ];

    //亚马逊状态
    const AMAZON_STATUS_NONE = 0;
    const AMAZON_STATUS_BUY = 10;
    const AMAZON_STATUS_OUT_STOCK = 20;
    const AMAZON_STATUS_RETURN = 30;
    const AMAZON_STATUS_REFUND = 40;
    const AMAZON_STATUS_EXCHANGE = 50;

    public static $amazon_status_map = [
        self::AMAZON_STATUS_NONE => '未下单',
        self::AMAZON_STATUS_BUY => '已购买',
        self::AMAZON_STATUS_OUT_STOCK => '缺货',
        self::AMAZON_STATUS_RETURN => '退货',
        self::AMAZON_STATUS_REFUND => '退款',
        self::AMAZON_STATUS_EXCHANGE => '换货',
    ];



    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%real_order}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['date', 'count', 'shop_id', 'status', 'add_time', 'update_time','real_delivery_status','amazon_arrival_time','real_order_status','admin_id','amazon_status','delete_time'], 'integer'],
            [['amazon_price', 'real_price', 'real_order_amount', 'profit'], 'number'],
            [['country', 'city', 'area', 'postcode', 'swipe_buyer_id', 'amazon_order_id', 'logistics_id','real_track_no','user_no'], 'string', 'max' => 60],
            [['order_id','specification','buyer_name'], 'string', 'max' => 128],
            [['asin'], 'string', 'max' => 30],
            [['amazon_buy_url','desc'], 'string', 'max' => 1000],
            [['buyer_phone'], 'string', 'max' => 20],
            [['address','image','goods_name','new_order_id'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'date' => 'Date',
            'shop_id' => 'Shop Id',
            'country' => 'Country',
            'city' => 'City',
            'area' => 'Area',
            'order_id' => 'Order ID',
            'asin' => 'Asin',
            'count' => 'Count',
            'amazon_buy_url' => 'Buy Url',
            'amazon_price' => 'Amazon Price',
            'real_price' => 'Real Price',
            'real_order_amount' => 'Real Order Amount',
            'profit' => 'Profit',
            'buyer_name' => 'Buyer Name',
            'buyer_phone' => 'Buyer Phone',
            'postcode' => 'Postcode',
            'address' => 'Address',
            'swipe_buyer_id' => 'Swipe Buyer ID',
            'amazon_order_id' => 'Amazon Order ID',
            'logistics_id' => 'Logistics ID',
            'status' => 'Status',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}