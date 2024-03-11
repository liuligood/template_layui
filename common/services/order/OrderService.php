<?php
namespace common\services\order;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\events\OrderCreateEvent;
use common\models\BuyGoods;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\goods\GoodsStock;
use common\models\goods_shop\GoodsShopOverseasWarehouse;
use common\models\GoodsEvent;
use common\models\GoodsShop;
use common\models\GoodsSource;
use common\models\Order;
use common\models\OrderDeclare;
use common\models\OrderEvent;
use common\models\OrderGoods;
use common\models\OrderRecommended;
use common\models\OrderStockOccupy;
use common\models\purchase\PurchaseOrder;
use common\models\purchase\PurchaseOrderGoods;
use common\models\Shop;
use common\models\sys\ShippingMethod;
use common\models\sys\ShippingMethodOffer;
use common\models\warehousing\WarehouseProvider;
use common\services\api\GoodsEventService;
use common\services\api\OrderEventService;
use common\services\buyer_account\BuyerAccountTransactionService;
use common\services\FApiService;
use common\services\FTransportService;
use common\services\goods\FGoodsService;
use common\services\goods\GoodsService;
use common\services\goods\GoodsStockService;
use common\services\purchase\PurchaseOrderService;
use common\services\purchase\PurchaseProposalService;
use common\services\sys\CountryService;
use common\services\sys\ExchangeRateService;
use common\services\sys\SystemOperlogService;
use common\services\transport\BaseTransportService;
use common\services\warehousing\WarehouseService;
use yii\base\Component;
use yii\base\Exception;
use yii\helpers\ArrayHelper;

class OrderService extends Component
{

