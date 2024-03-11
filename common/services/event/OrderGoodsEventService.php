<?php
namespace common\services\event;

use common\services\buy_goods\BuyGoodsService;
use Yii;
use yii\base\Exception;

/**
 *  商品事件服务类
 */
class OrderGoodsEventService
{
    /**
     * 【添加订单商品】事件处理
     */
    public static function afterGoodsAdd($event)
    {
        if (empty($event->order_id)) {
            throw new Exception("事件处理异常，订单ID为空");
        }

        $order_goods = $event->order_goods_data;
        $order_goods['order_id'] = $event->order_id;
        $order_goods['order_goods_id'] = $event->order_goods_id;
        (new BuyGoodsService())->addGoods($order_goods);
    }

    /**
     * 【修改订单商品】事件处理
     */
    public static function afterGoodsUpdate($event)
    {
        if (empty($event->order_id)) {
            throw new Exception("事件处理异常，订单ID为空");
        }

        $order_goods = $event->order_goods_data;
        $order_goods['order_id'] = $event->order_id;
        $order_goods['order_goods_id'] = $event->order_goods_id;
        (new BuyGoodsService())->updateGoods($order_goods);
    }

    /**
     * 【删除订单商品】事件处理
     */
    public static function afterGoodsDelete($event)
    {
        if (empty($event->order_id)) {
            throw new Exception("事件处理异常，订单ID为空");
        }

        (new BuyGoodsService())->deleteGoods($event->order_goods_id);

    }
} 