<?php
namespace common\events;

use yii\base\Event;

/**
 * 订单流程标准化 订单创建事件
 * Author: XuRui
 */
class OrderCreateEvent extends Event{

    public $order_id;
    public $order_data = [];
    public $goods = [];
    //新增
    /*public $op_user_role = '';// 下单角色
    public $op_user_id = '';// 下单人
    public $order_options;

    //日志数据
    public $op_data = [];//支付信息表数据*/

}