    public function init()
    {
        $this->on(Base::EVENT_ORDER_CREATE_FINISH, ['common\services\event\OrderEventService','afterOrderCreateFinish']);
        $this->on(Base::EVENT_ORDER_CREATE_SUCCESS, ['common\services\event\OrderEventService','afterOrderCreateSuccess']);
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
        $dataService = new ValidateService();
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
            $this->trigger(Base::EVENT_ORDER_CREATE_FINISH, new OrderCreateEvent([
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
            $this->trigger(Base::EVENT_ORDER_CREATE_SUCCESS, new OrderCreateEvent([
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
     * 获取订单商品
     * @param $order_id
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getOrderGoods($order_id)
    {
        return OrderGoods::find()->where(['order_id' => $order_id, 'goods_status' => [OrderGoods::GOODS_STATUS_UNCONFIRMED, OrderGoods::GOODS_STATUS_NORMAL]])->asArray()->all();
    }

    /**
     * 获取订单报关信息
     * @param $order_id
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getOrderDeclare($order_id)
    {
        return OrderDeclare::find()->where(['order_id' => $order_id])->asArray()->all();
    }

    /**
     * 获取订单信息
     * @param $order_id
     * @return null|static
     */
    public static function getOneByOrderId($order_id)
    {
        return Order::findOne(['order_id' => $order_id]);
    }

    /**
     * 统一更新订单价格，返回最新价格和小单费变动日志
     * @param $order_id string 订单号
     * @return array
     */
    public static function updateOrderPrice($order_id)
    {
        $order = self::getOneByOrderId($order_id);

        $order_goods = OrderService::getOrderGoods($order_id);

        $order_income_price = 0;
        $order_cost_price = 0;
        $order_profit = 0;
        $order_status = Order::ORDER_STATUS_WAIT_PURCHASE;
        $goods = [];
        foreach ($order_goods as $v) {
            //自建无成本的时候 取采购价格
            $goods_cost_price = $v['goods_cost_price'];
            if($v['goods_cost_price'] <= 0 && $order['source_method'] == GoodsService::SOURCE_METHOD_OWN) {
                $goods_cost_price = self::getGoodsPurchasePrice($v['platform_asin']);
                OrderGoods::updateAll(['goods_cost_price'=>$goods_cost_price],['id'=>$v['id']]);
            }

            $order_income_price += $v['goods_income_price'] * $v['goods_num'];
            $order_cost_price += $goods_cost_price * $v['goods_num'];

            if (empty($v['platform_type']) || empty($v['platform_asin']) || $v['goods_status'] == OrderGoods::GOODS_STATUS_UNCONFIRMED) {
                $order_status = Order::ORDER_STATUS_UNCONFIRMED;
            }

            $goods = $v;
        }

        //更新自建订单状态
        if($order['source_method'] == GoodsService::SOURCE_METHOD_OWN) {
            $order_status = (new PurchaseProposalService())->verifyOrderInStock($order['order_id']) ? Order::ORDER_STATUS_WAIT_PURCHASE : Order::ORDER_STATUS_UNCONFIRMED;
        }

        if( $order['exchange_rate'] <= 0) {
            $order['exchange_rate'] = ExchangeRateService::getValue($order['currency']);
        }

        $platform_fee = $order['platform_fee'];
        if($platform_fee <= 0) {
            $platform_fee = FGoodsService::factory($order['source'])->setCountryCode($order['country'])->platformFee($order_income_price,$order['order_id']);
        }

        if($order['source'] == Base::PLATFORM_FRUUGO && $order['source_method'] == GoodsService::SOURCE_METHOD_AMAZON) {
            $platform_fee = FGoodsService::factory($order['source'])->setCountryCode($order['country'])->platformFee($order_income_price,$order['order_id']);
            $platform_fee += (1 - 0.86) * $order_cost_price;
        }

        if(count($order_goods) == 1 && $order['freight_price'] <= 0) {
            $order['freight_price'] = self::getFreightPrice($order, $goods);
        }

        $order['order_income_price'] = $order_income_price;
        $order['order_cost_price'] = $order_cost_price;
        $order['platform_fee'] = $platform_fee;
        $order['order_profit'] = self::calculateProfitPrice($order);

        if(in_array($order['order_status'],[Order::ORDER_STATUS_UNCONFIRMED,Order::ORDER_STATUS_WAIT_PURCHASE])) {
            $order['order_status'] = $order_status;
        }

        $order->save();
        return [
            'order_income_price' => $order_income_price,
            'order_cost_price' => $order_cost_price,
            'platform_fee' => $platform_fee,
            'order_profit' => $order_profit
        ];
    }

    /**
     * 获取商品运费
     * @param $order
     * @return int|mixed
     */
    public static function getFreightPrice($order,$goods)
    {
        $order = Order::find()
            ->alias('o')->leftJoin(OrderGoods::tableName() . ' as og', 'og.order_id=o.order_id')
            ->where(['settlement_status' => [1,3], 'country' => $order['country'], 'og.platform_asin' => $goods['platform_asin'], 'og.goods_num' => $goods['goods_num']])
            ->asArray()->one();
        $freight_price = 0;
        if (!empty($order)) {
            $og_count = OrderGoods::find()->where(['order_id' => $order['order_id']])->count();
            if ($og_count == 1) {
                $freight_price = $order['freight_price'];
                return $freight_price;
            }
        }
        return 0;
    }

    /**
     * 获取商品采购金额
     * @param $sku_no
     * @return int|mixed
     */
    public static function getGoodsPurchasePrice($sku_no)
    {
        $purchase_order_goods = PurchaseOrderGoods::find()
            ->where(['sku_no' => $sku_no, 'goods_status' => [PurchaseOrderGoods::GOODS_STATUS_UNCONFIRMED, PurchaseOrderGoods::GOODS_STATUS_NORMAL]])
            ->orderBy('id desc')->one();
        $goods_price = 0;
        if (!empty($purchase_order_goods)) {
            $goods_price = PurchaseOrderService::getGoodsPurchaseOrderPrice($purchase_order_goods['order_id'],$purchase_order_goods['cgoods_no']);
        } else {
            $goods = Goods::find()->where(['sku_no'=>$sku_no])->one();
            if (empty($goods)){
                return 0;
            }
            $goods_source = GoodsSource::find()->where(['goods_no'=>$goods['goods_no'],'platform_type'=>Base::PLATFORM_1688,'is_main'=>2])->asArray()->one();
            if(!empty($goods_source)){
                $goods_price = $goods_source['price'];
            }else {
                $goods_source_reference = GoodsSource::find()->where(['goods_no' => $goods['goods_no'], 'platform_type' => Base::PLATFORM_1688, 'is_main' => [0, 1]])->asArray()->one();
                if(!empty($goods_source_reference)) {
                    $goods_price = $goods_source_reference['price'];
                }
            }
            if(empty($goods_price) && $goods['status'] == Goods::GOODS_STATUS_VALID){
                $goods_price = $goods['price'];
            }
        }
        return $goods_price;
    }

    /**
     * 计算利润
     * @param $order
     * @return float|int
     */
    public static function calculateProfitPrice($order)
    {
        if ($order['source_method'] == GoodsService::SOURCE_METHOD_AMAZON) {//亚马逊 利润 = 销售额-成本-平台费用 * 汇率差
            $order_profit = $order['order_income_price'] - $order['order_cost_price'] - $order['platform_fee'];
            $currency = $order['currency'];
            if(empty($currency)) {
                $currency = Shop::find()->where(['id' => $order['shop_id']])->select('currency')->scalar();
            }
            $order_profit = CountryService::getConvertRMB($order_profit, $currency);
        } else {//亚马逊 利润 = (销售额-平台费用) * 汇率差 - 成本 - 运费
            if(!empty($order['exchange_rate']) && $order['exchange_rate'] > 0){
                $order_income_price = ($order['order_income_price'] - $order['platform_fee']) * $order['exchange_rate'];
            }else{
                $currency = $order['currency'];
                if(empty($currency)) {
                    $currency = Shop::find()->where(['id' => $order['shop_id']])->select('currency')->scalar();
                }
                $order_income_price = CountryService::getConvertRMB($order['order_income_price'] - $order['platform_fee'], $currency);
            }
            $order_profit = $order_income_price - $order['order_cost_price'] - $order['freight_price'];
        }
        return $order_profit;
    }

    /**
     * 税计算
     * @param double $price 总金额
     * @param double $tax_rate 税率
     * @return float|int
     */
    public static function calculateTax($price,$tax_rate)
    {
        return round($price * $tax_rate / (1 + $tax_rate));
    }

    /**
     * 生成物流面单
     * @param $order_id
     * @return bool
     * @throws Exception
     */
    public static function genLogisticsPdf($order_id)
    {
        $label_arr = [
            100 => ['name'=>'GP-1324D面单','length'=>1000,'width'=>1000,],
            150 => ['name'=>'GP-1324D面单150','length'=>1000,'width'=>1500,]
        ];
        $label = 100;

        $hostinfo = \Yii::$app->request->hostinfo;
        $order = self::getOneByOrderId($order_id);

        if(in_array($order['source'] ,[Base::PLATFORM_LINIO,Base::PLATFORM_HEPSIGLOBAL])) {
            $label = 150;
        }

        if (in_array($order['source'], FApiService::$own_Logistics) || $order['integrated_logistics'] == Order::INTEGRATED_LOGISTICS_YES) {
            if($order['source'] == Base::PLATFORM_OZON) {
                $label = 150;
            }
        } else {
            $shipping_method = ShippingMethod::find()->where(['id' => $order['logistics_channels_id']])->asArray()->one();
            if($shipping_method['transport_code'] == 'xingyuan') {//兴远
                $label = 150;
            }
        }

        $basepath = \Yii::getAlias('@webroot');
        if (!empty($order['logistics_pdf'])) {
            //验证文件是否存在
            if (file_exists($basepath . $order['logistics_pdf'])) {
                return [
                    'pdf_url' => $hostinfo . $order['logistics_pdf'],
                    'label' => $label_arr[$label],
                ];
            }
        }

        $url = false;
        if (in_array($order['source'], FApiService::$own_Logistics) || $order['integrated_logistics'] == Order::INTEGRATED_LOGISTICS_YES) {
            $shop = Shop::find()->where(['id' => $order['shop_id']])->asArray()->one();
            $url = FApiService::factory($shop)->doPrint($order);
        } else {
            $ser = FTransportService::factory($shipping_method['transport_code']);
            $re = $ser->doPrint([$order]);
            if ($re['error'] != BaseTransportService::RESULT_FAIL) {
                $url = $re['data']['pdf_url'];
            }
        }

        if (empty($url)) {
            return false;
        }

        if (strpos($url, $hostinfo) === false) {
            $content = file_get_contents($url);
            if (strlen($content) < 1000) {
                return false;
            }
            $re = CommonUtil::savePDF($content);
            if (empty($re)) {
                return false;
            }
            $url = $re['pdf_url'];
        }

        $order['logistics_pdf'] = str_replace($hostinfo, '',$url);
        $order->save();
        return [
            'pdf_url' => $url,
            'label' => $label_arr[$label],
        ];
    }

    /**
     * 刷新缺货订单状态
     * @return bool
     */
    public static function refreshOutStockStatus()
    {
        $order = Order::find()->where(['order_status' => Order::ORDER_STATUS_WAIT_PRINTED_OUT_STOCK])->all();
        foreach ($order as $v) {
            $order_id = $v['order_id'];
            if ((new PurchaseProposalService())->verifyOrderInStock($order_id, OrderStockOccupy::TYPE_STOCK)) {
                Order::updateAll(['order_status' => Order::ORDER_STATUS_WAIT_PRINTED], ['order_id' => $order_id]);
            }
        }
        return true;
    }

    /**
     * 发货
     * @param $order_id
     * @param int $weight
     * @param string $size
     * @param bool $force 强制发货（不管亏本）
     * @return bool
     * @throws Exception
     * @throws \Throwable
     */
    public static function ship($order_id,$weight = 0,$size = '',$force = false)
    {
        $order = self::getOneByOrderId($order_id);
        if ($order['order_status'] != Order::ORDER_STATUS_WAIT_SHIP) {
            throw new \Exception($order_id . '订单状态不是待发货');
        }
        if ($order['abnormal_time'] > 0) {
            throw new \Exception($order_id . '该订单处于异常状态不能出库');
        }
        $order_goods = OrderGoods::find()->where(['order_id' => $order_id])->asArray()->all();
        $sku_no = [];
        $all_num = 0;
        $cgoods_no = '';

        $data = [];
        if ($weight > 0) {
            $data['weight'] = $weight;
        }
        if (!empty($size)) {
            $data['size'] = $size;
        }
        $is_own_warehouse = !empty(WarehouseService::$warehouse_map[$order['warehouse']]);

        $freight_price = 0;
        if(in_array($order['source'],FApiService::$own_Logistics) || $order['integrated_logistics'] == Order::INTEGRATED_LOGISTICS_YES) {
            $order['weight'] = $weight;
            $order['size'] = $size;
            $freight_price = self::getOwnLogisticsPrice($order);
            $freight_price = $freight_price === false?0:$freight_price;
        } else {
            $freight_price = self::getMethodLogisticsFreightPrice($order['logistics_channels_id'], $order['country'], $weight, $size);
        }

        //执行完成
        $exec_finish = false;
        if ($freight_price > 0) {
            $data['freight_price'] = $freight_price;
            $order['freight_price'] = $freight_price;
            $order_profit = self::calculateProfitPrice($order);
            $data['order_profit'] = $order_profit;
            if ($order_profit < 0 && !$force) {
                $exec_finish = true;
            }
        }

        //减商品库存
        foreach ($order_goods as $v) {
            if (empty($v['cgoods_no'])) {
                throw new \Exception($order_id . '订单商品信息不完整');
            }
            $cgoods_no = $v['cgoods_no'];
            $num = GoodsStock::find()->where(['cgoods_no' => $cgoods_no, 'warehouse' => $order['warehouse']])->select('num')->scalar();
            if ($num < $v['goods_num']) {
                throw new \Exception($v['platform_asin'] . '商品库存不足');
            }
            $all_num += $v['goods_num'];
            $sku_no[] = $v['platform_asin'];
            if(!$exec_finish) {
                //减库存
                GoodsStockService::changeStock($v['cgoods_no'], $order['warehouse'], GoodsStockService::TYPE_OUT_OF_STOCK, -$v['goods_num'], $order_id);
            }
        }

        if (!$exec_finish) {
            if (empty($data['delivery_time'])) {
                $data['delivery_time'] = time();
            }
            $data['delivery_status'] = 10;
            $data['order_status'] = $is_own_warehouse ? Order::ORDER_STATUS_FINISH : Order::ORDER_STATUS_SHIPPED;
        }
        Order::updateOneByCond(['order_id' => $order_id], $data);
        if ($all_num == 1 && count($sku_no) == 1) {
            $price_data = [];
            //重量变更
            if ($weight > 0) {
                $price_data['real_weight'] = $weight;
            }
            //尺寸变更
            if (!empty($size)) {
                $price_data['package_size'] = $size;
            }
            if(!empty($price_data)) {
                (new GoodsService())->updateChildPrice($cgoods_no, $price_data, '订单出库');
            }
        }

        if (!$exec_finish) {
            OrderEventService::addEvent($order['source'], $order['shop_id'], $order_id, OrderEvent::EVENT_TYPE_SHIPPING);
            if (in_array($order['source'],[Base::PLATFORM_OZON,Base::PLATFORM_LINIO])) {
                (new OrderLogisticsStatusService())->addLogisticsStatus($order['order_id']);
            }
            OrderStockOccupy::deleteAll(['sku_no' => $sku_no, 'warehouse' => $order['warehouse'], 'type' => OrderStockOccupy::TYPE_STOCK]);
            (new PurchaseProposalService())->updatePurchaseProposal($order['warehouse'], $sku_no);
            //(new PurchaseProposalService())->verifyOrderInStock($order_id,OrderStockOccupy::TYPE_STOCK);
        } else {
            CommonUtil::logs('order_id:'.$order_id .' 亏本金额：' .$order_profit, 'order_deficit');
            throw new \Exception('该订单亏本', 3004);
        }
        return true;
    }

    /**
     * 更新订单状态
     * @param $order_id
     */
    public static function updateOrderStatus($order_id)
    {
        $order = self::getOneByOrderId($order_id);
        if($order['order_status'] == Order::ORDER_STATUS_SHIPPED) {
            return ;
        }

        if($order['order_status'] == Order::ORDER_STATUS_FINISH) {
            return ;
        }

        $status = Order::ORDER_STATUS_UNCONFIRMED;
        $goods_status = OrderGoods::find()->where(['order_id'=>$order_id])->select('goods_status')->column();
        if(!empty($goods_status)){
            $status = Order::ORDER_STATUS_WAIT_PURCHASE;
            foreach ($goods_status as $v){
                if($v == OrderGoods::GOODS_STATUS_NORMAL){
                    $status = Order::ORDER_STATUS_WAIT_PURCHASE;
                }
            }
        }

        //更新自建订单状态
        if($order['source_method'] == GoodsService::SOURCE_METHOD_OWN) {
            $status = (new PurchaseProposalService())->verifyOrderInStock($order['order_id']) ? Order::ORDER_STATUS_WAIT_PURCHASE : Order::ORDER_STATUS_UNCONFIRMED;
        }

        $buy_goods_status = BuyGoods::find()->where(['order_id'=>$order_id])->select('buy_goods_status')->column();
        if(!empty($buy_goods_status)) {
            foreach ($buy_goods_status as $v) {
                if($v == BuyGoods::BUY_GOODS_STATUS_FINISH) {
                    $status = Order::ORDER_STATUS_WAIT_SHIP;
                }
            }
        }

        if($order['order_status'] == Order::ORDER_STATUS_WAIT_SHIP && $order['delivery_status'] == Order::DELIVERY_SHIPPED) {
            $status = Order::ORDER_STATUS_SHIPPED;
            $order['delivery_time'] = time();
            OrderEventService::addEvent($order['source'], $order['shop_id'], $order_id, OrderEvent::EVENT_TYPE_SHIPPING);
            if (in_array($order['source'],[Base::PLATFORM_OZON,Base::PLATFORM_LINIO])) {
                (new OrderLogisticsStatusService())->addLogisticsStatus($order['order_id']);
            }
        }

        if($status != $order['order_status'] && $status != Order::ORDER_STATUS_UNCONFIRMED) {
            $order['order_status'] = $status;
            $order->save();
            //更新采购建议 + 这里后续可能还要涉及减库存
            if($order['source_method'] == GoodsService::SOURCE_METHOD_OWN) {
                if ($status == Order::ORDER_STATUS_SHIPPED) {
                    $order_stock_occupy = OrderStockOccupy::find()->where(['order_id'=>$order_id,'type'=>OrderStockOccupy::TYPE_STOCK])->all();
                    if (!empty($order_stock_occupy)) {
                        foreach ($order_stock_occupy as $occupy) {
                            $occupy->delete();
                        }
                    }
                    (new PurchaseProposalService())->updatePurchaseProposalToOrderId($order_id);
                }
            }
        }
    }

    /**
     * 取消订单
     * @param $order_id
     * @param $cancel_reason
     * @param $cancel_remarks
     * @return bool
     * @throws Exception
     * @throws \Exception
     * @throws \Throwable
     */
    public function cancel($order_id,$cancel_reason = 0,$cancel_remarks = '')
    {
        $order = self::getOneByOrderId($order_id);
        if(empty($order) || $order['order_status'] == Order::ORDER_STATUS_CANCELLED){
            return false;
        }

        $order['order_status'] = Order::ORDER_STATUS_CANCELLED;
        $order['cancel_time'] = time();
        $order['cancel_reason'] = $cancel_reason;
        $order['cancel_remarks'] = $cancel_remarks;
        if($order->save()){
            $buy_goods = BuyGoods::find()->where(['order_id'=>(string)$order_id])->asArray()->all();
            if(!empty($buy_goods)) {
                foreach ($buy_goods as $buy_good) {
                    if (!empty($buy_good['swipe_buyer_id']) && !in_array($buy_good['buy_goods_status'], [BuyGoods::BUY_GOODS_STATUS_NONE, BuyGoods::BUY_GOODS_STATUS_OUT_STOCK, BuyGoods::BUY_GOODS_STATUS_ERROR_CON])) {
                        try {
                            $price = $buy_good['buy_goods_price']*$buy_good['buy_goods_num'];
                            BuyerAccountTransactionService::refund($buy_good['swipe_buyer_id'], $price, $buy_good['id']);
                        } catch (\Exception $e) {
                            CommonUtil::logs('订单退款错误：' . $buy_good['id'] . ' 分机号:' . $buy_good['swipe_buyer_id'] . ' 金额：' . $price . ' ' . $e->getMessage(), 'buyer_account_transaction_error');
                        }
                    }
                }
                BuyGoods::updateAll(['buy_goods_status' => BuyGoods::BUY_GOODS_STATUS_DELETE], ['order_id' => (string)$order_id]);
            }
            OrderStockOccupy::deleteAll(['order_id'=>(string)$order_id]);
            //关闭异常
            (new OrderAbnormalService())->closeAbnormalToOrderId($order_id,'订单取消');
            (new PurchaseProposalService())->updatePurchaseProposalToOrderId((string)$order_id);
            //(new PurchaseProposalService())->updatePurchaseProposalToOrderId($order_id);
            (new SystemOperlogService())->addOrderLog($order_id,['order_status'=>Order::ORDER_STATUS_CANCELLED,'cancel_reason'=>$cancel_reason,'cancel_remarks'=>$cancel_remarks],SystemOperlogService::ACTION_ORDER_CANCEL,$cancel_remarks);
            //ozon 取消订单重新更新商品库存
            if($order['source'] == Base::PLATFORM_OZON) {
                $order_goods = OrderGoods::find()->where(['order_id'=>$order_id])->all();
                foreach ($order_goods as $order_goods_v) {
                    if(!empty($order_goods_v['cgoods_no'])) {
                        $goods_shop = GoodsShop::find()->where(['cgoods_no'=>$order_goods_v['cgoods_no'],'shop_id'=>$order['shop_id']])->all();
                        foreach ($goods_shop as $goods_shop_v) {
                            GoodsEventService::addEvent($goods_shop_v, GoodsEvent::EVENT_TYPE_UPDATE_STOCK,1);
                        }
                    }
                }
            }
            return true;
        }
        return false;
    }

    /**
     * 订单退款
     * @param $order_id
     * @param $cancel_reason
     * @param $cancel_remarks
     * @return bool
     * @throws Exception
     * @throws \Exception
     * @throws \Throwable
     */
    public function refund($order_id,$cancel_reason = 0,$cancel_remarks = '')
    {
        $order = self::getOneByOrderId($order_id);
        if (empty($order) || $order['order_status'] == Order::ORDER_STATUS_REFUND || $order['order_status'] != Order::ORDER_STATUS_FINISH) {
            return false;
        }

        $order['order_status'] = Order::ORDER_STATUS_REFUND;
        $order['cancel_time'] = time();
        $order['cancel_reason'] = $cancel_reason;
        $order['cancel_remarks'] = $cancel_remarks;
        if ($order->save()) {
            $order_refund = OrderRefund::find()->where(['order_id' => $order_id])->one();
            if(empty($order_refund)) {
                $order_refund = new OrderRefund();
                $order_refund['order_id'] = $order_id;
            }
            $order_refund['refund_reason'] = $cancel_reason;
            $order_refund['refund_remarks'] = $cancel_remarks;
            $order_refund['refund_type'] = OrderRefund::REFUND_ONE;
            $order_refund['admin_id'] = 0;
            $order_refund['refund_num'] = $order['order_income_price'];
            $order_refund->save();

            $buy_goods = BuyGoods::find()->where(['order_id' => (string)$order_id])->asArray()->all();
            if (!empty($buy_goods)) {
                foreach ($buy_goods as $buy_good) {
                    if (!empty($buy_good['swipe_buyer_id']) && !in_array($buy_good['buy_goods_status'], [BuyGoods::BUY_GOODS_STATUS_NONE, BuyGoods::BUY_GOODS_STATUS_OUT_STOCK, BuyGoods::BUY_GOODS_STATUS_ERROR_CON])) {
                        try {
                            $price = $buy_good['buy_goods_price'] * $buy_good['buy_goods_num'];
                            BuyerAccountTransactionService::refund($buy_good['swipe_buyer_id'], $price, $buy_good['id']);
                        } catch (\Exception $e) {
                            CommonUtil::logs('订单退款错误：' . $buy_good['id'] . ' 分机号:' . $buy_good['swipe_buyer_id'] . ' 金额：' . $price . ' ' . $e->getMessage(), 'buyer_account_transaction_error');
                        }
                    }
                }
                BuyGoods::updateAll(['buy_goods_status' => BuyGoods::BUY_GOODS_STATUS_DELETE], ['order_id' => (string)$order_id]);
            }
            OrderStockOccupy::deleteAll(['order_id' => (string)$order_id]);
            //关闭异常
            (new OrderAbnormalService())->closeAbnormalToOrderId($order_id, '订单退款');
            (new PurchaseProposalService())->updatePurchaseProposalToOrderId((string)$order_id);
            //(new PurchaseProposalService())->updatePurchaseProposalToOrderId($order_id);
            (new SystemOperlogService())->addOrderLog($order_id, ['order_status' => Order::ORDER_STATUS_REFUND, 'cancel_reason' => $cancel_reason, 'cancel_remarks' => $cancel_remarks], SystemOperlogService::ACTION_ORDER_REFUND, $cancel_remarks);
            return true;
        }
        return false;
    }

    /**
     * 发票
     * @param $order_id
     * @return \TCPDF
     */
    public function invoice($order_id){
        $model = Order::find()->where(['order_id'=>$order_id])->asArray()->one();
        $shop = Shop::findOne(['id'=>$model['shop_id']]);
        $pdf = new \TCPDF('P', 'mm',  'S12R', true, 'UTF-8', false);

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $family = $model['source'] != Base::PLATFORM_ALLEGRO ? 'helvetica' : 'arial';
        $pdf->SetFont($family, '', 9);

        $pdf->AddPage();

        if ($model['source'] != Base::PLATFORM_ALLEGRO) {
            //发票号：R+月份+年份+-8位随机数字（如R1120-12345678）
            $invoice_no = 'R' . date('Ym', $model['date']) . '-' . CommonUtil::randString(8, 1);

            $model['invoice_no'] = $invoice_no;

            $order_goods = OrderGoods::find()->where(['order_id'=>$order_id,'goods_status'=>[OrderGoods::GOODS_STATUS_UNCONFIRMED,OrderGoods::GOODS_STATUS_NORMAL]])->asArray()->all();
            $goods = [];

            foreach ($order_goods as $v){
                $price3 = $v['goods_income_price'] * $v['goods_num'];//售价
                $price1 = number_format($price3 / 1.19, 2);//税前价格 售价除以1.16
                $price2 = number_format($price1 * 0.19, 2);//增值税金额 售价乘以0.16
                $goods[] = [
                    'goods_name' => $v['goods_name'],
                    'goods_num' => $v['goods_num'],
                    'price1' => $price1,
                    'price2' => $price2,
                    'price3' => $price3
                ];
            }
        } else {
            $goods = $this->getAllegroInvoice($model);
            $model = $this->getAllegroCountryTitle($model);
            $shop['invoice_template'] = 'fp-allegro';
        }


        $html = \Yii::$app->getView()->render('/order/invoice/'.$shop['invoice_template'],[
            'model' => $model,
            'goods' => $goods
        ]);

        $pdf->writeHTML($html, true, false, true, false, '');
        return $pdf;
    }

    /**
     * 获取allegro城市标题语言
     * @param $model
     * @return array
     */
    public function getAllegroCountryTitle($model)
    {
        $model['country_name'] = $model['country'] == 'PL' ? 'PL/N' : 'CZ';
        $model['order_date_name'] = 'Data zakończenia usługi' ;
        $model['delivery_time_name'] = 'Data wydania' ;
        $model['shop_name'] = 'Sprzedawca' ;
        $model['correspondence'] = 'Korespondencję';
        $model['user'] = 'Kupujący';
        $model['goods_title_name'] = 'Nazwa';
        $model['date_name'] = 'Data wyprzedaży';
        $model['goods_title_desc'] = 'Opisać';
        $model['title_size'] = 'rozmiar';
        $model['goods_title_num'] = 'Ilość';
        $model['price_title1'] = 'Cena jednostkowa netto';
        $model['price_title2'] = 'Sprzedaż netto';
        $model['price_title3'] = 'Obowiązująca stawka podatku';
        $model['price_title4'] = 'Całkowita sprzedaż';
        $model['price_title5'] = 'Całkowity podatek od sprzedaży netto';
        $model['price_title6'] = 'Opłaty za fracht';
        $model['price_title7'] = 'Razem zaległości';
        $model['currency'] = 'zł';
        $model['tax_rate'] = '23%';
        if ($model['country'] == 'CZ') {
            $model['order_date_name'] = 'Datum dokončení služeb' ;
            $model['delivery_time_name'] = 'Datum vydání' ;
            $model['shop_name'] = 'prodejci' ;
            $model['correspondence'] = 'Kontaktujte nás';
            $model['user'] = 'kupující';
            $model['goods_title_name'] = 'název';
            $model['date_name'] = 'Datum prodeje';
            $model['goods_title_desc'] = 'popisy';
            $model['title_size'] = 'velikosti';
            $model['goods_title_num'] = 'množství';
            $model['price_title1'] = 'čisté jednotkové náklady';
            $model['price_title2'] = 'čistý prodej';
            $model['price_title3'] = 'Sazba daně';
            $model['price_title4'] = 'celkové tržby';
            $model['price_title5'] = 'čistý prodej';
            $model['price_title6'] = 'poplatek za přepravu';
            $model['price_title7'] = 'Celkový počet nevyřízených zakázek';
            $model['currency'] = 'Kč';
            $model['tax_rate'] = '21%';
        }
        $model['date_time'] = date('Y',$model['date']) . '/' . date('m',$model['date']);
        $model['order_date'] = date('Y-m-d',$model['date']);
        $model['delivery_time'] = $model['delivery_time'] == 0 ? $model['order_date'] : date('Y-m-d',$model['delivery_time']);
        return $model;
    }

    /**
     * 获取allegro发票信息
     * @param $order
     * @return array
     */
    public function getAllegroInvoice($order)
    {
        $order_goods = OrderGoods::find()->where(['order_id' => $order['order_id'],'goods_status' => [OrderGoods::GOODS_STATUS_UNCONFIRMED, OrderGoods::GOODS_STATUS_NORMAL]])->asArray()->all();
        $cgoods_nos = ArrayHelper::getColumn($order_goods,'cgoods_no');
        $goods_child = GoodsChild::find()->where(['cgoods_no' => $cgoods_nos])->indexBy('cgoods_no')->asArray()->all();
        $goods = [];
        $tax_rate = $order['country'] == 'PL' ? 1.23 : 1.21;
        foreach ($order_goods as $v) {
            $price1 = $v['goods_income_price'] / $tax_rate;
            $price2 = $price1 * $v['goods_num'];
            $price3 = $v['goods_income_price'] * $v['goods_num'];
            $price4 = $price3 - $price2;
            $child = empty($goods_child[$v['cgoods_no']]) ? [] : $goods_child[$v['cgoods_no']];
            $date = date('M',$order['date']). ' ' .date('d',$order['date']).','.date('Y',$order['date']);
            if ($order['country'] == 'CZ') {
                $month = date('m',$order['date']);
                $month = OrderService::$month_czk[$month];
                $date = date('d',$order['date']).'.'.$month.' '.date('Y',$order['date']);
            }
            $goods[] = [
                'goods_name' => htmlentities($v['goods_name']),
                'date' => $date,
                'size' => empty($child) ? '' : $child['package_size'],
                'goods_num' => $v['goods_num'],
                'goods_desc' => htmlentities($v['goods_name']),
                'price1' => number_format($price1, 2,',',''),
                'price2' => number_format($price2, 2,',',''),
                'price3' => number_format($price3,2,',',''),
                'price4' => number_format($price4,2,',','')
            ];
        }
        return $goods;
    }

    /**
     * 捷克语月份
     */
    public static $month_czk = [
        '01' => 'leden',
        '02' => 'únor',
        '03' => 'březen',
        '04' => 'duben',
        '05' => 'květen',
        '06' => 'červen',
        '07' => 'červenec',
        '08' => 'srpen',
        '09' => 'září',
        10 => 'říjen',
        11 => 'listopad',
        12 => 'prosinec',
    ];

    /**
     * 获取税号
     * @param $order
     * @return string
     */
    public function getTaxNumber($order)
    {
        //不是欧盟不处理
        /*if(!CountryService::isEuropeanUnion($order['country'])){
            return '';
        }*/

        if (!empty($order['tax_number'])) {
            return $order['tax_number'];
        }

        //大于150欧元的不处理
        /*if($order['order_income_price'] >= 150) {
            return '';
        }*/

        /*$ioss = Shop::find()->where(['id'=>$order['shop_id']])->select('ioss')->scalar();
        if(empty($ioss)){
           return '';
        }*/

        return '';
    }

    /**
     * 推荐物流（商品）
     * @param $sku_no
     * @throws Exception
     */
    public function recommendedLogisticsToGoods($sku_no)
    {
        //推荐物流方式
        $where = [
            'order_status'=>[
                Order::ORDER_STATUS_UNCONFIRMED,
                Order::ORDER_STATUS_WAIT_PURCHASE,
            ],
            'og.source_method' => GoodsService::SOURCE_METHOD_OWN,
        ];
        $where['platform_asin'] = $sku_no;
        $order_ids = OrderGoods::find()->alias('og')
            ->leftJoin(Order::tableName() . ' o', 'o.order_id= og.order_id')
            ->where($where)->select('o.order_id')->column();
        foreach($order_ids as $order_id) {
            $this->recommendedLogistics($order_id);
        }
    }

    /**
     * 获取预估自有物流价格
     * @param $order
     * @return array|bool
     */
    public static function getOwnLogisticsPrice($order)
    {
        if ($order['source_method'] != GoodsService::SOURCE_METHOD_OWN) {
            return false;
        }
        if (!in_array($order['source'], FApiService::$own_Logistics) && $order['integrated_logistics'] != Order::INTEGRATED_LOGISTICS_YES) {
            return false;
        }
        $shipp_method = ShippingMethod::find()
            ->where(['transport_code'=>'sys','shipping_method_name'=>$order['logistics_channels_name']])
            ->one();
        if(empty($shipp_method)){
            return false;
        }

        $weight = 0;
        $size = '';
        if(!empty($order['weight']) && $order['weight'] > 0) {
            $weight = $order['weight'];
            $size = $order['size'];
        } else {
            $order_goods = OrderGoods::find()->where(['order_id' => $order['order_id']])->asArray()->all();
            foreach ($order_goods as $order_good_v) {
                $asin = $order_good_v['platform_asin'];
                $goods = GoodsChild::find()->where(['sku_no' => $asin])->asArray()->one();
                if ($goods['real_weight'] > 0) {
                    $real_weight = $goods['real_weight'];//实际重量不同运营商不一致
                } else {
                    $real_weight = $goods['weight'];
                }
                $weight += $real_weight * $order_good_v['goods_num'];
                if(!empty($goods['package_size'])) {
                    $size = $goods['package_size'];
                }
            }
        }
        return self::getMethodLogisticsFreightPrice($shipp_method['id'], $order['country'], $weight, $size);
    }

    /**
     * 获取物流预估价格
     * @param $order_id
     * @return array|bool
     */
    public function getLogisticsPrice($order_id) {
        $order = Order::findOne(['order_id' => $order_id]);
        if ($order['source_method'] != GoodsService::SOURCE_METHOD_OWN) {
            return false;
        }

        $order_goods = OrderGoods::find()->where(['order_id' => $order_id])->asArray()->all();

        $electric = Base::ELECTRIC_ORDINARY;
        $has_real = true;
        //$size = '';
        //$weight = 0;
        $goods_electric = Base::ELECTRIC_ORDINARY;
        foreach ($order_goods as &$order_good_v) {
            $goods_child = GoodsChild::find()->where(['cgoods_no' => $order_good_v['cgoods_no']])->asArray()->one();
            if (empty($goods_child)) {
                return false;
            }
            $goods = Goods::find()->where(['goods_no' => $goods_child['goods_no']])->asArray()->one();
            if (empty($goods)) {
                return false;
            }
            if ($goods_child['real_weight'] <= 0) {
                $has_real = false;
                //$real_weight = $goods['weight'];
            } else {
                //$real_weight = $goods['real_weight'];
            }
            $goods_electric = max($goods_electric,$goods['electric']);
            //$real_weight = abs($real_weight) < 0.00001?0.5:$real_weight;
            //$weight += $real_weight * $order_good_v['goods_num'];
            if (in_array($goods['electric'], [Base::ELECTRIC_SPECIAL, Base::ELECTRIC_SENSITIVE])) {
                $electric = Base::ELECTRIC_SPECIAL;
            }
            $goods = (new GoodsService())->dealGoodsInfo($goods, $goods_child);
            $order_good_v['goods'] = $goods;
            /*if(!empty($goods['size'])) {
                $size = $goods['size'];
            }*/
        }
        if (in_array($order['source'], FApiService::$own_Logistics) || $order['integrated_logistics'] == Order::INTEGRATED_LOGISTICS_YES) {
            if(empty($order['logistics_channels_name'])) {
                return false;
            }
            $shipp_where = ['sm.transport_code'=>'sys','sm.shipping_method_name'=>$order['logistics_channels_name']];
        }else {
            $warehouse_id = $order['warehouse'];
            $warehouse_type = WarehouseService::getWarehouseProviderType($warehouse_id);
            $where = [];
            if ($electric == Base::ELECTRIC_SPECIAL) {
                $where['electric_status'] = Base::ELECTRIC_SPECIAL;
            }
            $where['status'] = ShippingMethod::STATUS_VALID;
            if ($order['source'] == Base::PLATFORM_OZON) {
                /*$where['shipping_method_code'] = [
                    437,//燕文航空挂号-普货
                    456,//燕文航空挂号-特货
                    3661,//Leader急速达
                    3681,//Leader承诺达
                    4161,//Leader快速(内电)
                    4101,//Leader快速(普货)
                    4141,//Leader经济
                ];*/
                $where['transport_code'] = ['hualei', 'jnet', 'xingyuan'];
            }
            $where['recommended'] = ShippingMethod::RECOMMENDED_YES;
            if($warehouse_type == WarehouseProvider::TYPE_THIRD_PARTY) {
                $where['warehouse_id'] = $warehouse_id;
                if ($order['source'] == Base::PLATFORM_ALLEGRO && in_array($order['shop_id'],[492,493]) && $order['country'] == 'PL') {
                    $where['id'] = [
                        2272,//捷克仓DHL国际派送
                        2274,//捷克仓DHL德国派送
                    ];
                }
                $has_real = true;
            }else{
                $where['warehouse_id'] = 0;
            }
            $shipping_method_model = ShippingMethod::find()->where($where);
            /*if($order['order_income_price'] < 10) {
                $shipping_method_model = $shipping_method_model->andWhere(['like','shipping_method_name','平邮']);
            } else {
                $shipping_method_model = $shipping_method_model->andWhere(['not like','shipping_method_name','平邮']);
            }*/
            $shipping_method_ids = $shipping_method_model->select('id')->asArray()->column();
            $shipp_where = ['shipping_method_id' => $shipping_method_ids, 'country_code' => $order['country']];
        }
        $shipp_offer = ShippingMethodOffer::find()->select('smo.*,sm.cjz,sm.formula as cjz_formula,sm.currency')
            ->alias('smo')->leftJoin(ShippingMethod::tableName() . ' as sm', 'smo.shipping_method_id=sm.id')
            ->where($shipp_where)->asArray()->all();
        $shipping_method_logistics = [
            'has_real' => $has_real,
            //'electric_status' => $electric,
            //'size' => $size,
            //'weight' => $weight,
            'logistics' => [],
        ];

        foreach ($shipp_offer as $v) {
            $all_length = 0;
            $weight = 0;
            $exist = false;
            $other_price = 0;//另外收费
            $has_cjz = false;//是否要计泡重
            $use_cjz = false;
            if(!empty($v['cjz_formula']) && $v['cjz'] > 0) {
                foreach ($order_goods as $goods_v) {
                    $goods = $goods_v['goods'];
                    if (!ShippingMethod::execFormula($goods, $v['cjz_formula'])) {
                        $has_cjz = true;
                    }
                }
            } else {
                $has_cjz = true;
            }

            if(!empty($goods['size'])) {
                $size = GoodsService::getSizeArr($goods['size']);
                if (!empty($size)) {
                    $tmp_weight = 0;
                    foreach ($order_goods as $goods_v) {
                        $goods = $goods_v['goods'];
                        if ($goods['real_weight'] > 0) {
                            $real_weight = $goods['real_weight'];//实际重量不同运营商不一致
                        } else {
                            $real_weight = $goods['weight'];
                        }
                        $tmp_weight += $real_weight * $goods_v['goods_num'];
                    }
                    $all_length += (float)$size['size_l'] + (float)$size['size_w'] + (float)$size['size_h'];
                    $cjz_arr = self::hasCjz($v,$size,$tmp_weight);
                    $has_cjz = is_null($cjz_arr['has_cjz'])?$has_cjz:$cjz_arr['has_cjz'];
                    $other_price = $cjz_arr['other_price'];
                }
            }

            foreach ($order_goods as $goods_v) {
                $goods = $goods_v['goods'];
                $weight_cjz = empty($v['cjz'])?0:GoodsService::cjzWeight($goods['size'], $v['cjz'],0);
                if ($goods['real_weight'] > 0) {
                    $real_weight = $goods['real_weight'];//实际重量不同运营商不一致
                } else {
                    $real_weight = $goods['weight'];
                }
                if($has_cjz && $weight_cjz > $real_weight) {
                    $use_cjz = true;
                    $real_weight = $weight_cjz;
                }
                $real_weight = abs($real_weight) < 0.00001 ? 0.5 : $real_weight;
                $weight += $real_weight * $goods_v['goods_num'];

                if (!ShippingMethod::execFormula($goods, $v['formula'])) {
                    $exist = true;
                }
            }
            if ($exist) {
                continue;
            }
            $offers = $this->getOffers($v, $weight, $other_price);
            if($offers !== false) {
                $shipping_method_logistics['logistics'][] = [
                    'use_cjz' => $use_cjz,//使用材积重
                    'weight' => $weight,
                    'all_length' => $all_length,
                    'transport_code' => $v['transport_code'],//渠道
                    'shipping_method_id' => $v['shipping_method_id'],
                    'price' => $offers,
                    'goods_electric' => $goods_electric,
                ];
            }
        }
        return $shipping_method_logistics;
    }

    /**
     * 是否计算材积重
     * @param $shipp_offer
     * @param $size
     * @param $weight
     * @return array
     */
    public static function hasCjz($shipp_offer, $size, $weight)
    {

        $other_price = 0;
        if ($shipp_offer['shipping_method_code'] == 'RU-NZH') {
            $has_cjz = false;
            if (!empty($size)) {
                $size_l = (float)$size['size_l'];
                $size_w = (float)$size['size_w'];
                $size_h = (float)$size['size_h'];
                $max_l = (float)max($size_l, $size_h, $size_w);
                //俄全通 单边长大于150加收100 单边长大于180加收150 单边长大于200加收200 单边长大于300加收300
                if ($max_l > 150) {
                    $other_price = 100;
                }
                if ($max_l > 180) {
                    $other_price = 150;
                }
                if ($max_l > 200) {
                    $other_price = 200;
                }
                if ($max_l > 300) {
                    $other_price = 300;
                }


                if ($size_l + $size_w + $size_h > 90 && abs($weight) > 2) { //俄全通 实重大于2并且三边和大于90计泡重
                    $has_cjz = true;
                }
                return [
                    'has_cjz' => $has_cjz,
                    'other_price' => $other_price,
                ];
            }
        }

        return [
            'has_cjz' => null,
            'other_price' => $other_price,
        ];
    }

    /**
     * 获取渠道运费价格
     * @param $shipping_method_id
     * @param $country
     * @param $real_weight
     * @param string $real_size
     * @return float|int|mixed
     */
    public static function getMethodLogisticsFreightPrice($shipping_method_id,$country,$real_weight,$real_size = '')
    {
        if (empty($shipping_method_id)) {
            return 0;
        }

        if (abs($real_weight) < 0.01) {
            return 0;
        }

        $shipp_offer = ShippingMethodOffer::find()->select('smo.*,sm.cjz,sm.formula as cjz_formula,sm.currency')
            ->alias('smo')->leftJoin(ShippingMethod::tableName() . ' as sm', 'smo.shipping_method_id=sm.id')
            ->where(['shipping_method_id' => $shipping_method_id, 'country_code' => $country])->asArray()->all();
        foreach ($shipp_offer as $v) {
            $other_price = 0;//另外收费
            $has_cjz = false;//是否计泡重
            if (!empty($v['cjz_formula']) && $v['cjz'] > 0) {
                if (!ShippingMethod::execFormula(['size' => $real_size], $v['cjz_formula'])) {
                    $has_cjz = true;
                }
            } else {
                $has_cjz = true;
            }

            if (!empty($real_size)) {
                $size = GoodsService::getSizeArr($real_size);
                $cjz_arr = self::hasCjz($v, $size, $real_weight);
                $has_cjz = is_null($cjz_arr['has_cjz'])?$has_cjz:$cjz_arr['has_cjz'];
                $other_price = $cjz_arr['other_price'];
            }

            $weight_cjz = empty($v['cjz']) ? 0 : GoodsService::cjzWeight($real_size, $v['cjz'],0);

            if ($has_cjz) {
                $real_weight = max($weight_cjz, $real_weight);
            }
            $real_weight = abs($real_weight) < 0.00001 ? 0.5 : $real_weight;
            if (!ShippingMethod::execFormula(['size' => $real_size], $v['formula'])) {
                continue;
            }

            $offers = self::getOffers($v, $real_weight, $other_price);
            if($offers !== false) {
                return $offers;
            }
        }
        return 0;
    }

    /**
     * 推荐物流
     * @param $order_id
     * @return bool|int|mixed
     * @throws Exception
     */
    public function recommendedLogistics($order_id,$mandatory_update = false)
    {
        $order = Order::findOne(['order_id' => $order_id]);
        //海外仓
        $warehouse_id = $order['warehouse'];
        $warehouse_type = WarehouseService::getWarehouseProviderType($warehouse_id);
        $freight_price = 0;
        if (in_array($warehouse_type ,[WarehouseProvider::TYPE_PLATFORM,WarehouseProvider::TYPE_THIRD_PARTY])) {
            $order_goods = OrderGoods::find()->where(['order_id' => $order['order_id']])->asArray()->all();
            foreach ($order_goods as $v) {
                $ov_warehouse = GoodsShopOverseasWarehouse::find()->where(['cgoods_no' => $v['cgoods_no'], 'shop_id' => $order['shop_id']])->one();
                if (!empty($ov_warehouse)) {
                    $freight_price += $ov_warehouse['start_logistics_cost'] > 0 ? $ov_warehouse['start_logistics_cost'] : $ov_warehouse['estimated_start_logistics_cost'];
                }
            }
            if ($warehouse_type == WarehouseProvider::TYPE_PLATFORM) {//平台海外仓不需要推荐物流
                $order['freight_price'] = $freight_price;
                $order['order_profit'] = self::calculateProfitPrice($order);
                $order->save();
                return true;
            } else {
                $order['freight_price'] = 0;
            }
        } else {//集成物流运费
            if (in_array($order['source'], FApiService::$own_Logistics) || $order['integrated_logistics'] == Order::INTEGRATED_LOGISTICS_YES) {
                $freight_price = self::getOwnLogisticsPrice($order);
                if ($freight_price === false) {
                    return false;
                }
                $order['freight_price'] = $freight_price;
                $order['order_profit'] = self::calculateProfitPrice($order);
                $order->save();
                return true;
            }
        }

        $logistics = $this->getLogisticsPrice($order_id);
        if (empty($logistics) || empty($logistics['logistics'])) {
            return false;
        }

        $shipping_method_id = 0;
        $min_offers = null;
        $cur_logistics = [];
        $goods_electric = Base::ELECTRIC_ORDINARY;
        foreach ($logistics['logistics'] as $v) {
            if (is_null($min_offers) || $min_offers > $v['price']) {
                $min_offers = $v['price'];
                $shipping_method_id = $v['shipping_method_id'];
                $cur_logistics = $v;
            }
        }
        //推荐物流是燕文的时候需要重新判断云途的价格 并且长度小于70 两者相差价格小于50优先推荐云途
        /*if(!empty($cur_logistics['transport_code']) && $cur_logistics['transport_code'] == 'yanwen' && $cur_logistics['all_length'] < 85 && ($cur_logistics['use_cjz'] || $cur_logistics['all_length'] == 0)) {
            $yt_cur_logistics = [];
            $new_min_offers = null;
            foreach ($logistics['logistics'] as $v) {
                if ($v['transport_code'] == 'yuntu') {
                    if (is_null($new_min_offers) || $new_min_offers > $v['price']) {
                        $new_min_offers = $v['price'];
                        $yt_cur_logistics = $v;
                    }
                }
            }
            if (!empty($yt_cur_logistics)) {
                if ($yt_cur_logistics['price'] - $cur_logistics['price'] < 50) {
                    $min_offers = $yt_cur_logistics['price'];
                    $shipping_method_id = $yt_cur_logistics['shipping_method_id'];
                    $goods_electric = $yt_cur_logistics['goods_electric'];
                }
            }
        }*/

        $has_real = $logistics['has_real'];
        if($order['source'] == Base::PLATFORM_OZON) {
            $has_real = false;
            $mandatory_update = false;
        }
        if (!empty($shipping_method_id) && $goods_electric != Base::ELECTRIC_SENSITIVE) {
            $order_recommended = OrderRecommended::find()->where(['order_id' => $order_id])->one();
            $data = [
                'logistics_channels_id' => $shipping_method_id,
                'freight_price' => $min_offers,
            ];
            if (empty($order_recommended)) {
                $data['order_id'] = $order_id;
                OrderRecommended::add($data);
            } else {
                $order_recommended->load($data, '');
                $order_recommended->save();
            }

            $up_order = false;
            if ($order['freight_price'] <= 0) {
                $order['freight_price'] = $min_offers + $freight_price;
                $order['order_profit'] = self::calculateProfitPrice($order);
                $up_order = true;
            }

            if ($mandatory_update || ($has_real && empty($order['logistics_channels_id']))) {
                $order->logistics_channels_id = $shipping_method_id;
                $up_order = true;
            }

            if($up_order) {
                $order->save();
            }
            return false;
        }
        return true;
    }

    /**
     * @param $shipp_offer
     * @param $weight
     * @param $other_price
     * @return float|int
     */
    public static function getOffers($shipp_offer, $weight, $other_price)
    {
        if($shipp_offer['transport_code'] == 'sys') {
            $weight = ceil($weight * 10) / 10;
        }
        if($weight > 5 && in_array($shipp_offer['shipping_method_id'],[2010,2011,2014,2015])) { //超过5kg按1kg进位
            $weight = ceil($weight);
        }
        if($weight < 1 && in_array($shipp_offer['shipping_method_id'],[2012,2013])) { //抛货最低1kg
            $weight = 1;
        }
        if ($weight > $shipp_offer['start_weight'] && $weight <= $shipp_offer['end_weight']) {
            if (in_array($shipp_offer['shipping_method_code'], [2261, 3661, 3681, 6341, 6161])) {//固定价格
                $offers = $shipp_offer['weight_price'] + $shipp_offer['deal_price'];
            } else {
                $offers = ($shipp_offer['weight_price'] * $weight) + $shipp_offer['deal_price'];
            }
            //货币处理
            if(!empty($shipp_offer['currency']) && $shipp_offer['currency'] != 'CNY') {
                $offers = round($offers
                    * ExchangeRateService::getRealConversion($shipp_offer['currency'],'CNY'), 2);
            }
            $offers += $other_price;
            if ($shipp_offer['transport_code'] == 'yanwen') {//燕文打9.8折
                $offers = $offers * 0.98;
            }
            return $offers;
        }
        return false;
    }

}