<?php
namespace console\controllers\api;

use common\components\CommonUtil;
use common\components\HelperStamp;
use common\components\statics\Base;
use common\models\goods\GoodsChild;
use common\models\GoodsEvent;
use common\models\GoodsShop;
use common\models\Order;
use common\models\OrderEvent;
use common\models\OrderGoods;
use common\models\OrderStockOccupy;
use common\models\Shop;
use common\models\warehousing\WarehouseProvider;
use common\services\api\OrderEventService;
use common\services\api\RealService;
use common\services\FApiService;
use common\services\FTransportService;
use common\services\order\OrderService;
use common\services\purchase\PurchaseProposalService;
use common\services\sys\CountryService;
use common\services\sys\ExchangeRateService;
use common\services\transport\TransportService;
use common\services\warehousing\WarehouseService;
use yii\console\Controller;
use Exception;
use yii\helpers\ArrayHelper;

class OrderController extends Controller
{

    public function actionTest($shop_id)
    {
        /*$str = file_get_contents('/work/www/yshop/json/MP_ITEM_SPEC_4.6.json');
        $result = json_decode($str,true);
        foreach ($result['properties']['MPItem']['items']['properties']['Visible']['properties'] as $k=>$v) {
            echo $k."\n";
        }
        //var_dump($result);
        //echo CommonUtil::jsonFormat($result);
        exit;*/
        $shop = Shop::find()->where(['id'=>$shop_id])->asArray()->one();
        $api_service = FApiService::factory($shop);
        $result = $api_service->getProducts();
        echo CommonUtil::jsonFormat($result);
        exit;
        $order_event_lists = OrderEvent::find()->where(['id' => '31819'])->limit(50)->all();
        foreach ($order_event_lists as $v) {
            $v->status = OrderEvent::STATUS_RUNNING;
            $v->save();

            $order = Order::find()->where(['order_id' => $v['order_id']])->asArray()->one();

            $shop = Shop::findOne(['id' => $order['shop_id']]);
            if (empty($shop['client_key'])) {
                $v->status = GoodsEvent::STATUS_FAILURE;
                $v->error_msg = 'client_key为空';
                $v->save();
                continue;
            }

            try {
                $api_service = FApiService::factory($shop);
            } catch (\Exception $e) {
                CommonUtil::logs('order_event error: shop_id:' . $shop['id'] . ' platform_type:' . $shop['platform_type'] . ' ' . $e->getMessage(), 'order_api_error');
                continue;
            }

            $tracking_number = $order['track_no'];
            $arrival_time = $order['arrival_time'];
            $tracking_url = null;
            $carrier_code = '';
            if (empty(Order::$logistics_channels_map[$order['logistics_channels_id']])) {//自发货
                $shipping_method = TransportService::getShippingMethodInfo($order['logistics_channels_id']);
                if (!empty($shipping_method)) {
                    //$carrier_code = ucfirst($shipping_method['transport_code']);
                    $transport = TransportService::getTransportInfo($shipping_method['transport_code']);
                    //获取平台发货渠道
                    $carrier_code = FTransportService::factory($shipping_method['transport_code'])->getPlatformTransportCode($shop['platform_type'],$shipping_method['shipping_method_code']);
                    $tracking_url = $transport['track_url'];
                }
            } else {
                $carrier_code = Order::$logistics_channels_map[$order['logistics_channels_id']];
            }

            if ($shop['platform_type'] == Base::PLATFORM_REAL_DE) {
                $order_info = $api_service->getOrderInfo($order['relation_no']);
                if (empty($order_info)) {
                    $v->status = OrderEvent::STATUS_FAILURE;
                    $v->error_msg = '平台查不到该信息';
                    $v->save();
                    continue;
                }
                $order_info = $order_info->toArray();
                $result = false;
                foreach ($order_info['seller_units'] as $units) {
                    if ($units['status'] == 'cancelled') {
                        continue;
                    }
                    $result = $api_service->getOrderSend($units['id_order_unit'], $carrier_code, $tracking_number, $arrival_time, $tracking_url);
                }
            } else {
                $result = $api_service->getOrderSend($order['relation_no'], $carrier_code, $tracking_number, $arrival_time, $tracking_url);
            }



        }
    }

    /**
     * @param $shop_id
     * @param $order_id
     * @return void
     * @throws \yii\base\Exception
     */
    public function actionGetOne($shop_id,$order_id)
    {
        $shop = Shop::find()->where(['id'=>$shop_id])->asArray()->one();
        $api_service = FApiService::factory($shop);
        $result = $api_service->getOrderInfo($order_id);
        echo CommonUtil::jsonFormat($result);
        exit;
    }

