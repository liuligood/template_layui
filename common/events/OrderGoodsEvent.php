<?php
namespace common\events;

use yii\base\Event;

/**
 * 订单商品 事件
 * @author XuRui
 */
class OrderGoodsEvent extends Event{

    public $order_id;//订单ID
    public $order_goods_id;//订单商品ID
    public $order_goods_data = [];//订单商品数组
    public $old_order_goods_data = [];//旧订单商品数据
}