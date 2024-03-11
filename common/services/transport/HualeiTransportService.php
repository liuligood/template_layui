<?php
namespace common\services\transport;

use common\components\CommonUtil;
use common\models\OrderDeclare;
use common\models\OrderGoods;
use common\models\Shop;
use common\models\sys\ShippingMethod;
use common\services\FApiService;
use common\services\order\OrderService;
use common\services\sys\CountryService;
use yii\helpers\ArrayHelper;

/**
 * (立德国际物流)华磊物流接口业务逻辑类
 * http://118.24.172.73:8090/pages/viewpage.action?pageId=3473454
 */
class HualeiTransportService extends BaseTransportService
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
    public $base_url = 'http://129.204.214.77:8082';

    /**
     * 打单基础请求地址
     *
     */
    public $base_print_url = 'http://129.204.214.77:8089';

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
            ],
            'verify' => false,
            'base_uri' => $this->base_url,
            'timeout' => 20,
        ]);
        return $client;
    }

    /**
     * 获取用户授权信息
     * @return mixed|\Psr\Http\Message\ResponseInterface|string
     */
    public function getUserAuth()
    {
        $cache = \Yii::$app->redis;
        $cache_token_key = 'com::transport::hualei::auth' . $this->user_id;
        $token = $cache->get($cache_token_key);
        if (empty($token)) {
            $result = $this->getClient()->get('/selectAuth.htm?username=' . $this->user_id . '&password=' . $this->api_secret);
            $result = (string)$result->getBody();
            $token = str_replace("'", "\"", $result);
            $cache->setex($cache_token_key, 3 * 60 * 60, $token);
        }
        return json_decode($token, true);
    }

    /**
     * 获取发货渠道
     * @return array
     */
    public function getChannels()
    {
        try {
            $response = $this->getClient()->get('/getProductList.htm');
            $response = (string)$response->getBody();
            $result = mb_check_encoding($response, 'UTF-8') ? $response : mb_convert_encoding($response, 'UTF-8', 'gbk');
            $result = json_decode($result,true);
            if(empty($result)){
                return self::getResult(self::RESULT_FAIL, '', '接口异常');
            }

            $channel = [];
            foreach ($result as $v) {
                $channel[] = [
                    'id' => $v['product_id'],
                    'name' => $v['product_shortname'],
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
            $client = new \GuzzleHttp\Client([
                'verify' => false,
                'base_uri' => $this->base_url,
            ]);
            $response = $client->get('/selectTrack.htm?documentCode='.$code);
            $response = (string)$response->getBody();
            $result = mb_check_encoding($response, 'UTF-8') ? $response : mb_convert_encoding($response, 'UTF-8', 'gbk');
            $result = json_decode($result,true);
            if(empty($result)){
                return self::getResult(self::RESULT_FAIL, '', '接口异常');
            }
            $result = current($result);
            if (empty($result) || empty($result['data'])) {
                return self::getResult(self::RESULT_SUCCESS, '', '');
            } else {
                return self::getResult(self::RESULT_SUCCESS, $result['data'], '');
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
            $order_goods = ArrayHelper::index($order_goods,'id');

            //商品信息
            $quantity = 0;
            //$more_goods_name = '';
            $key = 0;
            $parcels = [];
            $weight = 0;

            $currency_code = 'USD';
            $ioss_code = '';
            $order_extra = [];
             if(CountryService::isEuropeanUnion($order['country'])) {
                 $ioss = (new OrderService())->getTaxNumber($order);
             }

             if($order['country'] != 'RU'){
                 return self::getResult(self::RESULT_FAIL, '', '该物流只适用于俄罗斯');
             }

            foreach ($order_declare as $v) {
                $quantity += $v['declare_num'];

                $weight += $v['declare_weight'];
                $weight = round($weight, 2);
                //$more_goods_name .= $v['declare_name_cn'] . ";";

                $sku = '';
                if(!empty($order_goods[$v['order_goods_id']])){
                    $sku = $order_goods[$v['order_goods_id']]['platform_asin'];
                }

                $declare_name_cn = $v['declare_name_cn'];
                $declare_name_en = $v['declare_name_en'];
                if($declare_name_cn == '手机') {
                    $declare_name_cn = '对讲机';
                    $declare_name_en = 'Intercom';
                }

                $info = [
                    "invoice_amount"=>$v['declare_price'],// "申报总价值，必填",
                    "invoice_pcs" => $v['declare_num'],// "件数，必填",
                    "invoice_title"=> $declare_name_en,// "英文品名，必填",
                    "invoice_weight"=> round($v['declare_weight'] / $v['declare_num'], 2),// "单件重",
                    "sku" => $declare_name_cn,// "中文品名",
                    "sku_code"=>$sku,// "配货信息",
                    "transaction_url"=>'',// "销售地址",
                    "invoiceunit_code"=>'',// "申报单位",
                    "invoice_imgurl"=>'',// "图片地址",
                    "invoice_brand"=>'',// "品牌",
                    "invoice_rule"=>'',// "规格",
                    "invoice_currency"=>$currency_code,// "申报币种",
                    "invoice_taxno"=>'',// "税则号",
                    "origin_country"=>'',// "原产国",
                    "invoice_material"=>'',// "材质",
                    "invoice_purpose"=>'',// "用途"
                ];
                if (!empty($v['declare_customs_code'])) {
                    $info['hs_code'] = $v['declare_customs_code'];// "海关编码",
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

            $user_auth = $this->getUserAuth();

            $order_transactionurl = '';
            if($shipping_method_code == '3661'){
                try {
                    $shop = Shop::findOne($order['shop_id']);
                    $api_service = FApiService::factory($shop);
                    $order_info = $api_service->getOrderInfo($order['relation_no']);
                    $sku_id = current($order_info['products'])['sku'];
                    $order_transactionurl = 'https://ozon.ru/context/detail/id/' . $sku_id;
                }catch (\Exception $e){
                    $order_transactionurl = 'https://ozon.ru/context/detail/id/96505089';
                }
            }


            $data = [
                "buyerid" => '',//买家id
                "order_piece" => 1,//件数，小包默认1，快递需真实填写
                //"consignee_mobile" => '',//手机号
                "order_returnsign" => 'Y',//退回标志，默认N表示不退回，Y标表示退回。中邮可以忽略该属性
                "trade_type" => 'ZYXT',
                "duty_type" => 'DDP',
                "consignee_name" => $order['buyer_name'],//收件人
                "consignee_companyname" => $order['company_name'],//收件人公司名称
                "consignee_address" => $order['address'],//收件地址街道
                "consignee_telephone" => $order['buyer_phone'],//收件电话
                "country" => $countryCode,//收件国家二字代码
                "consignee_state" => $order['area'],//收件人省/州
                "consignee_city" => $order['city'],//收货人-城市
                //"consignee_suburb" => '',//收货人-区
                "consignee_postcode" => $order['postcode'],//收货人-邮编
                "consignee_email" => $order['email'],//邮箱,
                //"consignee_taxno": "税号",
                //"consignee_streetno": "街道号",
                //"consignee_doorno": "门牌号",

                //"shipper_taxnotype":"税号类型，邮政产品可选值：IOSS,NO-IOSS,OTHER；DHL可选值：SDT、VAT、FTZ、DAN、EOR、CNP、EIN等(类型说明参照文档底部“DHL发件人税号类型”)","shipper_taxno":"税号","shipper_taxnocountry":"发件人税号国家,用国家二字码",

                "customer_id" => $user_auth['customer_id'],//客户ID
                "customer_userid" => $user_auth['customer_userid'],//登录人ID
                "order_customerinvoicecode" => $user_order_number, //原单号
                "product_id" => $shipping_method_code,//运输方式代码
                "weight" => $weight,//总重
                //"product_imagepath" => "",//图片地址，多图片地址用分号隔开
                "order_transactionurl" => $order_transactionurl,//产品销售地址
                //"order_cargoamount" => "",//选填；用于DHL/FEDEX运费；或用于白关申报（订单实际金额，特殊渠道使用）；或其他用途
                //"order_insurance": "保险金额",
                "cargo_type"=> "P",//包裹类型，P代表包裹，D代表文件，B代表PAK袋
                //"order_customnote"=>'',//自定义信息
                "orderInvoiceParam" => $parcels,
                "orderVolumeParam"=> [],/*[//选填
                    [
                    "volume_height"=>'',// "高，单位CM",
                    "volume_length"=>'',// "长，单位CM",
                    "volume_width"=>'',// "宽，单位CM",
                    "volume_weight"=>'',// "实重"
                    ]
                ],*/
            ];

            CommonUtil::logs('HUALEI orderId:' . $order_id . ' ' . json_encode($data), "transport");

            $response = $this->getClient()->post('/createOrderApi.htm', [
                'form_params' => ['param' => json_encode($data)]
            ]);
            $response = (string)$response->getBody();
            $response = json_decode($response, true);
            $message = urldecode( $response['message']);

            //记录返回数据到log文件
            CommonUtil::logs('HUALEI response,result,orderId:' . $order_id . ':' . print_r($response, true), "transport");
            if(!empty($response['tracking_number']) && !empty($response['ack']) && strtolower($response['ack']) == 'true' && (strtolower($message) == 'success' || $message == '')) {
                //获取跟踪号
                CommonUtil::logs('HUALEI success,result,orderId:' . $order_id . ' ' . json_encode($response), "transport");
                $track_no = $response['tracking_number'];
                $delivery_order_id = $response['order_id'];
                $track_logistics_no = '';//跟踪号
                return self::getResult(self::RESULT_SUCCESS, ['delivery_order_id'=>$delivery_order_id,'track_no' => $track_no, 'track_logistics_no' => $track_logistics_no], "上传成功，客户单号：" . $track_no);
            } else {
                if (!empty($message)) {
                    $error = $message;
                } else {
                    $error = '创建订单失败!';
                }
                CommonUtil::logs('HUALEI error,result,orderId:' . $order_id . ' ' . json_encode($response), "transport");
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
        $customer_number_arr = ArrayHelper::getColumn($order_lists,'delivery_order_id');
        $order_ids = ArrayHelper::getColumn($order_lists,'order_id');
        $order_ids = implode(',', $order_ids);

        CommonUtil::logs('HUALEI print result,orderId:' . $order_ids . print_r($customer_number_arr, true), "transport");

        //华磊系统的物流普通标签打印是通过url跳转获得pdf文件链接
        $url = $this->base_print_url.'/order/FastRpt/PDF_NEW.aspx?PrintType=lab10_10&order_id='
            .implode(',',$customer_number_arr);
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url);
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt( $ch, CURLOPT_TIMEOUT, 60);

        $response = curl_exec( $ch);
        $http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE);
        $response_headers = curl_getinfo( $ch);

        curl_close( $ch);
        $pdf_url = '';
        if( $response != $response_headers)
            $pdf_url = $response_headers["url"];

        if( !preg_match('/\.pdf$/', $pdf_url)) {
            return self::getResult(self::RESULT_FAIL, '', '打印运单失败,没有获取到正确的PDF链接');
        }

        //记录返回数据到log文件
        CommonUtil::logs('HUALEI print success,result,orderId:' . $order_ids . ' ' . $url, "transport");;
        if($is_show) {
            header("Content-type: application/pdf");
            echo file_get_contents($pdf_url);
            exit();
        }
        return self::getResult(self::RESULT_SUCCESS, ['pdf_url' => $pdf_url], '连接已生成,请点击并打印');
    }

}
