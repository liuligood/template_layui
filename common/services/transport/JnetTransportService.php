<?php
namespace common\services\transport;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\OrderDeclare;
use common\models\OrderGoods;
use common\models\Shop;
use common\models\sys\ShippingMethod;
use common\services\FApiService;
use common\services\order\OrderService;
use common\services\sys\CountryService;
use yii\helpers\ArrayHelper;

/**
 * 捷网国际物流接口业务逻辑类
 * http://www.j-net.cn/doc/j-net-o-api-new.html#p06
 */
class JnetTransportService extends BaseTransportService
{
    /**
     * 用户ID
     * @var string
     */
    public $user_id;
    /**
     * 用户 ApiSecret
     * @var string
     */
    public $api_secret;

    /**
     * 基础请求地址
     *
     */
    public $base_url = 'http://api.j-net.cn';

    /**
     * 请求地址
     *
     */
    public $server_url = '';
    public $request_body;

    public function __construct($param)
    {
        if(!empty($param['base_url'])) {
            $this->base_url = $param['base_url'];
        }
        $this->user_id = $param['user_id'];
        $this->api_secret = $param['api_secret'];
    }

    /**
     * @return \GuzzleHttp\Client
     */
    public function getClient()
    {
        $client = new \GuzzleHttp\Client([
            'headers'=>[
                'Accept'=>'application/json',
                'Content-Type' => 'application/json',
                'clientId'=>$this->user_id,
                'md5' => md5($this->user_id.$this->api_secret)
            ],
            'verify' => false,
            'base_uri' => $this->base_url,
            'timeout' => 20,
        ]);
        return $client;
    }

    /**
     * 获取平台发货渠道
     * @param $platform_type
     * @param $shipping_method_code
     * @param $order_id
     * @return string
     */
    public static function getPlatformTransportCode($platform_type,$shipping_method_code = null,$order_id = '')
    {
        $code = 'JNET';
        switch ($platform_type){
            case Base::PLATFORM_COUPANG:
                $code = 'JNET';
                break;
        }
        return $code;
    }

    /**
     * 获取发货渠道
     * @return array
     */
    public function getChannels()
    {
        try {
            $response = $this->getClient()->get('/model');
            $response = (string)$response->getBody();
            $result = json_decode($response,true);
            if(empty($result)){
                return self::getResult(self::RESULT_FAIL, '', '接口异常');
            }

            $channel = [];
            foreach ($result['RecList'] as $v) {
                $channel[] = [
                    'id' => $v['code'],
                    'name' => $v['model'],
                ];
            }

            if (empty($channel)) {
                return self::getResult(self::RESULT_FAIL, '', '');
            } else {
                return self::getResult(self::RESULT_SUCCESS, $channel, '');
            }
        } catch (\Exception $e) {
            return self::getResult(self::RESULT_FAIL, '', $e->getMessage());
        }
    }

    /**
     * 获取物流跟踪
     * @param $code
     * @return array
     */
    public function getTrackLogistics($code)
    {
        try {
            $response = $this->getClient()->get('/track/'.$code);
            $response = (string)$response->getBody();
            $result = json_decode($response,true);
            $result = $result['RecList'];
            $result = current($result);
            if (empty($result)) {
                return self::getResult(self::RESULT_SUCCESS, '', '');
            } else {
                return self::getResult(self::RESULT_SUCCESS, $result, '');
            }
        } catch (\Exception $e) {
            return self::getResult(self::RESULT_FAIL, '', $e->getMessage());
        }
    }