    public function actionLists($shop_id = null)
    {
        $where = [
           // 'platform_type' => [Base::PLATFORM_REAL_DE,Base::PLATFORM_FRUUGO,Base::PLATFORM_ONBUY,Base::PLATFORM_ALLEGRO,Base::PLATFORM_OZON,Base::PLATFORM_COUPANG,Base::PLATFORM_MERCADO,Base::PLATFORM_JDID,Base::PLATFORM_FYNDIQ,Base::PLATFORM_EPRICE,Base::PLATFORM_CDISCOUNT,Base::PLATFORM_LINIO,Base::PLATFORM_HEPSIGLOBAL,Base::PLATFORM_NOCNOC,Base::PLATFORM_RDC,Base::PLATFORM_WALMART,Base::PLATFORM_JUMIA,Base::PLATFORM_MICROSOFT,Base::PLATFORM_TIKTOK,Base::PLATFORM_WORTEN],
        ];
        if(!empty($shop_id)){
            $where['id'] = $shop_id;
        }
        //订单权限
        $where['api_assignment'] = (new HelperStamp(Shop::$api_assignment_maps))->getStamps(Shop::ORDER_ASSIGNMENT);
        $shop = Shop::find()->where($where)->orderBy('add_order_exe_time asc')->all();
        foreach ($shop as $shop_v) {
            echo $shop_v['id']." ".$shop_v['name']."\n";
            if(in_array($shop_v['id'],[28]) || $shop_v['platform_type'] == Base::PLATFORM_REAL_DE){
                continue;
            }

            if(empty($shop_v['client_key']) || empty($shop_v['secret_key'])){
                continue;
            }

            $add_order_exe_time = empty($shop_v['add_order_exe_time'])?strtotime(date('2020-11-28 00:00:00')):$shop_v['add_order_exe_time'];
            $add_order_exe_time = $add_order_exe_time - 60 * 10;
            try {
                $api_service = FApiService::factory($shop_v);
            }catch (\Exception $e){
                CommonUtil::logs('add_order error: shop_id:' . $shop_v['id'] . ' platform_type:' . $shop_v['platform_type'] .' '. $e->getMessage().' '.$e->getFile().$e->getLine(), 'order_api_error');
                continue;
            }

            if (empty($api_service)) {
                continue;
            }

            $limit = 50;
            $end_order_exe_time = $add_order_exe_time;
            $old_end_order_exe_time = 0;
            while (true) {
                if($old_end_order_exe_time == $end_order_exe_time){
                    break;
                }
                $old_end_order_exe_time = $end_order_exe_time;
                
                $order_lists = [];
                try {
                    $order_lists = $api_service->getOrderLists(date('Y-m-d H:i:s',$end_order_exe_time));
                } catch (\Exception $e) {
                    CommonUtil::logs('add_order shop error: shop_id:' . $shop_v['id'] . ' platform_type:' . $shop_v['platform_type'] . ' exec_time:' . $end_order_exe_time .' '. $e->getMessage().' '.$e->getFile().$e->getLine(), 'order_api_error');
                    break;
                }
                if(empty($order_lists)) {
                    break;
                }
                $count = 0;
                foreach ($order_lists as $order) {
                    try {
                        $count++;
                        $result = $api_service->dealOrder($order);
                        if (empty($result)) {
                            continue;
                        }

                        $add_time = $result['order']['add_time'];
                        if ($end_order_exe_time < $add_time) {
                            $end_order_exe_time = $add_time;
                        }

                        if($shop_v['platform_type'] != Base::PLATFORM_WILDBERRIES) {
                            $order_model = Order::find()->where(['source' => $shop_v['platform_type'], 'relation_no' => $result['order']['relation_no']])->one();
                            if (!empty($order_model)) {
                                continue;
                            }
                        }

                        $order_id = (new OrderService())->addOrder($result['order'], $result['goods']);
                        echo $order_id . "\n";

                        //确认订单
                        if(in_array($shop_v['platform_type'],[Base::PLATFORM_FRUUGO,Base::PLATFORM_COUPANG,Base::PLATFORM_WALMART])) {
                            $api_service->getConfirmOrder($result['order']['relation_no']);
                        }
                    } catch (\Exception $e) {
                        CommonUtil::logs('add_order error: shop_id:' . $shop_v['id'] . ' platform_type:' . $shop_v['platform_type'] .' exec_time:'. $end_order_exe_time .' '. $e->getMessage() .' '.$e->getFile().$e->getLine(), 'order_api_error');
                    }
                }

                if($count < $limit || $shop_v['platform_type'] == Base::PLATFORM_FRUUGO){
                    break;
                }
            }

            if($end_order_exe_time < time() - 15 * 24 * 60 *60) {
                $end_order_exe_time = time() - 15 * 24 * 60 * 60;
            }
            $shop_v->add_order_exe_time = $end_order_exe_time;
            $shop_v->save();
        }
        echo date('Y-m-d H:i:s')."添加订单执行成功\n";
    }

    /**
     * 添加一个订单
     * @param $shop_id
     * @param $order_id
     * @return void
     * @throws \yii\base\Exception
     */
    public function actionAddOne($shop_id,$order_id)
    {
        $shop = Shop::find()->where(['id'=>$shop_id])->asArray()->one();
        $api_service = FApiService::factory($shop);
        $order = $api_service->getOrderInfo($order_id);
        $result = $api_service->dealOrder($order);
        $order_model = Order::find()->where(['source'=> $shop['platform_type'],'relation_no'=>$result['order']['relation_no']])->one();
        if(!empty($order_model)){
            return ;
        }
        $order_id = (new OrderService())->addOrder($result['order'], $result['goods']);
        //确认订单
        if(in_array($shop['platform_type'],[Base::PLATFORM_FRUUGO,Base::PLATFORM_COUPANG,Base::PLATFORM_WALMART])) {
            $api_service->getConfirmOrder($result['order']['relation_no']);
        }
        echo $shop_id.','.$order_id . "\n";
        exit;
    }

