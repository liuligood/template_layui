<?php

namespace common\services\purchase;


use common\components\statics\Base;
use common\events\OrderCreateEvent;
use common\models\Goods;
use common\models\Order;
use common\models\OrderStockOccupy;
use common\models\purchase\PurchaseOrder;
use common\models\purchase\PurchaseOrderGoods;
use common\services\goods\GoodsService;
use common\services\goods\GoodsStockService;
use common\services\order\OrderService;
use yii\base\Component;
use yii\base\Exception;

class PurchaseOrderService extends Component
{

    public function init()
    {
        $this->on(Base::EVENT_PURCHASE_ORDER_CREATE_FINISH, ['common\services\event\PurchaseOrderEventService','afterOrderCreateFinish']);
        $this->on(Base::EVENT_PURCHASE_ORDER_CREATE_SUCCESS, ['common\services\event\PurchaseOrderEventService','afterOrderCreateSuccess']);
    }

    /**
     * 统一下单
     * @param array $order_info
     * @param array $goods
     * @param array $order_options
     * @return string
     * @throws Exception
     * @throws \yii\db\Exception
     */
    public function addOrder($order_info=[],$goods=[],$order_options = [])
    {
        $transaction = \Yii::$app->db->beginTransaction();
        $order_id = '';
        $dataService = new PurchaseValidateService();
        try {
            //验证下单数据
            $dataService->validateData($goods, $order_info, $order_options);
            //验证通过，初始化和解析下单数据
            $dataService->initData();

            //添加订单，返回订单号
            $order_id = $dataService->addOrder();
            //添加订单商品
            $dataService->addOrderGoods();

            //更新订单价格
            $order_data = self::updateOrderPrice($order_id);

            /**
             *【下单处理完成】关联事项处理
             * 注意：这里并未真正下单成功，如果关联事项处理出错将回滚。下单成功的额外处理、队列处理等要加到下方！
             */
            $this->trigger(Base::EVENT_PURCHASE_ORDER_CREATE_FINISH, new OrderCreateEvent([
                'order_id' => $order_id,
                'order_data' => array_merge($dataService->order_info, $order_data),
                //'op_data' => $dataService->op_data,
                //'op_user_role' => $dataService->op_user_role,
                //'op_user_id' => $dataService->op_user_id,
            ]));
            $transaction->commit();

            /**
             *【下单成功】后续事项处理
             */
            $this->trigger(Base::EVENT_PURCHASE_ORDER_CREATE_SUCCESS, new OrderCreateEvent([
                'order_id' => $order_id,
                'order_data' => array_merge($dataService->order_info, $order_data),
                'goods' => $goods,  //活动限购所需
            ]));
            //下单成功，返回订单号
            return $order_id;
        } catch (Exception $e) {
            $transaction->rollBack();
            throw new Exception($e->getMessage(),$e->getCode());
        }
    }

    /**
     * 取消订单
     * @param $order_id
     * @return bool
     * @throws \Exception
     * @throws \Throwable
     */
    public function cancel($order_id)
    {
        $order = self::getOneByOrderId($order_id);
        if(empty($order) || $order['order_status'] == PurchaseOrder::ORDER_STATUS_CANCELLED){
            return false;
        }

        $order['order_status'] = PurchaseOrder::ORDER_STATUS_CANCELLED;
        if($order->save()){
            $sku_no = OrderStockOccupy::find()->where(['purchase_order_id'=>$order['order_id']])->select('sku_no')->column();
            if(!empty($sku_no)) {
                OrderStockOccupy::deleteAll(['purchase_order_id'=>$order['order_id']]);
                (new PurchaseProposalService())->updatePurchaseProposal($order['warehouse'],$sku_no, $order['admin_id']);
            }
            return true;
        }
        return false;
    }


    /**
     * 结束剩余采购
     * @param $order_id
     * @return bool
     * @throws \Exception
     * @throws \Throwable
     */
    public function finish($order_id)
    {
        $order = self::getOneByOrderId($order_id);
        if(empty($order) || $order['order_status'] != PurchaseOrder::ORDER_STATUS_SHIPPED || $order['order_sub_status'] != PurchaseOrder::ORDER_SUB_STATUS_SHIPPED_PART){
            return false;
        }

        $order['order_status'] = PurchaseOrder::ORDER_STATUS_RECEIVED;
        $order['order_sub_status'] = PurchaseOrder::ORDER_SUB_STATUS_RECEIVED_PART;
        if($order->save()){
            OrderStockOccupy::deleteAll(['purchase_order_id'=>$order['order_id']]);
            $sku_no = PurchaseOrderGoods::find()->where(['order_id'=>$order['order_id']])->select('sku_no')->column();
            if(!empty($sku_no)) {
                (new PurchaseProposalService())->updatePurchaseProposal($order['warehouse'],$sku_no);
            }
            return true;
        }
        return false;
    }

