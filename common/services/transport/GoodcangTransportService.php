<?php
namespace common\services\transport;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\goods\GoodsChild;
use common\models\Order;
use common\models\order\OrderTransport;
use common\models\order\OrderTransportFeeDetail;
use common\models\OrderGoods;
use common\services\FFBWService;

/**
 * 谷仓物流接口业务逻辑类
 */
class GoodcangTransportService extends BaseTransportService
{

    /**
     * 获取平台发货渠道
     * @param $platform_type
     * @param $shipping_method_code
     * @param $order_id
     * @return string
     */
    public static function getPlatformTransportCode($platform_type,$shipping_method_code = null,$order_id = '')
    {
        if ($shipping_method_code == 'CZ-EU_DHL') {
            return 'DHL';
        }
        if ($shipping_method_code == 'CZ-PPL_CZ') {
            if ($platform_type == Base::PLATFORM_ALLEGRO) {
                return 'DHL';
            }
            return 'PPL';
        }
        if ($shipping_method_code == 'CZ-G2G_STANDARD') {
            if($platform_type == Base::PLATFORM_ALLEGRO) {
                return 'InPost';
            }
            if($platform_type == Base::PLATFORM_WORTEN) {
                $order = Order::find()->where(['order_id'=>$order_id])->one();
                if($order['country'] == 'PT'){
                    return 'ctt';
                }
                if($order['country'] == 'ES'){
                    return 'correosexpress';
                }
            }
            if($platform_type == Base::PLATFORM_RDC) {//法国
                $order_goods = OrderGoods::find()->where(['order_id'=>$order_id])->asArray()->all();
                $weight = 0;
                foreach ($order_goods as $v) {
                    $goods_child = GoodsChild::find()->where(['cgoods_no'=>$v['cgoods_no']])->asArray()->one();
                    $goods_weight = $goods_child['real_weight'] > 0?$goods_child['real_weight']:$goods_child['weight'];
                    $weight += $goods_weight * $v['goods_num'];
                }
                if($weight > 2) {
                    return 'GLS';
                } else {
                    return 'Colissimo';
                }
            }
        }
    }

    public function getChannels()
    {
        // TODO: Implement getChannels() method.
    }

    /**
     * 上传订单
     * @param $order
     * @return array|mixed
     */
    public function getOrderNO($data)
    {
        try {
            $order_id = $data['order_id'];
            $order_transport = OrderTransport::find()->where(['order_id' => $order_id, 'status' => [OrderTransport::STATUS_UNCONFIRMED, OrderTransport::STATUS_CONFIRMED]])->one();
            $ffbw = FFBWService::factory($data['warehouse']);
            if (!empty($order_transport)) {
                if (!empty($order_transport['track_no'])) {
                    return self::getResult(self::RESULT_FAIL, '', '该运单号已生成');
                }
            } else {
                $result = $ffbw->addOrder($order_id);
                if (empty($result['message']) || $result['message'] != 'Success') {
                    $error = $result['Error']['errMessage'];
                    CommonUtil::logs('Goodcang error,result,orderId:' . $order_id . ' ' . json_encode($result), "transport");
                    return self::getResult(self::RESULT_FAIL, '', $error);
                }
                $order_code = $result['data']['order_code'];
                $order_transport = new OrderTransport();
                $order_transport->order_id = $order_id;
                $order_transport->transport_code = $this->transport_code;
                $order_transport->warehouse_id = $data['warehouse'];
                $order_transport->shipping_method_id = $data['logistics_channels_id'];
                $order_transport->order_code = $order_code;
                $order_transport->status = OrderTransport::STATUS_UNCONFIRMED;
                $order_transport->admin_id = !empty(\Yii::$app->user) ? strval(\Yii::$app->user->getId()) : 0;
                $order_transport->save();
            }

            $order_exist = $ffbw->getOrder($order_id);
            if (empty($order_exist['data']) || $order_exist['ask'] != 'Success') {
                $error = $order_exist['Error']['errMessage'];
                CommonUtil::logs('Goodcang error,result,orderId:' . $order_id . ' ' . json_encode($order_exist), "transport");
                return self::getResult(self::RESULT_FAIL, '', $error);
            }

            $order_exist = $order_exist['data'];
            $track_no = $order_exist['tracking_no'];
            $order_transport->track_no = $track_no;
            $order_transport->weight = $order_exist['real_weight'];
            $order_transport->size = $order_exist['real_size'];
            $order_transport->currency = $order_exist['charge_details']['currency_code'];
            $order_transport->total_fee = $order_exist['charge_details']['total_fee'];
            $order_transport->status = empty($track_no)?OrderTransport::STATUS_CONFIRMED:OrderTransport::STATUS_EXISTING_TRACKING;
            $order_transport->save();

            foreach ($order_exist['charge_details']['charge_list'] as $fee_v) {
                $exist_fee = OrderTransportFeeDetail::find()->where(['order_transport_id'=>$order_transport->id,'fee_code'=>$fee_v['fee_code']])->one();
                if(!empty($exist_fee)) {
                    continue;
                }
                $order_transport_fee = new OrderTransportFeeDetail();
                $order_transport_fee->order_transport_id = $order_transport->id;
                $order_transport_fee->order_id = $order_id;
                $order_transport_fee->order_code = $order_transport->order_code;
                $order_transport_fee->warehouse_id = $order_transport->warehouse_id;
                $order_transport_fee->fee_name = $fee_v['fee_name'];
                $order_transport_fee->fee_code = $fee_v['fee_code'];
                $order_transport_fee->fee = $fee_v['fee_amount'];
                $order_transport_fee->currency = $order_transport->currency;
                $order_transport_fee->status = OrderTransport::STATUS_CONFIRMED;
                $order_transport_fee->save();
            }
            return self::getResult(self::RESULT_SUCCESS, ['delivery_order_id' => $order_transport->order_code, 'track_no' => $track_no], "上传成功，客户单号：" . $track_no);
        } catch (\Exception $e) {
            return self::getResult(self::RESULT_FAIL, '', $e->getMessage());
        }
    }

    public function cancelOrderNO($data)
    {
        // TODO: Implement cancelOrderNO() method.
    }

    public function doDispatch($data)
    {
        // TODO: Implement doDispatch() method.
    }

    public function getTrackingNO($data)
    {
        // TODO: Implement getTrackingNO() method.
    }

    public function doPrint($data, $is_show = false)
    {
        // TODO: Implement doPrint() method.
    }
}