    /**
     * 取消订单状态
     */
    public function actionCancelOrder($shop_id = null)
    {
        $where = [
            'platform_type' => [Base::PLATFORM_HEPSIGLOBAL,Base::PLATFORM_JDID,Base::PLATFORM_MERCADO,Base::PLATFORM_LINIO,Base::PLATFORM_JUMIA,Base::PLATFORM_OZON,Base::PLATFORM_NOCNOC,Base::PLATFORM_WILDBERRIES]
        ];
        if(!empty($shop_id)){
            $where['id'] = $shop_id;
        }
        //订单权限
        $where['api_assignment'] = (new HelperStamp(Shop::$api_assignment_maps))->getStamps(Shop::ORDER_ASSIGNMENT);
        $shop = Shop::find()->where($where)->orderBy('update_order_exe_time asc')->all();
        foreach ($shop as $shop_v) {
            echo $shop_v['id']." ".$shop_v['name']."\n";
            if (empty($shop_v['client_key']) || empty($shop_v['secret_key'])) {
                continue;
            }
            $end_order_exe_time = time();
            $update_order_exe_time = empty($shop_v['update_order_exe_time']) ? strtotime(date('2020-11-28 00:00:00')) : $shop_v['update_order_exe_time'];
            $update_order_exe_time = $update_order_exe_time - 60;
            $update_time = $update_order_exe_time;
            try {
                $api_service = FApiService::factory($shop_v);
            } catch (\Exception $e) {
                CommonUtil::logs('add_order error: shop_id:' . $shop_v['id'] . ' platform_type:' . $shop_v['platform_type'] . ' ' . $e->getMessage() . ' ' . $e->getFile() . $e->getLine(), 'order_api_error');
                continue;
            }

            if (empty($api_service)) {
                continue;
            }

            try {
                $order_lists = $api_service->getCancelOrderLists($update_time);
                foreach ($order_lists as $order) {
                    if (!empty($order['cancel_goods'])) {//只取消商品先不处理
                        continue;
                    }

                    if($shop_v['platform_type'] == Base::PLATFORM_WILDBERRIES) {
                        $order_model = Order::find()->where(['source' => $shop_v['platform_type'], 'delivery_order_id' => $order['delivery_order_id']])->one();
                    } else {
                        $order_model = Order::find()->where(['source' => $shop_v['platform_type'], 'relation_no' => $order['relation_no']])->one();
                    }
                    if (empty($order_model)) {
                        continue;
                    }

                    //取消状态的只处理未确认情况
                    if (in_array($order_model['order_status'], [Order::ORDER_STATUS_UNCONFIRMED, Order::ORDER_STATUS_WAIT_PURCHASE, Order::ORDER_STATUS_APPLY_WAYBILL, Order::ORDER_STATUS_WAIT_PRINTED_OUT_STOCK, Order::ORDER_STATUS_WAIT_PRINTED, Order::ORDER_STATUS_WAIT_SHIP])) {
                        (new OrderService())->cancel($order_model['order_id'], 9, '系统自动取消');
                        echo '取消订单' . $order_model['order_id'] . ' ' . $order_model['order_status'] . "\n";
                    }
                }

                $shop_v->update_order_exe_time = $end_order_exe_time;
                $shop_v->save();
            } catch (\Exception $e) {
                echo $shop_v['id']." ".$shop_v['name']. 'error ' .$e->getMessage() ."\n";
            }
        }
        echo date('Y-m-d H:i:s') . "更新订单执行成功\n";
    }

    /**
     * 修复剩余发货时间
     * @param $platform_type
     * @return void
     */
    public function actionFixRemainingTime($platform_type)
    {
        $where = ['order_status' => Order::$order_remaining_maps,'remaining_shipping_time'=>0];
        if (!empty($platform_type)) {
            $where['source'] = $platform_type;
        }
        $order_lists = Order::find()->where($where)->all();
        foreach ($order_lists as $order) {
            try {
                $shop_id = $order['shop_id'];
                $shop = Shop::find()->where(['id' => $shop_id])->asArray()->one();
                $api_service = FApiService::factory($shop);
                $result = $api_service->getOrderInfo($order['relation_no']);
                if(empty($result) ) {
                    continue;
                }

                $remaining_shipping_time = 0;
                if($platform_type == Base::PLATFORM_HEPSIGLOBAL) {
                    foreach ($result['order_items'] as $v) {
                        //剩余发货时间
                        if (empty($remaining_shipping_time)) {
                            $remaining_shipping_time = strtotime($v['due_date']) - 8 * 60 * 60;
                        } else {
                            $remaining_shipping_time = min($remaining_shipping_time, strtotime($v['due_date']) - 8 * 60 * 60);
                        }
                    }
                }

                if($platform_type == Base::PLATFORM_OZON) {
                    $remaining_shipping_time = strtotime($result['shipment_date']);
                }
                $order->remaining_shipping_time = $remaining_shipping_time;
                $order->save();
                echo $order['order_id']."\n";
            } catch (\Exception $e) {
                echo $order['order_id'].$e->getMessage()."\n";
            }
        }
    }

