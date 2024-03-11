<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "ys_order_overseas_stock".
 *
 * @property int $id
 * @property string $order_id 订单号
 * @property int $return_data 退件日期
 * @property int $number 数量
 * @property string $ware_house 仓库
 * @property string $goods_shelves 货架
 * @property int $status 状态
 * @property int $expire_time 预计过期时间
 * @property string $rewire_id 重发订单号
 * @property int $rewire_data 重发日期
 * @property string $desc 备注
 * @property string $cgoods_no 子商品编号
 * @property int $add_time 创建日期
 * @property int $update_time 更新日期
 * @property int $user_id 添加管理员
 */
class OrderOverseasStock extends BaseAR
{
    const ORDER_UODATE_NEVER = 3;
    const ORDER_UODATE_RESTAR = 1;
    const ORDER_UODATE_RESTORM = 2;

    public static $stutas_map=[
        self::ORDER_UODATE_NEVER => '未处理',
        self::ORDER_UODATE_RESTAR => '已重发',
        self::ORDER_UODATE_RESTORM => '已销毁'
    ];
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ys_order_overseas_stock';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['return_data', 'number', 'status', 'expire_time', 'rewire_data', 'add_time', 'update_time', 'user_id'], 'integer'],
            [['order_id', 'ware_house', 'goods_shelves', 'rewire_id', 'cgoods_no'], 'string', 'max' => 32],
            [['desc'], 'string', 'max' => 1000],
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
            'return_data' => 'Return Data',
            'number' => 'Number',
            'ware_house' => 'Ware House',
            'goods_shelves' => 'Goods Shelves',
            'status' => 'Status',
            'expire_time' => 'Expire Time',
            'rewire_id' => 'Rewire ID',
            'rewire_data' => 'Rewire Data',
            'desc' => 'Desc',
            'cgoods_no' => 'Cgoods No',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
            'user_id' => 'User ID',
        ];
    }
}
