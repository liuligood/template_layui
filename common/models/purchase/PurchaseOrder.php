<?php

namespace common\models\purchase;

use common\models\BaseAR;
use common\services\id\PurchaseOrderIdService;
use Yii;
use yii\base\Exception;

/**
 * This is the model class for table "{{%purchase_order}}".
 *
 * @property int $id
 * @property int $create_way 创建方式
 * @property int $source 来源
 * @property int $supplier_id 供应商id
 * @property string $order_id 订单号
 * @property string $relation_no 销售单号
 * @property string $goods_price 商品金额
 * @property string $freight_price 运费
 * @property string $other_price 其他费用
 * @property string $order_price 订单金额
 * @property int $logistics_channels_id 物流渠道
 * @property string $track_no 物流订单号
 * @property int $arrival_time 到货时间
 * @property int $ship_time 发货时间
 * @property int $warehouse 所属仓库
 * @property int $delivery_status 发货状态
 * @property int $order_status 订单状态
 * @property int $order_sub_status 订单子状态
 * @property int $logistics_status 物流状态
 * @property string $remarks 备注
 * @property int $admin_id 添加管理员
 * @property int $date 下单时间
 * @property int $plan_time 计划执行时间
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class PurchaseOrder extends BaseAR
{

    //创建方式
    const CREATE_WAY_BACKSTAGE = 1;//后台添加
    const CREATE_WAY_ASSOCIATE = 2;//关联

    //未确认（信息未录入完）
    //待采购（已确认）
    //待发货（已采购）
    //已发货（订单完成）
    //订单取消
    const ORDER_STATUS_UNCONFIRMED = 0;//未确认
    const ORDER_STATUS_WAIT_SHIP = 10;//待发货
    const ORDER_STATUS_SHIPPED = 20;//已发货
    const ORDER_STATUS_RECEIVED = 30;//已完成
    const ORDER_STATUS_CANCELLED = 40;//已取消

    const ORDER_SUB_STATUS_SHIPPED = 0;//已发货-未到货
    const ORDER_SUB_STATUS_SHIPPED_PART = 201;//已发货-部分未到货

    const ORDER_SUB_STATUS_RECEIVED = 0;//已完成-全部到货
    const ORDER_SUB_STATUS_RECEIVED_PART = 301;//已完成-部分未到货

    //物流状态
    const LOGISTICS_STATUS_WAIT = 0;//未出发
    const LOGISTICS_STATUS_ON_WAY = 1;//已出发

    public static $order_start_map = [
        self::ORDER_STATUS_WAIT_SHIP =>'待发货',
        self::ORDER_STATUS_SHIPPED =>'已发货',
        self::ORDER_STATUS_RECEIVED =>'已完成',
        self::ORDER_STATUS_CANCELLED =>'已取消',
    ];

    public static $order_sub_start_map = [
        self::ORDER_STATUS_SHIPPED => [
            self::ORDER_SUB_STATUS_SHIPPED => '未到货',
            self::ORDER_SUB_STATUS_SHIPPED_PART => '部分到货',
        ],
        self::ORDER_STATUS_RECEIVED => [
            self::ORDER_SUB_STATUS_RECEIVED => '全部入库',
            self::ORDER_SUB_STATUS_RECEIVED_PART => '部分入库',
        ],
    ];

    const ID_PREFIX = "PO";

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%purchase_order}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['create_way', 'source', 'supplier_id', 'warehouse', 'arrival_time', 'delivery_status', 'logistics_status', 'order_status', 'admin_id', 'add_time', 'update_time', 'ship_time', 'date', 'plan_time'], 'integer'],
            [['goods_price', 'freight_price', 'other_price', 'order_price'], 'number'],
            [['order_id','logistics_channels_id'], 'string', 'max' => 32],
            [['relation_no'], 'string', 'max' => 64],
            [['track_no'], 'string', 'max' => 60],
            [['remarks'], 'string', 'max' => 1000],
            [['order_id'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'create_way' => 'Create Way',
            'source' => 'Source',
            'order_id' => 'Order ID',
            'relation_no' => 'Relation No',
            'goods_price' => 'Goods Price',
            'freight_price' => 'Freight Price',
            'other_price' => 'Other Price',
            'order_price' => 'Order Price',
            'logistics_channels_id' => 'Logistics Channels ID',
            'track_no' => 'Track No',
            'arrival_time' => 'Arrival Time',
            'delivery_status' => 'Delivery Status',
            'order_status' => 'Order Status',
            'remarks' => 'Remarks',
            'admin_id' => 'Admin ID',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }

    //保存前处理
    public function beforeSave($insert)
    {
        if ($insert) {
            if (empty($this->order_id)) {
                $id_server = new PurchaseOrderIdService();
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

        $model = new self();
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