    /**
     * 检测订单状态
     * @param $shop_id
     * @return void
     * @throws \Throwable
     * @throws \yii\base\Exception
     */
    public function actionOrderStatus($shop_id = null)
    {
        $where = ['order_status'=> [Order::ORDER_STATUS_UNCONFIRMED, Order::ORDER_STATUS_WAIT_PURCHASE, Order::ORDER_STATUS_APPLY_WAYBILL, Order::ORDER_STATUS_WAIT_PRINTED_OUT_STOCK, Order::ORDER_STATUS_WAIT_PRINTED, Order::ORDER_STATUS_WAIT_SHIP],
            'source' => Base::PLATFORM_HEPSIGLOBAL
        ];
        if(!empty($shop_id)){
            $where['shop_id'] = $shop_id;
        }
        $order = Order::find()->where($where)->all();
        foreach ($order as $order_v) {
            try {
                echo $order_v['order_id']. "\n";
                $shop = Shop::find()->where(['id'=>$order_v['shop_id']])->one();
                $api_service = FApiService::factory($shop);
                $result_order = $api_service->getOrderInfo($order_v['relation_no']);
                $result = $api_service->dealCancelOrder($result_order);
                if (!empty($result) && $result['relation_no'] == $order_v['relation_no']) {//只取消商品先不处理
                    if (empty($result['cancel_goods'])) {
                        (new OrderService())->cancel($order_v['order_id'], 9, '系统自动取消');
                        echo '取消订单' . $order_v['order_id'] . ' ' . $order_v['order_status'] . "\n";
                    } else {
                        $order_goods = OrderGoods::find()->where(['order_id'=>$order_v['order_id']])->all();
                        OrderStockOccupy::deleteAll(['order_id'=>(string)$order_v['order_id']]);
                        foreach ($result['cancel_goods'] as $cancel_good_v) {
                            foreach ($order_goods as $order_goods_v) {
                                $cgoods_no = GoodsShop::find()->where(['platform_sku_no' => $cancel_good_v['sku_no'], 'shop_id' => $order_v['shop_id']])->select('cgoods_no')->scalar();
                                if (empty($cgoods_no)) {
                                    $cgoods_no = GoodsChild::find()->where(['cgoods_no' => $cancel_good_v['sku_no']])->select('cgoods_no')->scalar();
                                }
                                if (empty($cgoods_no)) {
                                    break;
                                }
                                if ($cgoods_no == $order_goods_v['cgoods_no']) {
                                    if ($order_goods_v['goods_num'] == $cancel_good_v['num']) {
                                        $order_goods_v->delete();
                                    } else {
                                        $order_goods_v->goods_num = $order_goods_v['goods_num'] - $cancel_good_v['num'];
                                        $order_goods_v->save();
                                    }
                                    (new PurchaseProposalService())->updatePurchaseProposal($order_v['warehouse'], $order_goods_v['platform_asin']);
                                    echo '取消订单商品' . $order_v['order_id'] . ' ' . $cancel_good_v['sku_no'] . ' ' . $order_v['order_status'] . "\n";
                                }
                            }
                        }
                        (new PurchaseProposalService())->updatePurchaseProposalToOrderId((string)$order_v['order_id']);
                    }
                }
            } catch (\Exception $e) {
                echo $order_v['order_id']. 'error ' .$e->getMessage() ."\n";
            }
        }
    }

    /**
     * 更新real订单状态
     */
    public function actionUpdateRealOrderStatus()
    {
        $limit = 50;
        $shop = Shop::find()->where([
            'platform_type' => Base::PLATFORM_REAL_DE,
            'status' => Shop::STATUS_VALID
        ])->andWhere(['<>', 'client_key', ''])->orderBy('update_order_exe_time asc')->all();
        foreach ($shop as $shop_v) {
            if(empty($shop_v['client_key']) || empty($shop_v['secret_key'])){
                continue;
            }
            date_default_timezone_set('Europe/Berlin');//德国时区
            $end_order_exe_time = time();
            $update_order_exe_time = empty($shop_v['update_order_exe_time'])?strtotime(date('2020-11-28 00:00:00')):$shop_v['update_order_exe_time'];
            $update_order_exe_time = $update_order_exe_time - 60;
            $update_time = date('Y-m-d H:i:s',$update_order_exe_time);
            date_default_timezone_set('Asia/Shanghai');

            $real_service = new RealService($shop_v);
            $offset = 0;

            while (true) {
                $order_lists = $real_service->getOrderLists(null, $update_time , $limit, $offset);
                if(empty($order_lists)) {
                    break;
                }

                $count = 0;
                foreach ($order_lists as $order) {
                    if(empty($order)){
                        continue;
                    }
                    $order = $order->toArray();
                    $count ++;
                    /*
                     array(4) {
                          ["seller_units_count"]=>
                          int(1)
                          ["ts_units_updated"]=>
                          string(19) "2020-11-28 08:20:06"
                          ["id_order"]=>
                          string(7) "M8YZ6X4"
                          ["ts_created"]=>
                          string(19) "2020-11-28 08:20:06"
                        }
                     */
                    $relation_no = $order['id_order'];
                    $order_info = $real_service->getOrderInfo($relation_no);
                    if(empty($order_info)){
                        continue;
                    }
                    $order_info = $order_info->toArray();
                    $status = $order_info['seller_units'][0]['status'];
                    if($status == 'cancelled') {//取消状态
                        $order_model = Order::find()->where(['relation_no'=>$relation_no])->one();
                        if(empty($order_model)){
                            continue;
                        }

                        //取消状态的只处理未确认情况
                        if($order_model['order_status'] == Order::ORDER_STATUS_UNCONFIRMED) {
                            (new OrderService())->cancel($order_model['order_id']);
                            echo '取消订单'.$order_model['order_id'] ."\n";
                        }
                    }
                }

                $offset += $limit;
                if($count < $limit){
                    break;
                }
            }

            $shop_v->update_order_exe_time = $end_order_exe_time;
            $shop_v->save();
        }
        echo date('Y-m-d H:i:s')."更新real订单执行成功\n";
    }

