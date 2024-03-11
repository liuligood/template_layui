<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%order_event}}".
 *
 * @property int $id
 * @property int $platform 平台
 * @property int $shop_id 店铺id
 * @property string $order_id 订单号
 * @property string $event_type 事件类型
 * @property int $status 状态
 * @property string $error_msg 错误信息
 * @property int $plan_time 计划时间
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class OrderEvent extends BaseAR
{

    const STATUS_WAIT_RUN = 0;//待执行
    const STATUS_RUNNING = 10;//执行中
    const STATUS_SUCCESS = 20;//执行成功
    const STATUS_FAILURE = 30;//执行失败

    const EVENT_TYPE_TRACKING_NUMBER = 'tracking_number';//跟踪号
    const EVENT_TYPE_SHIPPING = 'shipping';//发货
    const EVENT_TYPE_POST_INVOICE = 'post_invoice';//上传发票
    const EVENT_TYPE_CANCEL = 'cancel';//取消
    const EVENT_TYPE_ON_WAY = 'on_the_way';//快递在路上
    const EVENT_TYPE_DELIVERED = 'delivered';//到货
    const EVENT_TYPE_FIRST_LOGISTICS = 'first_logistics';//首程物流

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%order_event}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['platform', 'shop_id',  'status', 'add_time', 'update_time','plan_time'], 'integer'],
            [['order_id','event_type'], 'string', 'max' => 32],
            [['error_msg'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'platform' => 'Platform',
            'shop_id' => 'Shop ID',
            'order_id' => 'Order ID',
            'event_type' => 'Event Type',
            'status' => 'Status',
            'error_msg' => 'Error Msg',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}
