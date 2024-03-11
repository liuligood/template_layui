<?php
namespace common\services\event;

use common\components\statics\Base;
use common\models\sys\SystemOperlog;
use common\services\order\OrderService;
use common\services\purchase\PurchaseProposalService;
use common\services\sys\SystemOperlogService;
use yii\base\Exception;
use Yii;

class OrderEventService
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

        $op_user_info = [];
        if(empty($order['admin_id'])){
            $op_user_info['op_user_role'] = Base::ROLE_SYSTEM;
        }
        (new SystemOperlogService())->setType(SystemOperlog::TYPE_ADD)
            ->setOpUserInfo($op_user_info)
            ->addOrderLog($event->order_id,[],SystemOperlogService::ACTION_ORDER_CREATE,'');
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

        //推荐物流方式
        (new OrderService())->recommendedLogistics($event->order_id);
        //更新采购建议
        (new PurchaseProposalService())->updatePurchaseProposalToOrderId($event->order_id);
    }
} 