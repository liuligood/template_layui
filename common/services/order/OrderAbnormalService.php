<?php

namespace common\services\order;

use common\models\Order;
use common\models\order\OrderAbnormal;
use common\models\order\OrderAbnormalFollow;
use common\models\OrderStockOccupy;
use common\models\Shop;
use common\services\goods\GoodsService;
use common\services\purchase\PurchaseProposalService;

class OrderAbnormalService
{

    /**
     * 异常类型
     * @var array
     */
    public static $abnormal_type_maps = [
        1 => '核实订单信息',
        2 => '核实材积重',
        3 => '找不到产品',
        4 => '产品缺货',
        5 => '亏本',
        6 => '物流问题',
        7 => '待重派',
        8 => '违禁品',
        9 => '物流商退件',
        10 => '侵权产品',
        11 => '其他平台采购',
        12 => '待取件',
        13 => '关税问题',
        14 => '待上传护照',
        15 => '更换运单号',
        //16 => '敏感货',
        17 => '售后问题',
        18 => '取件转款或重新下单',
        19 => '可改派',
        50 => '刷单',
        99 => '其他',
    ];

    /**
     * 获取跟进时间
     * @param string $type
     * @return int
     */
    public static function getNextFollowTime($type = '1H')
    {
        $time_map = [
            '1H' => 60*60,
            '3H' => 60*60,
            '1D' => 24*60*60,
            '3D' => 3*24*60*60,
            '7D' => 7*24*60*60,
        ];
        $time = time();
        $time += $time_map[$type];
        return $time;
    }

    /**
     * 批量移入异常
     * @param $order_ids
     * @param $abnormal_type
     * @param $abnormal_remarks
     * @param $next_follow_time
     * @throws \Exception
     * @throws \Throwable
     */
    public function batchMoveAbnormal($order_ids,$abnormal_type,$abnormal_remarks,$next_follow_time)
    {
        $order_id_lists = [];
        foreach ($order_ids as $order_id) {
            $exist = OrderAbnormal::find()->where(['order_id'=>$order_id])->andWhere(['!=','abnormal_status',OrderAbnormal::ORDER_ABNORMAL_STATUS_CLOSE])->asArray()->one();
            if($exist) {
                if ($exist['abnormal_type'] == $abnormal_type) {
                    continue;
                }
                $this->closeAbnormalToOrderId($order_id, '移入新异常:' . self::$abnormal_type_maps[$abnormal_type]);
                //continue;
            }

            $order_id_lists[] = $order_id;
            $order = Order::find()->where(['order_id'=>$order_id])->asArray()->one();
            $data = [
                'order_id' => $order_id,
                'abnormal_type' => $abnormal_type,
                'abnormal_status' => OrderAbnormal::ORDER_ABNORMAL_STATUS_UNFOLLOW,
                'abnormal_remarks' => $abnormal_remarks,
                'next_follow_time' => !empty($next_follow_time)?$next_follow_time:self::getNextFollowTime('1H'),
                'admin_id' => !empty(\Yii::$app->user) ? \Yii::$app->user->getId() : 0,
                'follow_admin_id' => Shop::find()->where(['id'=>$order['shop_id']])->select('admin_id')->scalar()
            ];
            OrderAbnormal::add($data);
        }

        Order::updateAll(['abnormal_time' => time()], ['order_id' => $order_id_lists]);
        //报价信息更新
        GoodsService::updateDeclare($order_id_lists);
        OrderStockOccupy::deleteAll(['order_id' => $order_id_lists]);
        (new PurchaseProposalService())->updatePurchaseProposalToOrderId($order_id_lists);
    }

    /**
     * 跟进异常
     * @param $abnormal_id
     * @param $abnormal_status
     * @param $remarks
     * @param $follow_time
     * @param $follow_admin_id
     * @throws \Exception
     * @throws \Throwable
     */
    public function followAbnormal($abnormal_id,$abnormal_status,$remarks,$follow_time,$follow_admin_id = null)
    {
        $abnormal = OrderAbnormal::find()->where(['id' => $abnormal_id])->one();
        $order_id = $abnormal['order_id'];
        $next_follow_time = self::getNextFollowTime($follow_time);
        $data = [
            'abnormal_id' => $abnormal_id,
            'order_id' => $order_id,
            'abnormal_status' => $abnormal_status,
            'follow_remarks' => $remarks,
            'next_follow_time' => $next_follow_time,
            'admin_id' => !empty(\Yii::$app->user) ? \Yii::$app->user->getId() : 0
        ];
        OrderAbnormalFollow::add($data);

        $abnormal->abnormal_status = $abnormal_status;
        $abnormal->last_follow_time = time();
        $abnormal->last_follow_abnormal_remarks = $remarks;
        $abnormal->next_follow_time = $next_follow_time;
        if(!empty($follow_admin_id)){
            $abnormal->follow_admin_id = $follow_admin_id;
        }
        $abnormal->save();

        if($abnormal_status == OrderAbnormal::ORDER_ABNORMAL_STATUS_CLOSE) {
            $order = Order::find()->where(['order_id' => $order_id])->all();
            foreach ($order as $order_v) {
                if ($order_v['abnormal_time'] == 0) {
                    continue;
                }
                $order_v->abnormal_time = 0;
                $order_v->save();
                (new PurchaseProposalService())->updatePurchaseProposalToOrderId($order_id);
            }
        }
    }

    /**
     * 关闭异常
     * @param $order_id
     * @param $remarks
     * @throws \Exception
     * @throws \Throwable
     */
    public function closeAbnormalToOrderId($order_id,$remarks)
    {
        $abnormal_status = OrderAbnormal::ORDER_ABNORMAL_STATUS_CLOSE;
        $abnormal = OrderAbnormal::find()->where(['order_id' => $order_id])->andWhere(['!=','abnormal_status',OrderAbnormal::ORDER_ABNORMAL_STATUS_CLOSE])->one();
        if(empty($abnormal)){
            return;
        }
        $order_id = $abnormal['order_id'];
        $next_follow_time = self::getNextFollowTime();
        $data = [
            'abnormal_id' => $abnormal['id'],
            'order_id' => $order_id,
            'abnormal_status' => $abnormal_status,
            'follow_remarks' => $remarks,
            'next_follow_time' => $next_follow_time,
            'admin_id' => !empty(\Yii::$app->user) ? \Yii::$app->user->getId() : 0
        ];
        OrderAbnormalFollow::add($data);

        $abnormal->abnormal_status = $abnormal_status;
        $abnormal->last_follow_time = time();
        $abnormal->last_follow_abnormal_remarks = $remarks;
        $abnormal->next_follow_time = $next_follow_time;
        $abnormal->save();

        $order = Order::find()->where(['order_id' => $order_id])->all();
        foreach ($order as $order_v) {
            if ($order_v['abnormal_time'] == 0) {
                continue;
            }
            $order_v->abnormal_time = 0;
            $order_v->save();
            (new PurchaseProposalService())->updatePurchaseProposalToOrderId($order_id);
        }
    }

}