    /**
     * 更新订单运费
     */
    public function actionUpdateFreightPrice()
    {
        $shop = Shop::find()->where(['platform_type' => Base::PLATFORM_MERCADO])->andWhere(['<>', 'client_key', ''])->indexBy('id')->all();
        $order = Order::find()
            ->where(['source' => [Base::PLATFORM_MERCADO],
                'settlement_status' => [0,2],
                'create_way' => Order::CREATE_WAY_SYSTEM,])
            ->andWhere(['>', 'add_time', time() - 2 * 30 * 24 * 60 * 60])
            ->andWhere(['<', 'add_time', time() - 3 * 24 * 60 * 60])
            ->all();
        foreach ($order as $v) {
            try {
                $shop_v = $shop[$v['shop_id']];
                $api_service = FApiService::factory($shop_v);
                $info = $api_service->getOrderInfo($v['relation_no']);
                if (empty($info['shipping']) || empty($info['shipping']['id'])) {
                    continue;
                }
                $platform_fee = $v['platform_fee'];
                if($platform_fee <=0){
                    $platform_fee = 0;
                    foreach ($info['order_items'] as $info_v) {
                        $platform_fee += $info_v['sale_fee'];
                    }
                    $v['platform_fee'] = $platform_fee;
                }
                $shipping_id = $info['shipping']['id'];
                $shopp_info = $api_service->getShipping($shipping_id);
                if (empty($shopp_info['lead_time']) || empty($shopp_info['lead_time']['list_cost']) || $shopp_info['lead_time']['list_cost'] <= 0) {
                    continue;
                }
                $v['freight_price'] = CountryService::getConvertRMB($shopp_info['lead_time']['list_cost'], $shopp_info['lead_time']['currency_id']);
                $v['order_profit'] = OrderService::calculateProfitPrice($v);
                $v['settlement_status'] = HelperStamp::addStamp($v['settlement_status'], Order::SETTLEMENT_STATUS_COST);
                $v->save();
            } catch (\Exception $e) {
                continue;
            }
            echo $v['order_id'] . ' ' . $v['freight_price'] . "\n";
        }
        echo date('Y-m-d H:i:s') . "更新订单运费执行成功\n";
    }

