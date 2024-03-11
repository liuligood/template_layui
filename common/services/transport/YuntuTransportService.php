<?php
namespace common\services\transport;

use common\components\CommonUtil;
use common\components\HelperCurl;
use common\components\statics\Base;
use common\models\Order;
use common\models\OrderDeclare;
use common\models\OrderGoods;
use common\models\sys\ShippingMethod;
use common\services\order\OrderService;
use common\services\sys\CountryService;
use Exception;
use Jaeger\GHttp;
use yii\helpers\ArrayHelper;

/**
 * 云途物流接口业务逻辑类
 */
class YuntuTransportService extends BaseTransportService
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
    public $base_url = 'http://oms.api.yunexpress.com';

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
                'Authorization'=> 'basic '.$this->getToken(),
                'Accept'=>'application/json',
                'Content-Type' => 'application/json',
            ],
            'verify' => false,
            'base_uri' => $this->base_url,
            'timeout' => 20,
        ]);
        return $client;
    }


    public function getToken()
    {
        $customerID = $this->user_id;
        $apiSecret = $this->api_secret;
        $password = $customerID . '&' . $apiSecret;
        return base64_encode($password);
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
        $code = 'Yuntu';
        switch ($platform_type){
            case Base::PLATFORM_REAL_DE:
            case Base::PLATFORM_CDISCOUNT:
            case Base::PLATFORM_ALLEGRO:
                $code = 'Yun Express';
                break;
            case Base::PLATFORM_EPRICE:
                $code = 'YUNEXPRESS';
                break;
            case Base::PLATFORM_MICROSOFT:
            case Base::PLATFORM_WALMART:
                $code = 'YunExpress';
                break;
            case Base::PLATFORM_WORTEN:
                $code = 'yunexpress';
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
            $result = $this->getClient()->get('/api/Common/GetShippingMethods');
            $result = (string)$result->getBody();
            $result = json_decode($result,true);
            if(empty($result) || empty($result['Items'])){
                return self::getResult(self::RESULT_FAIL, '', '接口异常');
            }

            $channel = [];
            foreach ($result['Items'] as $v) {
                $channel[] = [
                    'id'=>$v['Code'],
                    'name'=>$v['DisplayName'],
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
                 if (!empty($ioss)) {
                     //$currency_code = 'EUR';
                     $platform_name = empty(Base::$order_source_maps[$order['source']]) ? '' : Base::$order_source_maps[$order['source']];
                     try {
                         $ioss_result = $this->getRegisterIoss($ioss, $platform_name);
                         if ($ioss_result['error'] == self::RESULT_SUCCESS) {
                             $ioss_code = $ioss_result['data']['ioss_code'];
                         } else {
                             return $ioss_result;
                         }
                     } catch (\Exception $e) {
                         return self::getResult(self::RESULT_FAIL, '', '云途ioss报备失败' . $e->getMessage());
                     }
                 } else {
                     $order_extra['ExtraCode'] = 'V1';
                     $order_extra['ExtraName'] = '云途预缴';
                     $order_extra = [$order_extra];
                 }
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

                $info = [
                    'EName' => $v['declare_name_en'],//包裹申报名称(英文)必填
                    'CName' => $v['declare_name_cn'],//包裹申报名称(中文)
                    //'HSCode' => '',//海关编码
                    'Quantity' => $v['declare_num'],//申报数量,必填
                    'UnitPrice' => round($v['declare_price'] / $v['declare_num'], 2),//申报价格(单价),单位 USD,必填
                    'UnitWeight' => round($v['declare_weight'] / $v['declare_num'], 2),//申报重量(单重)，单位 kg
                    'Remark' => '',//订单备注，用于打印配货单
                    'ProductUrl' => '',//产品销售链接地址
                    'SKU' => $sku,//用于填写商品 SKU，FBA 订单必填
                    'InvoiceRemark' => '',//配货信息
                    'CurrencyCode' => $currency_code,//申报币种，默认USD，仅英国国家支持USD和GBP
                ];
                if (!empty($v['declare_customs_code'])) {
                    $info['HsCode'] = $v['declare_customs_code'];
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
            //云途大货专线挂号 或者 捷克 客户编号不超过30字符
            if(($shipping_method_code == 'DHZXR'|| $order['country'] =='CZ') && $order['source'] == Base::PLATFORM_ALLEGRO) {
                $user_order_number = $order['order_id'];
            }
            $user_order_number = !empty($order['logistics_reset_times']) ? ($user_order_number . '-' . $order['logistics_reset_times']) : $user_order_number;


            $buyer_name = explode(' ', $order['buyer_name']);
            $name_i = 0;
            $first_name = '';
            $last_name = [];
            foreach ($buyer_name as $name_v){
                if($name_i == 0) {
                    $first_name = $name_v;
                } else {
                    $last_name[] = $name_v;
                }
                $name_i++;
            }
            $last_name = empty($last_name) ? $first_name : implode(' ',$last_name);

            //用户税号
            $national_id = '';
            if($countryCode == 'BR') {
                $national_id = empty($order['user_no'])?'':$order['user_no'];
            }
            //英国vat
            $ioss = $order['tax_number'];
            if($countryCode == 'GB' && strpos($ioss,'GB') !== false) {
                $national_id = $ioss;
            }

            $data = [
                'CustomerOrderNumber' => $user_order_number,//客户订单号,不能重复
                'ShippingMethodCode' => $shipping_method_code,//运输方式代码
                //'TrackingNumber' => '',//包裹跟踪号，可以不填写
                'TransactionNumber' => !empty($order['tax_relation_no'])?$order['tax_relation_no']:$order['relation_no'],//平台交易号（wish 邮）
                //'TaxNumber'=>'',//增值税号，巴西国家必填 CPF 或 CNPJ， CPF 码格式为 000.000.000-00,CNPJ码格式 为 00.000.000/0000-00，其它国家非必填， 英国税号格式为：前缀GB+9位纯数字 或者 前缀GB+12位纯数字
                'TaxNumber' => $national_id,
                //'EoriNumber' => '',//欧盟税号，非必填，格式为：前缀GB+9位 纯数字+后缀000 或者 前缀GB+12位纯数字 +后缀000
                //'Length'=>1,//预估包裹单边长，单位cm，非必填，默认 1
                //'Width'=>1,//预估包裹单边宽，单位cm，非必填，默认 1
                //'Height'=>1,//预估包裹单边高，单位cm，非必填，默认 1
                'PackageCount' => 1,//运单包裹的件数，必须大于 0 的整数
                'Weight' => $weight,//预估包裹总重量，单位 kg,最多 3 位小数
                'Receiver' => [//收件人信息
                    'CountryCode' => $countryCode,//收件人所在国家，填写国际通用标准 2 位简码， 可通过国家查询服务查询
                    'FirstName' => $first_name,//收件人姓
                    'LastName' => $last_name,//收件人名字
                    'Company' => $order['company_name'],//收件人公司名称
                    'Street' => $order['address'],//收件人详细地址
                    'StreetAddress1' => '',//收件人详细地址 1
                    'StreetAddress2' => '',//收件人详细地址 2
                    'State' => $order['area'],//收件人省/州
                    'City' => $order['city'],//收货人-城市
                    'Zip' => $order['postcode'],//收货人-邮编
                    'Phone' => $order['buyer_phone'],//收件人电话
                    'HouseNumber' => '',//收件人街道地址门牌号
                    'Email' => $order['email'],//收件人电子邮箱
                    'MobileNumber' => '',//收件人手机号
                    //'CertificateCode' => '',//收件人ID，中东专线-约旦国家必填，10位数字
                ],
                //'Sender' => [],//发件人信息
                //'ApplicationType' => 4,//申 报 类 型 , 用 于 打 印 CN22 ， 1-Gift,2-Sameple,3-Documents,4-Others, 默 认 4-Other
                //'ReturnOption' => 0',//是否退回,包裹无人签收时是否退回，1-退 回，0-不退回，默认 0
                //'TariffPrepay' => 0,//关税预付服务费，1-参加关税预付，0-不参加 关税预付，默认 0 (渠道需开通关税预付 服务)
                //'InsuranceOption' => 0,//包裹投保类型，0-不参保，1-按件，2-按比 例，默认 0，表示不参加运输保险，具体参 考包裹运输
                //'Coverage' => 0,//保险的最高额度，单位 RMB
                //'SensitiveTypeID'=>'',//包裹中特殊货品类型，可调用货品类型查 询服务查询，可以不填写，表示普通货品
                'Parcels' => $parcels,//申报信息
                //'SourceCode' => '',//订单来源
                //'ChildOrders' => [],//箱子明细信息，FBA 订单必填
                'OrderExtra' => $order_extra,
                'IossCode' => $ioss_code,
            ];

            CommonUtil::logs('YANTU orderId:' . $order_id . ' ' . json_encode($data), "transport");

            $response = $this->getClient()->post('/api/WayBill/CreateOrder', [
                'body' => json_encode([$data])
            ]);
            $response = (string)$response->getBody();
            $response = json_decode($response, true);
            //记录返回数据到log文件
            CommonUtil::logs('YANTU response,result,orderId:' . $order_id . ':' . print_r($response, true), "transport");
            if (!empty($response) && !empty($response['Item']) && $response['Code'] == '0000') {
                //获取跟踪号
                CommonUtil::logs('YANTU success,result,orderId:' . $order_id . ' ' . json_encode($response), "transport");
                $items = current($response['Item']);
                $track_no = $items['WayBillNumber'];
                $track_logistics_no = empty($items['TrackingNumber']) ? '' : $items['TrackingNumber'];//跟踪号
                return self::getResult(self::RESULT_SUCCESS, ['track_no' => $track_no, 'track_logistics_no' => $track_logistics_no], "上传成功，客户单号：" . $track_no);
            } else {
                if(!empty($response['Item'])) {
                    $items = current($response['Item']);
                    $error = $items['Remark'];
                }
                if(empty($error)) {
                    if (!empty($response['Message'])) {
                        $error = $response['Message'];
                    } else {
                        $error = '创建订单失败!';
                    }
                }
                CommonUtil::logs('YANTU error,result,orderId:' . $order_id . ' ' . json_encode($response), "transport");
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

        CommonUtil::logs('YANTU print result,orderId:' . $order_ids . print_r($customer_number_arr, true), "transport");
        $response = $this->getClient()->post('/api/Label/Print', [
            'body' => json_encode($customer_number_arr)
        ]);
        $response = (string)$response->getBody();
        CommonUtil::logs('YANTU print error1,result,orderId:' . $order_ids . ' ' .$response, "transport");
        $response = json_decode($response, true);
        //记录返回数据到log文件
        if($response['Code'] != '0000'){
            CommonUtil::logs('YANTU print error2,result,orderId:' . $order_ids . ' ' . json_encode($response), "transport");
            return self::getResult(self::RESULT_FAIL, '', '打印运单失败,请检查订单后重试');
        } else {
            CommonUtil::logs('YANTU print success,result,orderId:' . $order_ids . ' ' . json_encode($response), "transport");
            $item = current($response['Item']);
            $url = $item['Url'];
            if($is_show) {
                header("Content-type: application/pdf");
                echo file_get_contents($url);
                exit();
            }
            return self::getResult(self::RESULT_SUCCESS, ['pdf_url' => $url], '连接已生成,请点击并打印');
        }
    }

    /**
     * ioss报备
     * @param $ioss
     * @param $platform_name
     */
    public function getRegisterIoss($ioss, $platform_name)
    {
        $cache_token_key = 'com::yuntu::ioss::'.$ioss;
        $cache = \Yii::$app->cache;
        $ioss_code = $cache->get($cache_token_key);
        if (empty($ioss_code)) {
            $ioss_data = [
                'IossType' => 1,
                'PlatformName' => empty($platform_name) ? 'Amazon' : $platform_name,
                'IossNumber' => $ioss
            ];
            CommonUtil::logs('YANTU RegisterIoss post,ioss:' . $ioss . print_r($ioss_data, true), "transport");
            $response = $this->getClient()->post('/api/WayBill/RegisterIoss', [
                'body' => json_encode($ioss_data)
            ]);
            $response = (string)$response->getBody();
            CommonUtil::logs('YANTU RegisterIoss result,ioss:' . $ioss . ' ' . $response, "transport");
            $response = json_decode($response, true);
            if (empty($response['IossCode'])) {
                return self::getResult(self::RESULT_FAIL, '', 'isso报备失败：' . $response['Message']);
            }
            $ioss_code=  $response['IossCode'];
            $cache->set($cache_token_key, $ioss_code, 60 * 60 * 7);
        }
        return self::getResult(self::RESULT_SUCCESS, ['ioss_code' => $ioss_code], '');
    }

}
