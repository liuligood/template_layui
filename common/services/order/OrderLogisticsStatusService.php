<?php
namespace common\services\order;

use common\components\CommonUtil;
use common\components\HelperStamp;
use common\components\statics\Base;
use common\models\Order;
use common\models\OrderEvent;
use common\models\OrderLogisticsStatus;
use common\models\Shop;
use common\models\sys\ShippingMethod;
use common\services\api\OrderEventService;
use common\services\FApiService;
use common\services\FTransportService;
use common\services\goods\FGoodsService;
use common\services\sys\CountryService;
use common\services\transport\BaseTransportService;

class OrderLogisticsStatusService
{

    /**
     * 添加物流状态
     * @param $order_id
     * @param null $plan_time
     * @return bool
     * @throws \yii\base\Exception
     */
    public function addLogisticsStatus($order_id, $plan_time = null)
    {
        $order = Order::find()->where(['order_id' => $order_id])->asArray()->one();
        if(!in_array($order['source'] ,[Base::PLATFORM_OZON,Base::PLATFORM_LINIO])) {
            return true;
        }

        if($order['source'] == Base::PLATFORM_OZON && $order['integrated_logistics'] == Order::INTEGRATED_LOGISTICS_YES){
            return true;
        }

        if(OrderLogisticsStatus::findOne(['order_id'=>$order_id])){
            return true;
        }

        $data = [
            'order_id' => $order_id,
            'source' => $order['source'],
            'shop_id' => $order['shop_id'],
            'relation_no' => $order['relation_no'],
            'status' => OrderLogisticsStatus::STATUS_WAIT,
        ];
        $data['plan_time'] = $plan_time;
        if (is_null($plan_time)) {
            $data['plan_time'] = time() + 3 * 34 * 60 * 60;
        }
        return OrderLogisticsStatus::add($data);
    }

    /**
     *
     * @param $id
     * @return bool
     * @throws \yii\base\Exception
     */
    public function trackLogistics($id)
    {
        $order_logistics = OrderLogisticsStatus::findOne($id);
        if($order_logistics['source'] == Base::PLATFORM_OZON) {
            return $this->ozonTrackLogistics($order_logistics);
        }

        //linio
        $order_id = $order_logistics['order_id'];
        $order = Order::find()->where(['order_id' => $order_id])->one();
        if(empty($order['delivery_order_id']) || $order['order_status'] == Order::ORDER_STATUS_CANCELLED || (new HelperStamp(Order::$settlement_status_map))->isExistStamp($order['settlement_status'], Order::SETTLEMENT_STATUS_COST)) {
            $order_logistics['status'] = 3;
            $order_logistics->save();
            return true;
        }

        if ($order['source'] != Base::PLATFORM_LINIO || $order_logistics['status'] == OrderLogisticsStatus::STATUS_DELIVERED) {
            return true;
        }

        $shop = Shop::find()->where(['id'=>$order['shop_id']])->asArray()->one();
        $order_info = FApiService::factory($shop)->getOrderInfo($order['delivery_order_id']);

        $freight_price = 0;
        foreach ($order_info as $v) {
            if (!in_array($v['Status'],['returned','delivered','failed'])) {
                continue;
            }

            if(!empty($v['ShippingServiceCost'])) {
                $freight_price = $v['ShippingServiceCost'];
            }
        }

        if($freight_price > 0) {
            $country_code = $order['country'];
            $currency = $shop['currency'];
            $order_income_price = $order['order_income_price'];
            $platform_fee = FGoodsService::factory($order['source'])->setCountryCode($country_code)->platformFee($order_income_price,$order['shop_id']);
            $cost_profit = $order_income_price - $platform_fee - $freight_price;
            $cost_profit_usd = CountryService::getConvertRMB($cost_profit, $currency);
            $cost_profit_usd = $cost_profit_usd/6.5;
            $tax = 0;
            switch ($country_code) {
                case 'CL'://智利
                    if ($cost_profit_usd > 28) {
                        $tax = OrderService::calculateTax($cost_profit, 0.29);
                    }
                    break;
                case 'PE'://秘鲁
                    if ($cost_profit_usd > 200) {
                        $tax = OrderService::calculateTax($cost_profit, 0.22);
                    }
                    break;
                case 'CO'://哥伦比亚
                    $tax = OrderService::calculateTax($cost_profit, 0.29);
                    break;
                case 'MX'://墨西哥 暂不确定
                default:
                    if ($cost_profit_usd > 47) {
                        $tax = OrderService::calculateTax($cost_profit, 0.19);
                    }
            }
            $order['platform_fee'] = $platform_fee + $tax;
            $order['freight_price'] = CountryService::getConvertRMB($freight_price, $currency);
            $order_profit = OrderService::calculateProfitPrice($order);
            $order['order_profit'] = $order_profit;
            $order['settlement_status'] = HelperStamp::addStamp($v['settlement_status'], Order::SETTLEMENT_STATUS_COST);;
            $order->save();

            $order_logistics['status'] = OrderLogisticsStatus::STATUS_DELIVERED;
            $order_logistics->save();
        }else{
            if($order['add_time'] + 6*24*24*30 < time()) {//超过半年订单设为异常
                $order_logistics['status'] = 4;
                $order_logistics->save();
                return true;
            }
        }
        return true;
    }