    /**
     * 订单事件
     */
    public function actionOrderEvent($limit = 1)
    {
        $order_event_lists = OrderEvent::find()->where(['status'=>OrderEvent::STATUS_WAIT_RUN])
            ->andWhere(['<','plan_time',time()])->offset(200*($limit-1))->limit(200)->all();
        foreach ($order_event_lists as $v){
            $v->status = OrderEvent::STATUS_RUNNING;
            $v->save();

            $order = Order::find()->where(['order_id'=>$v['order_id']])->asArray()->one();

            $shop = Shop::findOne(['id'=>$order['shop_id']]);
            if (empty($shop['client_key'])){
                $v->status = GoodsEvent::STATUS_FAILURE;
                $v->error_msg = 'client_key为空';
                $v->save();
                continue;
            }

            if($shop['status'] != Shop::STATUS_VALID){
                $v->status = GoodsEvent::STATUS_FAILURE;
                $v->error_msg = '店铺状态被禁用';
                $v->save();
                continue;
            }

            echo date('Y-m-d H:i:s').' '. $shop['id'].' '.$v['order_id'].' '.$shop['platform_type'] ."\n";

            try {
                $api_service = FApiService::factory($shop);
            }catch (\Exception $e){
                CommonUtil::logs('order_event error: shop_id:' . $shop['id'] . ' platform_type:' . $shop['platform_type'] .' '. $e->getMessage(), 'order_api_error');
                continue;
            }
            //$real_service = new RealService($shop['client_key'], $shop['secret_key']);
            switch ($v['event_type']){
                case OrderEvent::EVENT_TYPE_SHIPPING://发货
                    //if($order['delivery_status'] == Order::DELIVERY_SHIPPED) {
                        try {
                            $tracking_number = $order['track_no'];
                            $arrival_time = $order['arrival_time'];
                            $tracking_url = null;
                            $carrier_code = '';
                            if(empty(Order::$logistics_channels_map[$order['logistics_channels_id']])) {//自发货
                                $shipping_method = TransportService::getShippingMethodInfo($order['logistics_channels_id']);
                                if(!empty($shipping_method)) {
                                    //$carrier_code = ucfirst($shipping_method['transport_code']);
                                    $transport = TransportService::getTransportInfo($shipping_method['transport_code']);
                                    //获取平台发货渠道
                                    $carrier_code = FTransportService::factory($shipping_method['transport_code'])->getPlatformTransportCode($shop['platform_type'],$shipping_method['shipping_method_code'],$order['order_id']);
                                    $tracking_url = $transport['track_url'];
                                    if ($shipping_method['transport_code'] == 'yanwen') {//燕文
                                        $tmp_sub = substr($tracking_number, 0, 2);
                                        $tmp_l_sub = substr($tracking_number, -2, 2);
                                        if (in_array($tmp_sub, ['EV', 'RV', 'LV'])) {
                                            $tracking_url = 'https://www.ems.com.cn/english/';
                                        } else if (in_array($tmp_l_sub, ['NL'])) {
                                            $tracking_url = 'https://postnl.post';
                                        }
                                    } else if ($shipping_method['transport_code'] == 'yuntu') {//云途
                                        $tracking_url = 'https://www.yuntrack.com/parcelTracking?id=' . $tracking_number;
                                    } else if ($shipping_method['transport_code'] == 'huahan') {//华翰
                                        $tracking_url = 'https://www.hh-exp.cn/WebTrack/CargoTrack.html';
                                        $tracking_number = $order['track_logistics_no'];
                                    }
                                }
                            }else{
                                $carrier_code = Order::$logistics_channels_map[$order['logistics_channels_id']];
                            }

                            if($shop['platform_type'] == Base::PLATFORM_REAL_DE) {
                                $order_info = $api_service->getOrderInfo($order['relation_no']);
                                if (empty($order_info)) {
                                    $v->status = OrderEvent::STATUS_FAILURE;
                                    $v->error_msg = '平台查不到该信息';
                                    $v->save();
                                    continue;
                                }
                                $order_info = $order_info->toArray();
                                $result = false;
                                foreach ($order_info['seller_units'] as $units) {
                                    if ($units['status'] == 'cancelled') {
                                        continue;
                                    }
                                    $result = $api_service->getOrderSend($units['id_order_unit'], $carrier_code, $tracking_number, $arrival_time, $tracking_url);
                                }
                            } else {
                                $result = $api_service->getOrderSend($order['relation_no'], $carrier_code, $tracking_number, $arrival_time, $tracking_url);
                            }

                            if ($result) {
                                $v->status = OrderEvent::STATUS_SUCCESS;
                                $v->save();
                                //real需要发票
                                /*if($shop['platform_type'] == Base::PLATFORM_REAL_DE) {
                                    OrderEventService::addEvent($v['platform'], $v['shop_id'], $v['order_id'], OrderEvent::EVENT_TYPE_POST_INVOICE);
                                }*/
                            } else {
                                $v->status = OrderEvent::STATUS_FAILURE;
                                $v->error_msg = '发货失败';
                                $v->save();
                            }
                        } catch (Exception $e) {
                            $v->status = OrderEvent::STATUS_FAILURE;
                            $v->error_msg = '发货失败'.$e->getMessage();
                            $v->save();
                        }
                    //}
                    break;
                case OrderEvent::EVENT_TYPE_POST_INVOICE:
                    if($shop['platform_type'] != Base::PLATFORM_REAL_DE) {
                        break;
                    }
                    $pdf = (new OrderService())->invoice($v['order_id']);
                    $name = 'Rechnungen'.$order['relation_no'].'.pdf';
                    $content =  base64_encode($pdf->Output($name, 'S'));
                    $result = $api_service->postInvoice($order['relation_no'], $name, $content);
                    if ($result) {
                        $v->status = OrderEvent::STATUS_SUCCESS;
                        $v->save();
                    } else {
                        $v->status = OrderEvent::STATUS_FAILURE;
                        $v->error_msg = '上传发票失败';
                        $v->save();
                    }
                    break;
                case OrderEvent::EVENT_TYPE_TRACKING_NUMBER:
                    if(in_array($shop['platform_type'] ,[Base::PLATFORM_OZON,Base::PLATFORM_EPRICE, Base::PLATFORM_RDC,Base::PLATFORM_LINIO,Base::PLATFORM_JUMIA,Base::PLATFORM_HEPSIGLOBAL,Base::PLATFORM_WORTEN])) {
                        try {
                            $tracking_number = $order['track_no'];
                            $tracking_url = null;
                            $carrier_code = '';
                            if(!empty($order['logistics_channels_id']) && $order['integrated_logistics'] != Order::INTEGRATED_LOGISTICS_YES) {
                                if (empty(Order::$logistics_channels_map[$order['logistics_channels_id']])) {//自发货
                                    $shipping_method = TransportService::getShippingMethodInfo($order['logistics_channels_id']);
                                    if (!empty($shipping_method)) {
                                        //$carrier_code = ucfirst($shipping_method['transport_code']);
                                        $transport = TransportService::getTransportInfo($shipping_method['transport_code']);
                                        //获取平台发货渠道
                                        $carrier_code = FTransportService::factory($shipping_method['transport_code'])->getPlatformTransportCode($shop['platform_type'],$shipping_method['shipping_method_code'],$order['order_id']);
                                        $tracking_url = $transport['track_url'];
                                    }
                                } else {
                                    $carrier_code = Order::$logistics_channels_map[$order['logistics_channels_id']];
                                }
                            }
                            if($shop['platform_type'] == Base::PLATFORM_OZON) {
                                try {
                                    $result = $api_service->setStatusShip($order['relation_no']);
                                } catch (\Exception $e) {

                                }
                            }
                            $result = $api_service->setTrackingNumber($order['relation_no'],$tracking_number,$carrier_code,$tracking_url);
                            if ($result) {
                                $v->status = OrderEvent::STATUS_SUCCESS;
                                $v->save();
                            } else {
                                $v->status = OrderEvent::STATUS_FAILURE;
                                $v->error_msg = '输入物流跟踪号失败';
                                $v->save();
                            }
                        } catch (Exception $e) {
                            $v->status = OrderEvent::STATUS_FAILURE;
                            $v->error_msg = '输入物流跟踪号失败'.$e->getMessage();
                            $v->save();
                        }

                    }
                    break;
                case OrderEvent::EVENT_TYPE_ON_WAY://派送中
                    if($shop['platform_type'] == Base::PLATFORM_OZON) {
                        try {
                            $result = $api_service->setStatusLastMile($order['relation_no']);
                            if($result == -1) {
                                $v->status = OrderEvent::STATUS_WAIT_RUN;
                                $v->plan_time = time() + 24 * 60 * 60;
                                $v->error_msg = '';
                                $v->save();
                                break;
                            } else if ($result == 1) {
                                $v->status = OrderEvent::STATUS_SUCCESS;
                                $v->save();
                            } else {
                                $v->status = OrderEvent::STATUS_FAILURE;
                                $v->error_msg = '更改为最后一公里状态失败';
                                $v->save();
                            }
                        } catch (Exception $e) {
                            $v->status = OrderEvent::STATUS_FAILURE;
                            if(strpos($e->getMessage(), 'Company is blocked, please contact support') !== false){
                                $v->error_msg = '店铺被关,更改为最后一公里状态失败';
                            } else {
                                $v->error_msg = '更改为最后一公里状态失败' . $e->getMessage();
                            }
                            $v->save();
                        }
                    }
                    break;
                case OrderEvent::EVENT_TYPE_DELIVERED://已到货
                    if(in_array($shop['platform_type'],[Base::PLATFORM_OZON,Base::PLATFORM_B2W])) {
                        try {
                            $result = $api_service->setStatusDelivered($order['relation_no']);
                            if($result == -1 || $result == -2) {
                                $v->status = OrderEvent::STATUS_WAIT_RUN;
                                $v->plan_time = time() + 24 * 60 * 60;
                                $v->error_msg = '';
                                $v->save();
                                if($result == -2) {
                                    OrderEventService::addEvent($order['source'], $order['shop_id'], $order['order_id'], OrderEvent::EVENT_TYPE_ON_WAY);
                                }
                            } else if ($result == 1) {
                                $v->status = OrderEvent::STATUS_SUCCESS;
                                $v->save();
                            } else {
                                $v->status = OrderEvent::STATUS_FAILURE;
                                $v->error_msg = '更改为已到货状态失败';
                                $v->save();
                            }
                        } catch (Exception $e) {
                            $v->status = OrderEvent::STATUS_FAILURE;
                            if(strpos($e->getMessage(), 'Company is blocked, please contact support') !== false){
                                $v->error_msg = '店铺被关,更改为已到货状态失败';
                            } else {
                                $v->error_msg = '更改为已到货状态失败'.$e->getMessage();
                            }
                            $v->save();
                        }
                    }
                    break;
                case OrderEvent::EVENT_TYPE_FIRST_LOGISTICS://已到货
                    if(in_array($shop['platform_type'],[Base::PLATFORM_NOCNOC,Base::PLATFORM_COUPANG])) {
                        try {
                            $result = $api_service->sendFirstLogistics($order['relation_no']);
                            if ($result == 1) {
                                $v->status = OrderEvent::STATUS_SUCCESS;
                                $v->save();
                            } else {
                                $v->status = OrderEvent::STATUS_FAILURE;
                                $v->error_msg = '发送国内物流失败';
                                $v->save();
                            }
                        } catch (Exception $e) {
                            $v->status = OrderEvent::STATUS_FAILURE;
                            $v->error_msg = '发送国内物流失败'.$e->getMessage();
                            $v->save();
                        }
                    }
                    break;
                case OrderEvent::EVENT_TYPE_CANCEL:
                    if($shop['platform_type'] != Base::PLATFORM_FRUUGO) {

                    }
                    break;
            }
        }
    }

