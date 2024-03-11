<?php

namespace common\models\order;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%order_abnormal}}".
 *
 * @property int $id
 * @property string $order_id 订单号
 * @property int $abnormal_type 异常类型
 * @property string $abnormal_remarks 异常备注
 * @property int $abnormal_status 异常状态
 * @property int $last_follow_time 最后一次跟进时间
 * @property string $last_follow_abnormal_remarks 最后一次跟进记录
 * @property int $next_follow_time 下次跟进时间
 * @property int $admin_id 管理员id
 * @property int $follow_admin_id 跟进人id
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class OrderAbnormal extends BaseAR
{

    //未跟进 待处理 待联系客户 待客户回复 待买货 待取消订单 已关闭
    const ORDER_ABNORMAL_STATUS_UNFOLLOW = 0;//未跟进
    const ORDER_ABNORMAL_STATUS_PENDING = 10;//待处理
    const ORDER_ABNORMAL_STATUS_CONTACTED = 20;//待联系客户
    const ORDER_ABNORMAL_STATUS_REPLIED = 30;//待客户回复
    const ORDER_ABNORMAL_STATUS_CHOOSE_LOGISTICS = 40;//待选物流方式
    const ORDER_ABNORMAL_STATUS_WAIT_PURCHASE = 50;//待采购
    const ORDER_ABNORMAL_STATUS_PROCURED = 51;//已采购
    const ORDER_ABNORMAL_STATUS_CANCEL = 90;//待取消订单
    const ORDER_ABNORMAL_STATUS_CLOSE = 100;//已关闭

    public static $order_abnormal_status_map = [
        self::ORDER_ABNORMAL_STATUS_UNFOLLOW => '未跟进',
        self::ORDER_ABNORMAL_STATUS_PENDING => '待处理',
        self::ORDER_ABNORMAL_STATUS_CONTACTED => '待联系客户',
        self::ORDER_ABNORMAL_STATUS_REPLIED => '待客户回复',
        self::ORDER_ABNORMAL_STATUS_WAIT_PURCHASE => '待采购',
        self::ORDER_ABNORMAL_STATUS_PROCURED => '已采购',
        self::ORDER_ABNORMAL_STATUS_CHOOSE_LOGISTICS => '待选物流方式',
        self::ORDER_ABNORMAL_STATUS_CANCEL => '待取消订单',
        self::ORDER_ABNORMAL_STATUS_CLOSE => '已关闭',
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%order_abnormal}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['abnormal_type', 'abnormal_status', 'last_follow_time', 'next_follow_time', 'admin_id', 'follow_admin_id', 'add_time', 'update_time'], 'integer'],
            [['order_id'], 'string', 'max' => 32],
            [['abnormal_remarks', 'last_follow_abnormal_remarks'], 'string', 'max' => 1000],
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
            'abnormal_type' => 'Abnormal Type',
            'abnormal_remarks' => 'Abnormal Remarks',
            'abnormal_status' => 'Abnormal Status',
            'last_follow_time' => 'Last Follow Time',
            'last_follow_abnormal_remarks' => 'Last Follow Abnormal Remarks',
            'admin_id' => 'Admin ID',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}