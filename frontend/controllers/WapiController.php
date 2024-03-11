<?php

namespace frontend\controllers;

use common\models\Order;
use common\models\warehousing\LogisticsOutboundLog;
use common\services\transport\TransportService;
use yii\web\Controller;
use yii\web\Response;

/**
 * 仓储接口
 */
class WapiController extends Controller
{
    public $enableCsrfValidation = false;

    const REQUEST_SUCCESS=1;
    const REQUEST_FAIL=0;

    /**
     * 扫描物流单号
     */
    public function actionScanLogisticsNo()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        $req = \Yii::$app->request;
        $logistics_no = $req->post('logisticsNo');
        $weight = $req->post('weight',0);
        $length = $req->post('length',0);
        $width = $req->post('width',0);
        $height = $req->post('height',0);
        $pic = $req->post('pic','');
        if(empty($logistics_no)) {
            return $this->FormatArray(self::REQUEST_FAIL, '物流单号不能为空');
        }
        $data = [
            'logistics_no' => trim($logistics_no),
            'weight' => $weight,
            'length' => $length,
            'width' => $width,
            'height' => $height,
            'pic' => $pic,
        ];
        $logistics = LogisticsOutboundLog::add($data);
        $order = Order::find()->where(['track_no'=>$logistics_no])->one();
        if(empty($order)) {
            return $this->FormatArray(self::REQUEST_FAIL, '订单不存在');
        }
        $shipping_method = TransportService::getShippingMethodInfo($order['logistics_channels_id']);
        $transport = TransportService::getTransportInfo($shipping_method['transport_code']);
        $msg = empty($transport['transport_name'])?'不存在物流方式':$transport['transport_name'];
        return $this->FormatArray(self::REQUEST_SUCCESS, $msg);
    }

    /**
     * @param int $code 状态码
     * @param string $msg 错误消息
     * @return array
     */
    public function FormatArray($code,$msg){
        return ['status'=>$code,'msg'=>$msg];
    }

}