    /**
     * 更新订单ean
     */
    public function actionUpdateEan($limit)
    {
        $shop = Shop::find()->where([
            'platform_type' => Base::PLATFORM_REAL_DE,
            'status' => Shop::STATUS_VALID
        ])->asArray()->all();
        $shop = ArrayHelper::index($shop,'id');

        $order = Order::find()->where(['in','ean',['',null]])->offset(($limit-1)*100)->limit(100)->all();
        foreach ($order as $item) {
            $relation_no = $item['relation_no'];
            $shop_v = $shop[$item['shop_id']];
            $real_service = new RealService($shop_v['client_key'],$shop_v['secret_key']);
            $order_info = $real_service->getOrderInfo($relation_no);
            if(empty($order_info)){
                continue;
            }
            $order_info = $order_info->toArray();

            $ean = $order_info['seller_units'][0]['item']['eans'][0];
            $item->ean = $ean;
            $item->save();
            echo $item['relation_no']."更新".$ean."\n";
        }
        echo "==============\n";
        $this->actionOrderEvent();
    }

    /**
     * 海外仓订单状态
     * @param $platform_type
     * @return void
     */
    public function actionOverseasOrderStatus($platform_type = null)
    {
        $warehouse_map = WarehouseService::getOverseasWarehouse();
        $warehouse_overseas_list = array_keys($warehouse_map);
        $or_status = Order::$order_remaining_maps;
        $or_status[] = Order::ORDER_STATUS_SHIPPED;
        $where = ['order_status' => $or_status,'warehouse'=>$warehouse_overseas_list];
        if (!empty($platform_type)) {
            $where['source'] = $platform_type;
        } else {
            $where['source'] = [Base::PLATFORM_OZON,Base::PLATFORM_ALLEGRO,Base::PLATFORM_EMAG,Base::PLATFORM_WILDBERRIES];
        }
        $order_lists = Order::find()->where($where)->all();
        foreach ($order_lists as $order) {
            try {
                $shop_id = $order['shop_id'];
                $shop = Shop::find()->where(['id' => $shop_id])->asArray()->one();
                if($order['source'] == Base::PLATFORM_WILDBERRIES){
                    $status = Order::ORDER_STATUS_WAIT_SHIP;
                    if($order['add_time'] < time() - 2 * 24 * 60 * 60) {
                        $status = Order::ORDER_STATUS_SHIPPED;
                    }
                } else {
                    $api_service = FApiService::factory($shop);
                    $result = $api_service->getOrderInfo($order['relation_no']);
                    if (empty($result)) {
                        continue;
                    }

                    $status = $api_service->getOrderStatus($result);
                    if ($status === false) {
                        continue;
                    }
                    //ozon取消和退款都是同一个状态 按订单已经发货来区分
                    if($status == Order::ORDER_STATUS_CANCELLED && $order['source'] == Base::PLATFORM_OZON) {
                        if ($order['order_status'] == Order::ORDER_STATUS_SHIPPED) {
                            $status = Order::ORDER_STATUS_REFUND;
                        }
                    }
                }

                //第三方海外仓不是完成的不处理
                $warehouse_type = WarehouseService::getWarehouseProviderType($order['warehouse']);
                if($warehouse_type == WarehouseProvider::TYPE_THIRD_PARTY && $status != Order::ORDER_STATUS_FINISH) {
                    continue;
                }

                if(in_array($order['source'] ,[Base::PLATFORM_ALLEGRO,Base::PLATFORM_WILDBERRIES]) && $order['order_status'] == Order::ORDER_STATUS_SHIPPED && $status == Order::ORDER_STATUS_SHIPPED) {
                    if($order['delivery_time'] < time() - 7 * 24 * 60 * 60) {
                        $status = Order::ORDER_STATUS_FINISH;
                    }
                }

                if($status == $order['order_status']) {
                    continue;
                }
                //取消订单
                if($status == Order::ORDER_STATUS_CANCELLED) {
                    (new OrderService())->cancel($order['order_id'], 9, '系统自动取消');
                    echo $order['order_id']."\n";
                    continue;
                }

                //退款
                if($status == Order::ORDER_STATUS_REFUND) {
                    (new OrderService())->refund($order['order_id'], 109, '系统自动退款');
                    echo $order['order_id']."\n";
                    continue;
                }


                if($order['order_status'] > $status) {
                    continue;
                }

                //已发货
                if($status == Order::ORDER_STATUS_SHIPPED || ($status == Order::ORDER_STATUS_FINISH && $order['order_status'] != Order::ORDER_STATUS_SHIPPED)) {
                    if ($order['order_status'] != Order::ORDER_STATUS_WAIT_SHIP) {
                        $order->order_status = Order::ORDER_STATUS_WAIT_SHIP;
                        $order->save();
                    }
                    (new OrderService())->ship($order['order_id'], 0, '', true);
                    echo $order['order_id'] . "\n";
                    continue;
                }

                $order->order_status = $status;
                $order->save();
                (new PurchaseProposalService())->updatePurchaseProposalToOrderId((string)$order['order_id']);
                echo $order['order_id']."\n";
            } catch (\Exception $e) {
                echo $order['order_id'].$e->getMessage()."\n";
            }
        }
        echo date('Y-m-d H:i:s')." 执行完成"."\n";
    }

}