    /**
     * 上传订单
     * @param $order
     * @return array|mixed
     */
    public function getOrderNO($order)
    {
        try {
            $order_id = $order['order_id'];
            $order_declare = OrderDeclare::find()->where(['order_id' => $order['order_id']])->asArray()->all();
            $shipping_method = ShippingMethod::findOne($order['logistics_channels_id']);
            $shipping_method_code = $shipping_method['shipping_method_code'];
            $order_goods = OrderGoods::find()->where(['order_id' => $order['order_id']])->asArray()->all();
            $order_goods = ArrayHelper::index($order_goods, 'id');

            //商品信息
            $quantity = 0;
            //$more_goods_name = '';
            $key = 0;
            $parcels = [];
            $weight = 0;

            $currency_code = 'USD';
            $ioss_code = '';
            $order_extra = [];
            if (CountryService::isEuropeanUnion($order['country'])) {
                $ioss = (new OrderService())->getTaxNumber($order);
            }

            if ($order['country'] != 'RU') {
                //return self::getResult(self::RESULT_FAIL, '', '该物流只适用于俄罗斯');
            }

            $order_transactionurl = '';//物品连接，0-2048字符；俄全通普货和带电必须填写物品链接
            if ($shipping_method_code == 'RU-NZH' && $order['country'] == 'RU') {
                if($order['source'] == Base::PLATFORM_OZON) {
                    try {
                        $shop = Shop::findOne($order['shop_id']);
                        $api_service = FApiService::factory($shop);
                        $order_info = $api_service->getOrderInfo($order['relation_no']);
                        $sku_id = current($order_info['products'])['sku'];
                        $order_transactionurl = 'https://ozon.ru/context/detail/id/' . $sku_id;
                    } catch (\Exception $e) {
                        $order_transactionurl = 'https://ozon.ru/context/detail/id/96505089';
                    }
                }
            }

            foreach ($order_declare as $v) {
                $quantity += $v['declare_num'];

                $weight += $v['declare_weight'];
                $weight = round($weight, 2);
                //$more_goods_name .= $v['declare_name_cn'] . ";";

                $sku = '';
                if (!empty($order_goods[$v['order_goods_id']])) {
                    $sku = $order_goods[$v['order_goods_id']]['platform_asin'];
                }

                $declare_name_cn = $v['declare_name_cn'];
                $declare_name_en = $v['declare_name_en'];
                if ($declare_name_cn == '手机') {
                    $declare_name_cn = '对讲机';
                    $declare_name_en = 'Intercom';
                }

                $info = [
                    "cnName" => $declare_name_cn,// "中文品名",
                    "enName" => $declare_name_en,// "英文品名，必填",
                    "quantity" => $v['declare_num'],// "件数，必填",
                    "declaredValue" => round($v['declare_price'] / $v['declare_num'], 2),// "申报总价值，必填",
                    "sku" => $sku,// "配货信息",
                ];
                if (!empty($order_transactionurl)) {
                    $info['url'] = $order_transactionurl;
                }
                if (!empty($v['declare_customs_code'])) {
                    $info['hsCode'] = $v['declare_customs_code'];// "海关编码",
                }
                $parcels[] = $info;
                $key++;
            }

            /*$more_goods_name .= "\n";
            foreach ($order_goods as $v) {
                $more_goods_name .= $v['platform_asin'] . "*" . $v['goods_num'] . ";";
            }*/

            if ($order['country'] == "UK") {
                $countryCode = "GB";
            } else {
                $countryCode = $order['country'];
            }

            //重复发货订单号
            //$user_order_number =  empty($order['track_no'])?$order['relation_no']:$order['track_no'];
            $user_order_number = $order['relation_no'];
            $user_order_number = !empty($order['logistics_reset_times']) ? ($user_order_number . '-' . $order['logistics_reset_times']) : $user_order_number;

            $national_id = in_array($order['source'],[Base::PLATFORM_COUPANG])?$order['user_no']:'';
            if($countryCode == 'BR') {
                $national_id = empty($order['user_no'])?'':$order['user_no'];
            }
            
            $data = [
                //"waybillNum" => $user_order_number,//运单号
                "clientId" => $this->user_id,//捷网注册用户客户ID；唯一身份识别字段
                "type" => 1,//快件类型，默认为1；0(文件),1(包裹),2(防水袋)
                "language" => 0,//地域语言，默认为0；0(中国大陆),1(华语),2(其它地域)
                "model" => $shipping_method_code,//快递渠道
                "des" => $countryCode,//快递渠道所到达的国家,填写国家二字码；0-63字符
                "weight" => $weight,//重量，kg
                "pieces" => 1,//包裹数量
                "receiverName" => $order['buyer_name'],//收件人
                "receiverUnit" => empty($order['company_name']) ? $order['buyer_name'] : $order['company_name'],//收件人公司名称
                "receiverAddr" => $order['address'],//收件地址街道
                "receiverCity" => $order['city'],//收货人-城市
                "receiverPostcode" => $order['postcode'],//收货人-邮编
                "receiverProvince" => $order['area'],//收件人省/州
                "receiverCountry" => $countryCode,//收件国家二字代码
                "receiverPhone" => $order['buyer_phone'],//收件电话
                "receiverSms" => $order['buyer_phone'],//收件电邮，0-63字符；若无可以和收件电话相同
                "receiverEMail" => $order['email'],//邮箱,
                "receiverTaxNumber" => $national_id,
                "memo" => $shipping_method_code,
                "source" => 'own',
                "currency" => $currency_code,
                "goodsList" => $parcels,
                'refNo' => $user_order_number,
            ];

            CommonUtil::logs('J-Net orderId:' . $order_id . ' ' . json_encode($data), "transport");

            $response = $this->getClient()->post('/order', [
                'body' => json_encode([$data])
            ]);
            $response = (string)$response->getBody();
            $response = json_decode($response, true);
            //记录返回数据到log文件
            CommonUtil::logs('J-Net response,result,orderId:' . $order_id . ':' . print_r($response, true), "transport");
            if (!empty($response['RecList']) && $response['ReturnValue'] > 0) {
                //获取跟踪号
                CommonUtil::logs('J-Net success,result,orderId:' . $order_id . ' ' . json_encode($response), "transport");
                $re_lists = current($response['RecList']);
                $track_no = $re_lists['message'];
                $track_logistics_no = '';//跟踪号
                return self::getResult(self::RESULT_SUCCESS, ['track_no' => $track_no, 'track_logistics_no' => $track_logistics_no], "上传成功，客户单号：" . $track_no);
            } else {
                if (!empty($response['cMess'])) {
                    $error = $response['cMess'];
                } else {
                    $error = '创建订单失败!';
                }
                CommonUtil::logs('J-Net error,result,orderId:' . $order_id . ' ' . json_encode($response), "transport");
                return self::getResult(self::RESULT_FAIL, '', $error);
            }
        } catch (\Exception $e) {
            return self::getResult(self::RESULT_FAIL, '', $e->getMessage());
        }
    }

