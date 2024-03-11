<?php
namespace common\services\event;

use common\services\purchase\PurchaseProposalService;
use yii\base\Exception;
use Yii;

class PurchaseOrderEventService
{
    /**
     * 【下单处理完成】关联事项处理
     * 注意：这里并未真正下单成功，如果关联事项处理出错将回滚。下单成功的额外处理、队列处理等要加到下方！
     */
    public static function afterOrderCreateFinish($event)
    {
        if (empty($event->order_id) || empty($event->order_data)) {
            throw new Exception("订单创建事件处理异常，订单数据为空");
        }
        Yii::$app->db->enableSlaves = false;
        $order = $event->order_data;

        //操作人角色、用户ID初始化
        //$op_user_role = empty($event->op_user_role) ? Base::ROLE_USER : $event->op_user_role;
        //$op_user_id = empty($event->op_user_id) ? strval($order['user_id']) : strval($event->op_user_id);
    }

    /**
     * 【下单成功】后续事项处理
     */
    public static function afterOrderCreateSuccess($event)
    {
        if (empty($event->order_id) || empty($event->order_data)) {
            throw new Exception("订单创建事件处理异常，订单数据为空");
        }
        Yii::$app->db->enableSlaves = false;
        $order = $event->order_data;

        (new PurchaseProposalService())->updatePurchaseProposalToPOrderId($event->order_id);
    }
} 