    /**
     * 商品到货
     * @param $purchase_order_id
     * @param $arrival_goods
     * @param null $arrival_time
     * @return bool
     * @throws Exception
     * @throws \Throwable
     * @throws \yii\db\Exception
     */
    public function receivedGoods($purchase_order_id,$arrival_goods,$arrival_time = null)
    {
        $purchase_order = self::getOneByOrderId($purchase_order_id);
        if ($purchase_order['order_status'] != PurchaseOrder::ORDER_STATUS_SHIPPED) {
            return false;
        }

        $transaction = \Yii::$app->db->beginTransaction();
        try {
            $purchase_order_goods = PurchaseOrderGoods::find()->where(['order_id' => $purchase_order_id])->all();
            $all_arrival = true;
            foreach ($purchase_order_goods as $goods_v) {
                $arrival_num = empty($arrival_goods[$goods_v['id']]) ? 0 : (int)$arrival_goods[$goods_v['id']];
                $goods_num = $goods_v['goods_num'];
                $goods_finish_num = $goods_v['goods_finish_num'];
                if($goods_num == $goods_finish_num){
                    continue;
                }

                if ($arrival_num <= 0) {
                    $all_arrival = false;
                    continue;
                }

                if (empty($goods_v['cgoods_no'])) {
                    throw new \Exception('商品数据异常');
                }

                if ($goods_num - $goods_finish_num < $arrival_num) {
                    throw new \Exception('到货数量不正确');
                }

                //部分到货
                if ($goods_num - $goods_finish_num > $arrival_num) {
                    $all_arrival = false;
                }
                $goods_v['goods_finish_num'] = $goods_finish_num + $arrival_num;
                $goods_v->save();

                //先入库
                GoodsStockService::changeStock($goods_v['cgoods_no'], $purchase_order['warehouse'], GoodsStockService::TYPE_WAREHOUSING, $arrival_num, $purchase_order_id, '采购订单到货');
                //清空库存占用
                OrderStockOccupy::deleteAll(['purchase_order_id' => $purchase_order_id]);
                //在途改为库存占用
                //OrderStockOccupy::updateAll(['type'=>OrderStockOccupy::TYPE_STOCK,'purchase_order_id'=> ''],['purchase_order_id' => $purchase_order_id, 'sku_no' => $goods_v['sku_no']]);
            }
            if ($all_arrival) {//全部到货
                $purchase_order['order_status'] = PurchaseOrder::ORDER_STATUS_RECEIVED;
                $purchase_order['order_sub_status'] = PurchaseOrder::ORDER_SUB_STATUS_RECEIVED;
            } else {//部分到货
                $purchase_order['order_status'] = PurchaseOrder::ORDER_STATUS_SHIPPED;
                $purchase_order['order_sub_status'] = PurchaseOrder::ORDER_SUB_STATUS_SHIPPED_PART;
            }
            if (empty($purchase_order['arrival_time'])) {
                $purchase_order['arrival_time'] = empty($arrival_time) ? time() : $arrival_time;
            }
            if ($purchase_order->save()) {
                (new PurchaseProposalService())->updatePurchaseProposalToPOrderId($purchase_order_id);
                $transaction->commit();
                return true;
            } else {
                $transaction->rollBack();
                return false;
            }
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw new Exception($e->getMessage(), $e->getCode());
        }

        return false;
    }