    /**
     *
     * @param $order_logistics
     * @return bool
     * @throws \yii\base\Exception
     */
    public function ozonTrackLogistics($order_logistics)
    {
        $order_id = $order_logistics['order_id'];
        $order = Order::find()->where(['order_id' => $order_id])->asArray()->one();
        if($order['integrated_logistics'] == Order::INTEGRATED_LOGISTICS_YES) {
            $order_logistics['status'] = 3;
            $order_logistics->save();
            return true;
        }
        if ($order['source'] != Base::PLATFORM_OZON || $order_logistics['status'] == OrderLogisticsStatus::STATUS_DELIVERED) {
            return true;
        }

        $shipping_method = ShippingMethod::find()->where(['id' => $order['logistics_channels_id']])->asArray()->one();
        //获取跟踪信息
        $result = FTransportService::factory($shipping_method['transport_code'])->getTrackLogistics($order['track_no']);
        if ($result['error'] == BaseTransportService::RESULT_FAIL) {
            CommonUtil::logs($order_id . ' 获取跟踪信息失败', 'logistics_status_error');
            $order_logistics['error_status'] = OrderLogisticsStatus::ERROR_STATUS_YES;
            $order_logistics->save();
            return false;
        }
        $status = 0;
        //立德 物流状态
        if($shipping_method['transport_code'] == 'hualei') {
            $track_data = current($result['data']);
            if ($track_data['businessStatus'] == '签收') {
                $status = 2;
            } else {
                $map = [
                    'Прошел таможнфю',
                    'ПРОШЕЛ ТАМОЖНЮ',
                    'Отправлен в город получателя',
                ];
                $on_way = false;
                foreach ($track_data['trackDetails'] as $track_v) {
                    foreach ($map as $str_v) {
                        if (strpos($track_v['track_content'], $str_v) !== false) {
                            $on_way = true;
                            break 2;
                        }
                    }
                }

                if ($on_way) {
                    $status = 1;
                }
            }
        }

        //捷网 物流状态
        if($shipping_method['transport_code'] == 'jnet') {
            $track_data = $result['data'];
            if ($track_data['orderStatus'] === 3) {
                $status = 2;
            } else {
                $map = [
                    'Прошел таможнфю',
                    'ПРОШЕЛ ТАМОЖНЮ',
                    'Отправлен в город получателя',
                ];
                $on_way = false;
                foreach ($track_data['trackElementList'] as $track_v) {
                    if (strpos($track_v['facilityName'], 'Russia') !== false) {
                        $status = 1;
                        break;
                    }

                    foreach ($map as $str_v) {
                        if (strpos($track_v['desc'], $str_v) !== false) {
                            $on_way = true;
                            break;
                        }
                    }

                    if ($on_way) {
                        $status = 1;
                    }
                }
            }
        }


        //到达俄罗斯
        if($status === 1){
            if ($order_logistics['status'] == OrderLogisticsStatus::STATUS_ON_WAY) {
                return true;
            }
            OrderEventService::addEvent($order['source'], $order['shop_id'], $order['order_id'], OrderEvent::EVENT_TYPE_ON_WAY);
            $order_logistics['status'] = OrderLogisticsStatus::STATUS_ON_WAY;
            $order_logistics->save();
        }

        //已签收
        if($status === 2){
            if ($order_logistics['status'] == OrderLogisticsStatus::STATUS_WAIT) {
                OrderEventService::addEvent($order['source'], $order['shop_id'], $order['order_id'], OrderEvent::EVENT_TYPE_ON_WAY);
            }
            OrderEventService::addEvent($order['source'], $order['shop_id'], $order['order_id'], OrderEvent::EVENT_TYPE_DELIVERED);
            $order_logistics['status'] = OrderLogisticsStatus::STATUS_DELIVERED;
            $order_logistics->save();
        }

        if($status == 0) {
            if ($order['add_time'] + 6 * 24 * 24 * 30 < time()) {//超过半年订单设为异常
                $order_logistics['status'] = 4;
                $order_logistics->save();
                return true;
            }
        }
        return true;
    }

}