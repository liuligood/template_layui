<?php

namespace common\models;

use common\services\id\OrderIdService;
use Yii;
use yii\base\Exception;

/**
 * This is the model class for table "{{%order}}".
 *
 * @property int $id
 * @property int $source_method 来源方式
 * @property int $create_way 创建方式
 * @property string $order_id 订单号
 * @property string $relation_no 销售单号
 * @property int $source 来源
 * @property string $ean EAN
 * @property int $shop_id 店铺id
 * @property int $date 下单时间
 * @property string $country 国家
 * @property string $city 城市
 * @property string $area 区
 * @property string $user_no 客户编号
 * @property string $company_name 公司名称
 * @property string $buyer_name 买家名称
 * @property string $buyer_phone 电话
 * @property string $postcode 邮编
 * @property string $email 邮箱
 * @property string $address 地址
 * @property string $tax_number 税号
 * @property string $tax_relation_no 对应ioss销售单号
 * @property int $tax_number_use 税号是否被使用
 * @property string $order_income_price 订单收入金额
 * @property string $order_cost_price 订单成本金额
 * @property string $order_profit 订单利润
 * @property string $platform_fee 平台费用
 * @property int $logistics_channels_id 物流渠道
 * @property string $logistics_channels_name 物流渠道名
 * @property string $delivery_order_id 物流商物流订单号
 * @property string $track_no 物流订单号
 * @property string $track_logistics_no 物流转单号
 * @property int $delivery_status 发货状态
 * @property int $pdelivery_status 平台发货状态
 * @property int $order_status 订单状态
 * @property int $printed_status 打印状态
 * @property int $after_sale_status 售后状态
 * @property int $logistics_reset_times 物流重新申请次数
 * @property int $admin_id 添加管理员
 * @property int $arrival_time 预计到货时间
 * @property int $delivery_time 发货时间
 * @property string $freight_price 运费
 * @property string $weight 重量
 * @property string $size 尺寸
 * @property string $remarks 备注
 * @property int $warehouse 所属仓库
 * @property int $abnormal_time 移入异常时间
 * @property int $delivered_time 交换时间（到货时间）
 * @property int $cancel_time 取消时间
 * @property int $cancel_reason 取消原因
 * @property string $cancel_remarks 取消备注
 * @property int $settlement_status 结算状态
 * @property string $logistics_pdf 物流面单
 * @property string $logistics_pdf1 物流面单
 * @property string $first_track_no 首程运单号（国内运单号）
 * @property int $integrated_logistics 是否集成物流
 * @property string $currency 货币
 * @property string $exchange_rate 汇率
 * @property int $remaining_shipping_time 剩余发货时间
 * @property int $exec_status_time 执行状态时间
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class Order extends BaseAR
{

    //创建方式
    const CREATE_WAY_BACKSTAGE = 1;//后台
    const CREATE_WAY_SYSTEM = 2;//系统

    public static $create_way = [
        self::CREATE_WAY_BACKSTAGE => '后台录单',
        self::CREATE_WAY_SYSTEM => '系统下单',
    ];


    //未确认（信息未录入完）
    //待采购（已确认 = 待亚马逊购买）
    //待发货（已采购 = 已亚马逊购买）
    //已发货（订单完成）
    //订单取消
    const ORDER_STATUS_UNCONFIRMED = 0;//未确认
    const ORDER_STATUS_WAIT_PURCHASE = 10;//待采购  //待处理

    const ORDER_STATUS_APPLY_WAYBILL = 12;//申请运单号
    const ORDER_STATUS_WAIT_PRINTED_OUT_STOCK = 14;//缺货
    const ORDER_STATUS_WAIT_PRINTED = 16;//待打单


    const ORDER_STATUS_WAIT_SHIP = 20;//待发货
    const ORDER_STATUS_SHIPPED = 30;//已发货
    const ORDER_STATUS_CANCELLED = 40;//已取消
    const ORDER_STATUS_FINISH = 50;//已完成
    const ORDER_STATUS_REFUND = 60;//已退款

    public static $order_status_map = [
        self::ORDER_STATUS_UNCONFIRMED => '未确认',
        self::ORDER_STATUS_WAIT_PURCHASE => '待处理',
        self::ORDER_STATUS_APPLY_WAYBILL => '申请运单号',
        self::ORDER_STATUS_WAIT_PRINTED_OUT_STOCK => '待打单|缺货',
        self::ORDER_STATUS_WAIT_PRINTED => '待打单',
        self::ORDER_STATUS_WAIT_SHIP => '待发货',
        self::ORDER_STATUS_SHIPPED => '已发货',
        self::ORDER_STATUS_FINISH => '已完成',
        self::ORDER_STATUS_CANCELLED => '已取消',
        self::ORDER_STATUS_REFUND => '已退款',
    ];

    public static $cancel_reason_map = [
        1=>'亏本不发',
        2=>'买家取消',
        3=>'全网缺货',
        4=>'无法寄送',
        9=>'其他',
    ];

    public static $refund_reason_map = [
        101 => '延迟交付',
        102 => '产品破损',
        103 => '买家退货',
        109 => '其他',
    ];
    
    public static $order_finsh_shipped = [
        self::ORDER_STATUS_FINISH,//已完成
        self::ORDER_STATUS_SHIPPED,//已发货
    ];

    //显示剩余发货时间状态
    public static $order_remaining_maps = [
        self::ORDER_STATUS_UNCONFIRMED,
        self::ORDER_STATUS_WAIT_PURCHASE,
        self::ORDER_STATUS_APPLY_WAYBILL,
        self::ORDER_STATUS_WAIT_PRINTED,
        self::ORDER_STATUS_WAIT_PRINTED_OUT_STOCK,
        self::ORDER_STATUS_WAIT_SHIP
    ];

    //发货状态
    const DELIVERY_NORMAL = 0;
    const DELIVERY_SHIPPED = 10;
    const DELIVERY_NOT_TRACK = 20;
    const DELIVERY_ARRIVAL = 30;//到货
    public static $delivery_status_map = [
        self::DELIVERY_NORMAL => '未发货',
        self::DELIVERY_SHIPPED => '已发货',
        self::DELIVERY_NOT_TRACK => '无跟踪信息发',
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


    //虚拟发货
    const PDELIVERY_NORMAL = 0;
    const PDELIVERY_SHIPPED = 10;

    /**
     * 打印面单
     */
    const PRINTED_STATUS_L = 1;
    /**
     * 打印拣货单
     */
    const PRINTED_STATUS_I = 2;

    public static $printed_status_map = [
        self::PRINTED_STATUS_L => '打印面单',
        self::PRINTED_STATUS_I => '打印拣货单'
    ];

    const TAX_NUMBER_USE_YES = 1;
    const TAX_NUMBER_USE_NO = 0;
    public static $tax_number_use_map = [
        self::TAX_NUMBER_USE_NO => '否',
        self::TAX_NUMBER_USE_YES => '是',
    ];

    const INTEGRATED_LOGISTICS_YES = 1;
    const INTEGRATED_LOGISTICS_NO = 0;

    //结算状态
    const SETTLEMENT_STATUS_COST = 1;//成本结算
    const SETTLEMENT_STATUS_SALE = 2;//销售结算

    public static $settlement_status_map = [
        self::SETTLEMENT_STATUS_COST => '成本结算',
        self::SETTLEMENT_STATUS_SALE => '销售结算',
    ];

    /**
     * 物流渠道
     * @var array
     */
    public static $logistics_channels_map = [
        1 => 'DHL',
        2 => 'DPD',
        3 => 'Hermes',
        4 => 'GLS',
        5 => 'UPS',
        6 => 'Yun Express',
        7 => 'La Poste',
        8 => 'China Post',
        9 => 'Chronopost',
        10 => 'Deutsche Post',
        11 => 'CNE Express',
        12 => 'Fedex',
        13 => '4PX',
        14 => 'PostNL',
        15 => 'Yanwen',
        16 => 'Amazon Logistics UK',
    ];

    const ID_PREFIX = "O";

    

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%order}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['date', 'source_method', 'warehouse', 'delivery_status', 'pdelivery_status', 'printed_status', 'order_status', 'admin_id', 'shop_id', 'add_time', 'update_time', 'source', 'after_sale_status','arrival_time','logistics_channels_id','delivery_time','create_way','logistics_reset_times','tax_number_use','abnormal_time','cancel_reason','cancel_time','delivered_time','settlement_status','integrated_logistics','remaining_shipping_time','exec_status_time'], 'integer'],
            [['order_income_price', 'order_cost_price', 'order_profit', 'freight_price', 'weight', 'platform_fee','exchange_rate'], 'number'],
            [['relation_no','tax_relation_no','tax_number','size'], 'string', 'max' => 64],
            [['order_id','first_track_no'], 'string', 'max' => 32],
            [['country', 'user_no', 'postcode', 'track_no','track_logistics_no'], 'string', 'max' => 60],
            [['buyer_name','company_name','email','logistics_channels_name', 'area','city','delivery_order_id'], 'string', 'max' => 120],
            [['buyer_phone','ean','currency'], 'string', 'max' => 30],
            [['address','logistics_pdf','logistics_pdf1'], 'string', 'max' => 500],
            [['remarks','cancel_remarks'], 'string', 'max' => 1000],
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
            'date' => 'Date',
            'country' => 'Country',
            'city' => 'City',
            'area' => 'Area',
            'user_no' => 'User No',
            'buyer_name' => 'Buyer Name',
            'buyer_phone' => 'Buyer Phone',
            'postcode' => 'Postcode',
            'address' => 'Address',
            'order_income_price' => 'Order Income Price',
            'order_cost_price' => 'Order Cost Price',
            'order_profit' => 'Order Profit',
            'track_no' => 'Track No',
            'delivery_status' => 'Delivery Status',
            'order_status' => 'Order Status',
            'admin_id' => 'Admin ID',
            'remarks' => 'Remarks',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }

    public function beforeSave($insert)
    {
        if ($insert) {
            if (empty($this->order_id)) {
                $id_server = new OrderIdService();
                $this->order_id =  self::ID_PREFIX . $id_server->getNewId();
            }
        }
        return parent::beforeSave($insert);
    }

    /**
     * 添加订单信息
     * @param array $data
     * @return string
     * @throws Exception
     */
    public static function addOrder($data = [])
    {
        if (empty($data) || !is_array($data)) {
            $message = !is_array($data) ? 'data参数错误' : 'data参数为空';
            throw new Exception($message);
        }

        $model = new Order();
        $model->load($data, '');
        if ($model->validate() && $model->save()) {
            return $model->order_id;
        } else {
            throw new Exception(current($model->getFirstErrors()));
        }
    }

    /**
     * 根据条件 获取订单总数
     * @param array $where
     * @return int
     */
    public static function getCountByCond($where = [],$query = null)
    {
        return parent::getCacheCountByCond($where,$query,__CLASS__.__FUNCTION__);
    }

    /**
     * 修改订单信息
     * @param $order_id
     * @param array $data
     * @return bool
     * @throws Exception
     */
    public static function updateOneByOrderId($order_id, $data = [])
    {
        $model = self::findOne(['order_id' => $order_id]);
        if (empty($model) || empty($data)) {
            $message = empty($model) ? '订单不存在' : 'data参数为空';
            throw new Exception($message);
        }

        $model->load($data, '');
        if ($model->validate() && $model->save()) {
            return true;
        } else {
            throw new Exception(current($model->getFirstErrors()));
        }

    }
}