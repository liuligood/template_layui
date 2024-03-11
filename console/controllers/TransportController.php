<?php
namespace console\controllers;

use common\components\CommonUtil;
use common\models\sys\ShippingMethod;
use common\services\FTransportService;
use common\services\transport\BaseTransportService;
use common\services\transport\YanwenTransportService;
use common\services\transport\YuntuTransportService;
use yii\console\Controller;

class TransportController extends Controller
{

    /**
     * 更新运输方式
     */
    public function actionShippingMethod($transport_code)
    {
        $result = FTransportService::factory($transport_code)->getChannels();
        if($result['error'] == BaseTransportService::RESULT_SUCCESS){
            foreach ($result['data'] as $v) {
                //$transport_code = 'yanwen';
                $exist = ShippingMethod::find()->where(['transport_code'=>$transport_code,'shipping_method_code'=>$v['id']])->one();
                if($exist){
                    $old_name = $exist['shipping_method_name'];
                    if($v['name'] != $old_name){
                        $exist['shipping_method_name'] = $v['name'];
                        $exist->save();
                        echo $v['id'].' new：'.$v['name'] .' old：'. $old_name."\n";
                        CommonUtil::logs($v['id'].' new：'.$v['name'] .' old：'. $old_name, 'shipping_method');
                    }
                    continue;
                }
                $data = [
                    'transport_code' => $transport_code,
                    'shipping_method_code' => $v['id'],
                    'shipping_method_name' => $v['name'],
                    'currency' => 'CNY',
                    'status' => ShippingMethod::STATUS_INVALID
                ];
                //是否带电
                if(!empty($v['electric_status'])) {
                    $data['electric_status'] = $v['electric_status'];
                }
                ShippingMethod::add($data);
            }
        }
        echo '执行成功'."\n";
        exit;
    }

}