    //用来判断是否支持打印
    public static function isPrintOk()
    {
        return true;
    }

    /**
     * 取消跟踪号
     * @param $data
     * @return mixed
     */
    public function cancelOrderNO($data)
    {
        // TODO: Implement cancelOrderNO() method.
    }

    /**
     * 交运
     * @param $data
     * @return mixed
     */
    public function doDispatch($data)
    {
        // TODO: Implement doDispatch() method.
    }

    /**
     * 申请跟踪号
     * @param $data
     * @return mixed
     */
    public function getTrackingNO($data)
    {
        // TODO: Implement getTrackingNO() method.
    }

    /**
     * 打单
     * @param $order_lists
     * @param bool $is_show
     * @return array|mixed
     */
    public function doPrint($order_lists, $is_show = false)
    {
        $customer_number_arr = ArrayHelper::getColumn($order_lists,'track_no');
        $order_ids = ArrayHelper::getColumn($order_lists,'order_id');
        $order_ids = implode(',', $order_ids);

        CommonUtil::logs('J-Net print result,orderId:' . $order_ids . print_r($customer_number_arr, true), "transport");
        $response = $this->getClient()->get('/label?type=RM&encryptype=file&nums='.implode(",",$customer_number_arr));
        $response = (string)$response->getBody();
        //CommonUtil::logs('J-Net print error1,result,orderId:' . $order_ids . ' ' .$response, "transport");
        /*$response = json_decode($response, true);
        //记录返回数据到log文件
        if($response['ReturnValue'] < 0){
            CommonUtil::logs('J-Net print error2,result,orderId:' . $order_ids . ' ' . json_encode($response), "transport");
            return self::getResult(self::RESULT_FAIL, '', '打印运单失败,请检查订单后重试');
        } else {*/
            //CommonUtil::logs('J-Net print success,result,orderId:' . $order_ids . ' ' . json_encode($response), "transport");
            if (strlen($response) > 1000) {
                if($is_show) {
                    header("Content-type: application/pdf");
                    echo $response;
                    exit();
                }
                $pdfUrl = CommonUtil::savePDF($response);
                return self::getResult(self::RESULT_SUCCESS, ['pdf_url' => $pdfUrl['pdf_url']], '连接已生成,请点击并打印');
            } else {
                CommonUtil::logs('J-Net print error2,result,orderId:' . $order_ids . ' ' . json_encode($response), "transport");
                return self::getResult(self::RESULT_FAIL, '', '打印运单失败,请检查订单后重试');
            }
       // }
    }

}