    /**
     * 到货
     * @param $purchase_order_id
     * @return bool
     * @throws \Exception
     * @throws \Throwable
     */
    public function received($purchase_order_id,$arrival_time = null)
    {
        $purchase_order = self::getOneByOrderId($purchase_order_id);
        if($purchase_order['order_status'] != PurchaseOrder::ORDER_STATUS_SHIPPED){
            return false;
        }

        $transaction = \Yii::$app->db->beginTransaction();
        try {
            $purchase_order['order_status'] = PurchaseOrder::ORDER_STATUS_RECEIVED;
            $purchase_order['arrival_time'] = empty($arrival_time)?time():$arrival_time;
            if ($purchase_order->save()) {
                $purchase_order_goods = PurchaseOrderGoods::find()->where(['order_id' => $purchase_order_id])->all();
                foreach ($purchase_order_goods as $goods_v) {
                    $goods_num = $goods_v['goods_num'];
                    $goods_v['goods_finish_num'] = $goods_num;
                    $goods_v->save();

                    //先入库
                    GoodsStockService::changeStock($goods_v['goods_no'], $purchase_order['warehouse'], GoodsStockService::TYPE_WAREHOUSING, $goods_num,$purchase_order_id,'采购订单已完成');
                    //在途改为库存占用
                    OrderStockOccupy::updateAll(['type'=>OrderStockOccupy::TYPE_STOCK,'purchase_order_id'=> ''],['purchase_order_id' => $purchase_order_id, 'sku_no' => $goods_v['sku_no']]);
                    /*$order_stock_occupy = OrderStockOccupy::find()->where(['purchase_order_id' => $purchase_order_id, 'sku_no' => $goods_v['sku_no']])->all();
                    foreach ($order_stock_occupy as $occupy) {
                        $order = OrderService::getOneByOrderId($occupy['order_id']);
                        //已发货 改为 完成$occupy
                        if ($order['order_status'] == Order::ORDER_STATUS_SHIPPED) {
                            Order::updateOneByCond(['order_id' => $occupy['order_id']], ['order_status' => Order::ORDER_STATUS_FINISH]);
                            //$goods_num -= $occupy['num'];
                            //订单已发货需要出库
                            //GoodsStockService::changeStock($goods_v['goods_no'], $purchase_order['warehouse'], GoodsStockService::TYPE_OUT_OF_STOCK, -$occupy['num'],$occupy['order_id'],'采购订单已完成');
                        }
                        $occupy->delete();
                    }*/
                }
                (new PurchaseProposalService())->updatePurchaseProposalToPOrderId($purchase_order_id);
                $transaction->commit();
                return true;
            }
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw new Exception($e->getMessage(), $e->getCode());
        }
        return false;
    }

    public static function getLogisticsChannels(){
        return [
            'YUNDA'=>'韵达',
            'STO'=>'申通',
            'TTKD'=>'天天',
            'DBL'=>'德邦',
            'ZTO'=>'中通',
            'YTO'=>'圆通',
            'DBKD'=>'德邦快递',
            'ZTOKY'=>'中通快运',
            'SFEXPRESS'=>'顺丰',
            'EMS'=>'EMS',
            'HTKY'=>'百世快递',
            'BTWL'=>'百世快运',
            'FAST'=>'快捷速递',
            'QFKD'=>'全峰快递',
            'UC'=>'优速',
            'GTO'=>'国通快递',
            'YZBK' => '邮政国内标快',
            'XFWL' => '信丰物流',
            'SURE' => '速尔物流',
            'OTHER'=>'其他',
        ];
    }

    /**
     * 统一更新订单价格，返回最新价格和小单费变动日志
     * @param $order_id string 订单号
     * @return array
     * @throws Exception
     */
    public static function updateOrderPrice($order_id)
    {
        $order = self::getOneByOrderId($order_id);

        $order_goods = self::getOrderGoods($order_id);

        $order_price = 0;
        foreach ($order_goods as $v) {
            $order_price += $v['goods_price'] * $v['goods_num'];
        }

        $order_data = [
            'goods_price' => $order_price,
            'order_price' => $order_price + $order['freight_price'] + $order['other_price']
        ];

        PurchaseOrder::updateOneByOrderId($order_id, $order_data);
        return $order_data;
    }

    /**
     * 获取订单信息
     * @param $order_id
     * @return null|static
     */
    public static function getOneByOrderId($order_id)
    {
        return PurchaseOrder::findOne(['order_id' => $order_id]);
    }

    /**
     * 获取订单商品
     * @param $order_id
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getOrderGoods($order_id)
    {
        return PurchaseOrderGoods::find()->where(['order_id' => $order_id, 'goods_status' => [PurchaseOrderGoods::GOODS_STATUS_UNCONFIRMED, PurchaseOrderGoods::GOODS_STATUS_NORMAL]])->asArray()->all();
    }

    /**
     * 从采购订单获取商品采购金额
     * @param $purchase_order_id
     * @param $cgoods_no
     * @return int|mixed
     */
    public static function getGoodsPurchaseOrderPrice($purchase_order_id,$cgoods_no)
    {
        //价格为售价加运费（平摊商品数量）
        $purchase_order = PurchaseOrder::find()->where(['order_id' => $purchase_order_id])->one();
        if (empty($purchase_order)) {
            return 0;
        }
        $purchase_order_goods = PurchaseOrderGoods::find()->where(['order_id' => $purchase_order_id])->asArray()->all();
        $goods_num = 0;
        $goods_price = 0;
        foreach ($purchase_order_goods as $good) {
            $goods_num += $good['goods_num'];
            if ($good['cgoods_no'] == $cgoods_no) {
                $goods_price = $good['goods_price'];
            }
        }
        return $goods_price + ($purchase_order['freight_price'] + $purchase_order['other_price']) / $goods_num;
    }

}