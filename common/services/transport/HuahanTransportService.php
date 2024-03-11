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
 * 华翰物流接口业务逻辑类
 * http://new.hh-exp.com:8181/docs/mindoc/getShippingMethod
 */
class HuahanTransportService extends BaseTransportService
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
    public $base_url = 'http://api.hh-exp.com/default/svc/web-service';

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
     * 获取平台发货渠道
     * @param $platform_type
     * @param $shipping_method_code
     * @param $order_id
     * @return string
     */
    public static function getPlatformTransportCode($platform_type,$shipping_method_code = null,$order_id = '')
    {
        $code = 'Huahan';
        switch ($platform_type) {
            case Base::PLATFORM_MICROSOFT:
                $code = 'HuaHan Logistics';
                break;
        }
        return $code;
    }

    /**
     * @throws \Exception
     */
    public function request($service, $params = '')
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://www.example.org/Ec/">
    <SOAP-ENV:Body>
        <ns1:callService>            
            <paramsJson>' . json_encode($params) . '</paramsJson>
            <appToken>' . $this->user_id . '</appToken>
            <appKey>' . $this->api_secret . '</appKey>
            <service>' . $service . '</service>
        </ns1:callService>
    </SOAP-ENV:Body>
</SOAP-ENV:Envelope>';
        $response = $this->getClient()->post('', ['body' => $xml]);
        $response = (string)$response->getBody();
        $response = str_replace(['</SOAP-ENV:', '</ns1:'], '</', $response);
        $response = str_replace(['<SOAP-ENV:', '<ns1:'], '<', $response);
        $output = json_decode(json_encode(simplexml_load_string($response)), true);
        $result = [];
        if (!empty($output['Body'])) {
            $result = $output['Body']['callServiceResponse']['response'];
            $result = json_decode($result, true);
        }
        CommonUtil::logs('Huahan service:'.$service.',params:'.json_encode($params)  . ',result:' . json_encode($result), "transport");
        if(!empty($result['ask']) && $result['ask'] == 'Success') {
            return $result;
        }

        $error = !empty($result['message'])?$result['message']:'未知错误';
        if(!empty($result['Error'])){
            $error = $error .' '. $result['Error']['errMessage'] . '('.$result['Error']['errCode'].')';
        }
        throw new \Exception($error);
    }

    /**
     * 获取发货渠道
     * @return array
     */
    public function getChannels()
    {
        try {
            $result = $this->request('getShippingMethod');
            if(empty($result) || empty($result['data'])){
                return self::getResult(self::RESULT_FAIL, '', '接口异常');
            }

            $result = $result['data'];
            $channel = [];
            foreach ($result as $v) {
                $channel[] = [
                    'id' => $v['code'],
                    'name' => $v['cn_name'],
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
     * @param $code // 此接口支持批量查询，建议一次请求50票；不建议并发请求，并发可能会被防火墙拦截 *
     * @return array
     */
    public function getTrackLogistics($code)
    {
        try {
            $result = $this->request('getCargoTrack',[
                'codes' => (array)$code
            ]);
            if(empty($result)){
                return self::getResult(self::RESULT_FAIL, '', '接口异常');
            }
            $result = current($result);
            if (empty($result) || empty($result['Data'])) {
                return self::getResult(self::RESULT_SUCCESS, '', '');
            } else {
                return self::getResult(self::RESULT_SUCCESS, $result['Data'], '');
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
            $key = 0;
            $parcels = [];
            $weight = 0;

            $currency_code = 'USD';
            $ioss = (new OrderService())->getTaxNumber($order);

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
                    "invoice_enname"=> $declare_name_en,// 英文海关申报品名
                    "invoice_cnname"=> $declare_name_cn,// 中文海关申报品名
                    "invoice_weight"=> round($v['declare_weight'] / $v['declare_num'], 2),// 申报重量，单位KG, 精确到三位小数。
                    "invoice_quantity" => $v['declare_num'],// 数量
                    "invoice_unitcharge"=> round($v['declare_price'] / $v['declare_num'], 2),// "申报总价值，必填",
                    "invoice_currencycode"=>$currency_code,// "申报币种",
                    "invoice_note"=>$sku,// "配货信息",
                ];
                if (!empty($v['declare_customs_code'])) {
                    $info['hs_code'] = $v['declare_customs_code'];// "海关编码",
                }
                $parcels[] = $info;
                $key++;
            }

            if ($order['country'] == "UK") {
                $countryCode = "GB";
            } else {
                $countryCode = $order['country'];
            }

            //coupang 需要用户税号
            $national_id = in_array($order['source'],[Base::PLATFORM_COUPANG,Base::PLATFORM_GMARKE])?$order['user_no']:'';
            if($countryCode == 'BR') {
                $national_id = empty($order['user_no'])?'':$order['user_no'];
            }

            //重复发货订单号
            //$user_order_number =  empty($order['track_no'])?$order['relation_no']:$order['track_no'];
            $user_order_number = $order['relation_no'];
            $user_order_number = !empty($order['logistics_reset_times']) ? ($user_order_number . '-' . $order['logistics_reset_times']) : $user_order_number;

            $data = [
                'reference_no' => $user_order_number,//客户参考号(客户自定义单号，不能重复，需唯一)
                'shipping_method' => $shipping_method_code,//运输方式代码
                'country_code' => $countryCode,//收件国家二字代码
                'extra_service' => '',//附加服务代码，每个以英文分号“;”隔开
                "order_weight" => $weight,//总重
                "order_pieces" => 1,//外包装件数,默认1（默认值1即可,发FBA空海运等一票货多箱的时候才需要根据情况设置）
                "mail_cargo_type" => 4,//包裹申报种类（1-Gif礼品；2-CommercialSample商品货样；3-Document文件；4-Other其他。默认4）
                //"length" => 1,
                //"width" => 1,
                //"height" => 1,
                'Consignee' => [
                    'consignee_company' => $order['company_name'],//收件人公司名
                    'consignee_province' => $order['area'],//收件人省
                    'consignee_city' => $order['city'],//收件人城市
                    'consignee_street' => $order['address'],//收件地址街道
                    'consignee_postcode' => $order['postcode'],//收件地址邮编
                    'consignee_name' => $order['buyer_name'],//收件人
                    'consignee_telephone' => $order['buyer_phone'],//收件电话
                    'consignee_mobile' => $order['buyer_phone'],//收件地址街道
                    'consignee_email' => $order['email'],//收件人邮箱
                    'consignee_taxno' => $national_id,//收件人税号（VAT税号）
                    'IOSS' => $ioss,//欧盟税号（ioss税号）
                ],
                'Shipper' => [
                    'shipper_company' => '',
                    'shipper_countrycode' => 'CN',
                    'shipper_province' => '广东省',
                    'shipper_city' => '中山市',
                    'shipper_street' => '彩虹大道88号汉宏创意园A1002',
                    'shipper_postcode' => '528411',
                    'shipper_name' => '廖生',
                    'shipper_telephone' => '13560667088',
                    'shipper_mobile' => '13560667088',
                    //'shipper_taxno' => ''//发件人税号（发件人VAT税号）
                ],
                'ItemArr' => $parcels,
            ];

            CommonUtil::logs('Huahan orderId:' . $order_id . ' ' . json_encode($data), "transport");

            $response = $this->request('createOrder',$data);
            if(empty($response)){
                return self::getResult(self::RESULT_FAIL, '', '接口异常');
            }
            //记录返回数据到log文件
            CommonUtil::logs('Huahan response,result,orderId:' . $order_id . ':' . print_r($response, true), "transport");
            if(!empty($response['order_code'])) {
                //获取跟踪号
                CommonUtil::logs('Huahan success,result,orderId:' . $order_id . ' ' . json_encode($response), "transport");
                $track_no = $response['shipping_method_no'];
                $track_logistics_no = $response['order_code'];//跟踪号
                return self::getResult(self::RESULT_SUCCESS, ['track_no' => $track_no, 'track_logistics_no' => $track_logistics_no], "上传成功，客户单号：" . $track_no);
            } else {
                $error = empty($response['message'])?'创建订单失败!':$response['message'];
                if(!empty($response['Error'])) {
                    $error .= $response['Error']['errMessage'];
                }
                CommonUtil::logs('Huahan error,result,orderId:' . $order_id . ' ' . json_encode($response), "transport");
                return self::getResult(self::RESULT_FAIL, '', $error);
            }
        } catch (\Exception $e) {
            return self::getResult(self::RESULT_FAIL, '', '创建订单失败!'.$e->getMessage());
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

        CommonUtil::logs('Huahan print result,orderId:' . $order_ids . print_r($customer_number_arr, true), "transport");

        try {
            $result = $this->request('batchGetLabelUrl',[
                'reference_nos' => (array)$customer_number_arr,
                'label_type' => 1,
                'label_content_type' => 4,
                'extra_option' => [
                    'print_declare_info' => 'Y'
                ]
            ]);
            CommonUtil::logs('Huahan print success,result,orderId:' . $order_ids . ' ' . json_encode($result), "transport");
            if(empty($result) || empty($result['url'])) {
                return self::getResult(self::RESULT_FAIL, '', '打印运单失败,没有获取到正确的PDF链接');
            }
            $url = $result['url'];
            if($is_show) {
                header("Content-type: application/pdf");
                echo file_get_contents($url);
                exit();
            }
            return self::getResult(self::RESULT_SUCCESS, ['pdf_url' => $url], '连接已生成,请点击并打印');
        } catch (\Exception $e) {
            return self::getResult(self::RESULT_FAIL, '', '打印运单失败,'.$e->getMessage());
        }
    }

}
