<?php

namespace common\models\order;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%order_abnormal_follow}}".
 *
 * @property int $id
 * @property int $abnormal_id 异常订单号
 * @property string $order_id 订单号
 * @property int $abnormal_status 异常状态
 * @property string $follow_remarks 异常备注
 * @property int $admin_id 管理员id
 * @property int $next_follow_time
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class OrderAbnormalFollow extends BaseAR
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%order_abnormal_follow}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['abnormal_id', 'abnormal_status', 'admin_id', 'add_time', 'update_time' , 'next_follow_time'], 'integer'],
            [['order_id'], 'string', 'max' => 32],
            [['follow_remarks'], 'string', 'max' => 1000],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'abnormal_id' => 'Abnormal ID',
            'order_id' => 'Order ID',
            'abnormal_status' => 'Abnormal Status',
            'follow_remarks' => 'Follow Remarks',
            'admin_id' => 'Admin ID',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}